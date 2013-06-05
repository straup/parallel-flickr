<?php

	include("include/init.php");

	features_ensure_enabled("flickr_push");

	loadlib("flickr_push_subscriptions");
	loadlib("flickr_push_photos");
	loadlib("flickr_push_utils");

	loadlib("flickr_push_littleprinter");

	loadlib("flickr_backups");
	loadlib("flickr_users");
	loadlib("flickr_api");

	loadlib("syndication_atom");

	$secret_url = get_str("secret_url");

	if (! $secret_url){
		error_404();
	}

	# error_log("[PARALLEL] updates for {$secret_url}");

	$subscription = flickr_push_subscriptions_get_by_secret_url($secret_url);

	if (! $subscription){
		# error_log("[PARALLEL] no subscription for {$secret_url}");
		error_404();
	}

	if ($verify_token = get_str("verify_token")){

		if ($subscription['verify_token'] != $verify_token){
			error_404();
		}

		$mode = get_str('mode');

		if ($mode == 'subscribe'){

			$update = array(
				'verified' => time(),
			);

			$rsp = flickr_push_subscriptions_update($subscription, $update);
		}

		else if ($mode == 'unsubscribe'){

			$rsp = flickr_push_subscriptions_delete($subscription);

			if (! $rsp['ok']){
				error_404();
			}
		}

		else {
			error_404();
		}

		echo get_str("challenge");
		exit();
	}

	# TO DO: check $subscription['topic_id'] here against
	# the 'flickr_push_enable_*' flags (20111203/straup)

	$xml = file_get_contents('php://input');
	$atom = syndication_atom_parse_str($xml);

	$user = users_get_by_id($subscription['user_id']);
	$flickr_user = flickr_users_get_by_user_id($user['id']);

	$do_push_backups = features_is_enabled("flickr_push_backups");
	$is_push_backup = flickr_push_subscriptions_is_push_backup($subscription);
	$is_backup_user = flickr_backups_is_registered_user($user);

	$to_backup = array();
	$wtf = array();

	$new = 0;

	foreach ($atom->items as $e){

		# TO DO: check $subscription['topic_id'] here because
		# at some point if we start to use the push stuff to
		# track things we're backing we'll need to store the
		# data in another table (20111203/straup)

		if (! preg_match("!.*/(\d+)$!", $e['id'], $m)){
			continue;
		}

		$photo_id = $m[1];
		$update_type = (isset($e['flickr']['update@type'])) ? $e['flickr']['update@type'] : '';

		# for debugging...
		# $fh = fopen("/tmp/wtf.json", "a");
		# fwrite($fh, "photo: {$photo_id} update: {$update_type}\n");
		# fwrite($fh, "user: {$flickr_user['nsid']} author: {$e['flickr']['author_nsid']} bacup: {$is_backup_user}\n");
		# fwrite($fh, "title {$e['media']['category']}\n");
		# fwrite($fh, "\n---\n");
		# fclose($fh);

		# See this: It's not ideal but there you go. The push stuff includes neither
		# the author of a tag nor the description of the photo (I think) so we're going
		# to filter photos that belong to backup users whose title is 'flickr:push=ignore'
		# (20130605/straup)

		# TO DO: check update type

		if (($is_backup_user) && ($flickr_user['nsid'] == $e['flickr']['author_nsid'])){

			# TO DO: preg_match ?

			if ($e['title'] == "flickr:push=ignore"){
				continue;
			}
		}

		$photo = array(
			'photo_id' => $photo_id,
			'owner' => $e['flickr']['author_nsid'],
			'ownername' => $e['author'],
			'title' => $e['title'],
			'updated' => $e['updated'],
			'photo_url' => $e['media']['atom_content@url'],
			'thumb_url' => $e['media']['thumbnail@url'],
			'update_type' => $update_type,
		);

		if ($subscription['topic_id'] == 2){
			$photo['faved_by'] = $e['contributor_name'];
			$photo['faved_by_nsid'] = $e['flickr']['contributor_nsid'];
		}

		$enc_photo = json_encode($photo);

		$photo_data = array(
			'user_id' => $user['id'],
			'subscription_id' => $subscription['id'],
			'photo_id' => $photo_id,
			'photo_data' => $enc_photo,
		);

		$debug = "user: {$user['id']} topic: {$subscription['topic_id']} photo: {$photo_id}";
		$wtf[] = $debug;

		$rsp = flickr_push_photos_record($user, $photo_data);

		# You may be asking yourself: OMGWTFBBQ??!?!?
		# The reasons are discussed below (20120605/straup)

		if (($do_push_backups) && ($is_push_backup) && ($is_backup_user)){

			$args = array(
				'photo_id' => $photo_id,
				'auth_token' => $flickr_user['auth_token'],
			);

			$to_backup[] = $args;
		}

		if ($rsp['ok']){
			$new ++;
		}

		else {
			error_log("[PARALLEL] " . var_export($rsp, 1));
		}
	}

	if ($new){

		$update = array(
			'last_update' => time(),
		);

		$rsp = flickr_push_subscriptions_update($subscription, $update);
	}

	flickr_push_photos_purge();

	# Okay, now we're going to deal with push backups. It's a bit
        # complicated, for historical reasons outlined below. The term
	# 'SPR' refers to a 'standard photo response' (SPR) which Kellan
	# wrote a good blog post on while back. (20120605/straup)

	# http://code.flickr.com/blog/2008/08/19/standard-photos-response-apis-for-civilized-age/

	# asc: do the push/atom feeds return a bunch of exif gobbledygook
	#   for [redacted] 's benefit?
	# nw: dunno what the original reason for all goblledygook
	# nw: I just used the existing rss feed templates
	# asc: right
	# asc: hrmm… that complicates things
	# asc: because all the import_* stuff is predicated on having access
	#   to a bunch of details not included in the rss/atom feed specs
	# nw: sadly, i don't have commit access anymore
	# nw: what do you need changed?
	# asc: the short version – the one where I couldn't say exactly how
	#   it affects things – is to be able to have the push stuff return a SPR
	# asc: SPR + extras, really
	# asc: which would mean… I guess… passing in another / different
	#   parameter for the "output" 
	# asc: distinct from the format
	# asc: so (and I'm just making shit up now)
	# asc: { 'format': 'json', 'output': 'spr', 'extras': 'tags,geo,etc' }
	# asc: that's what you'd pass along when you subscribed to something
	# nw: yeah I see
	# nw: it really wouldn't be atom
	# asc: right
	# asc: well, atom is kind of stupid to start with
	# asc: so there's that
	# nw: yeah
	# nw: actually that would make sense and is a good idea
	# asc: shame we don't work at flickr anymore...
	# asc: I guess what I'll do is
	# asc: - bucket all the photos that need to be backed up (for
	#   example, not your contacts faves) 
	# asc: - issue a big ass multi_http request at the end to
	#   photos.getInfo
	# asc: - assume that it will all come back fast enough
	# nw: ugh
	# asc: - and then build SRP blobs
	# asc: and feed them to the import_* functions
	# asc: also, I am totally including an abridged version of this
	#   conversation in the comments ;-)
	# nw: har
	# nw: If I had been thinking clearly I would have built this
	#   in before i left
	# nw: or more to the point, just made my/ourselves an output
	#   format that included everything
	# nw: in json
	# asc: yeah
	# asc: you did the right thing
	# asc: following the spec
	# asc: it's just that now it's clear where the stress points are
	# asc: or at least one of them
	# asc: and there's no way to fix it :-P
	# nw: after all it is just passing a photo object into a
	#   template
	# nw: so it would be make a new template
	# nw: or even extend the json one
	# nw: and put extra fields in it
	# nw: what data are you missing?
	# nw: so some of it is only for owners
	# nw: which would make sense as to why I didn't put it in
	#   before :)
	# asc: yeah
	# nw: there would have to be a little trickiness to wire it
	#   up so that you could only use that format if you were getting your photos
	# nw: or just remove the stuff you're not authed for

	# So, instead we're going to call flickr.photos.getInfo a bunch...

	# Note: this assumes that $to_backups will simply be empty if the
        # various feature flags around push backups are disabled.

 	if (count($to_backup)){

		loadlib("http");
		loadlib("flickr_photos_import");
		loadlib("flickr_faves_import");

		$reqs = array();

		foreach ($to_backup as $args){

			list($url, $args) = flickr_api_call_build('flickr.photos.getInfo', $args);

			$url = $url . "?" . http_build_query($args);

			$reqs[] = array(
				'method' => 'GET',
				'url' => $url,
			);
		}

		$multi_rsp = http_multi($reqs);

		$topic_map = flickr_push_topic_map();
		$topic_id = $subscription['topic_id'];
		$topic = $topic_map[$topic_id];

		foreach ($multi_rsp as $rsp){

			$rsp = flickr_api_parse_response($rsp);

			if (! $rsp['ok']){
				continue;
			}

			$photo = $rsp['rsp']['photo'];
			$spr = flickr_push_utils_info2spr($photo);

			log_info("[PUSH] wtf: {$topic} ({$user['id']}) start import...");
			# log_info("[PUSH] SPR " . var_export($spr, 1));

			$import_rsp = null;

			if ($topic == 'my_photos'){
				$import_rsp = flickr_photos_import_photo($spr);
			}

			else if ($topic == 'my_faves'){
				$import_rsp = flickr_faves_import_photo($spr, $user);
			}

			else if ($topic == 'commons'){
				$import_rsp = flickr_photos_import_photo($spr);

				# $rsp = flickr_push_littleprinter($spr);				
			}

			else {
				# log_info("skip photo for '{$user['id']}' : '{$topic}'");
			}

			# log_info("[DUMP] {$topic} ({$user['id']}) : " . var_export($import_rsp, 1));
		}
		
	}

	# log_info("[PUSH] wtf: " . count($wtf));

	if (count($wtf)){
		# $msg = implode("\n", $wtf);
		# log_info("[PUSH] wtf: " . $msg);
		# mail('aaron@aaronland.net', 'parallel-flickr push debug', $msg);
	}

	exit();
?>
