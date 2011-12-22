<?php

	include("include/init.php");

	if (! $GLOBALS['cfg']['enable_feature_flickr_push']){
		error_disabled();
	}

	loadlib("flickr_push_subscriptions");
	loadlib("flickr_push_photos");
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

	$new = 0;

	foreach ($atom->items as $e){

		# for debugging...
		# $fh = fopen("/tmp/wtf.json", "w");
		# fwrite($fh, json_encode($e));
		# fclose($fh);

		# TO DO: check $subscription['topic_id'] here because
		# at some point if we start to use the push stuff to
		# track things we're backing we'll need to store the
		# data in another table (20111203/straup)

		if (! preg_match("!.*/(\d+)$!", $e['id'], $m)){
			continue;
		}

		$photo_id = $m[1];
		$update_type = (isset($e['flickr']['update@type'])) ? $e['flickr']['update@type'] : '';

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

		$rsp = flickr_push_photos_record($user, $photo_data);

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
	exit();
?>
