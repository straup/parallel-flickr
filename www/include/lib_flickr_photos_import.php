<?php

	#################################################################

	loadlib("flickr_photos");
	loadlib("flickr_photos_permissions");
	loadlib("flickr_geo_permissions");
	loadlib("flickr_places");
	loadlib("flickr_photos_lookup");
	loadlib("flickr_photos_search");
	loadlib("flickr_api");
	loadlib("flickr_users");

	#################################################################

	function flickr_photos_import_for_nsid($nsid){

		$flickr_user = flickr_users_get_by_nsid($nsid);
		$user = users_get_by_id($flickr_user['user_id']);

		if (! $user){
			return not_okay("not a valid user");
		}

		$method = 'flickr.photos.search';

		$args = array(
			'user_id' => $nsid,
			'auth_token' => $flickr_user['auth_token'],
			'extras' => 'original_format,tags,media,date_upload,date_taken,geo',
			'per_page' => 100,
			'sort' => 'date-posted-desc',
			'page' => 1,
		);

		$pages = null;

		while ((! isset($pages)) || ($pages >= $args['page'])){

			$rsp = flickr_api_call($method, $args);

			if (! $rsp['ok']){
				return $rsp;
			}

			if (! isset($pages)){
				$pages = $rsp['rsp']['photos']['pages'];
			}

			$photos = $rsp['rsp']['photos']['photo'];

			if (! is_array($photos)){
				return not_okay("no photos");
			}

			foreach ($photos as $photo){

				flickr_photos_import_photo($photo, $more);
			}

			$args['page'] += 1;
		}

		return array(
			'ok' => 1,
		);
	}

	#################################################################

	# expects a SPR photo row

	function flickr_photos_import_photo($photo, $more=array()){

		log_info("get ready to import a photo...");

		$user = flickr_users_ensure_user_account($photo['owner'], $photo['ownername']);

		if ((! $user) || (! $user['id'])){
			return not_okay("failed to retrieve user (photo owner)");
		}

		$photo = _flickr_photos_import_prepare_photo($user, $photo);

		# log_info("photo..." . var_export($photo, 1));

		# TO DO: error handling...

		if ($_photo = flickr_photos_get_by_id($photo['id'])){

			log_info("update photo {$photo['id']}");

			# TO DO: make this less stupid...

			unset($photo['id']);
			flickr_photos_update_photo($_photo, $photo);

			$photo = flickr_photos_get_by_id($_photo['id']);
		}

		else {

			log_info("add photo {$photo['id']}");

			$rsp = flickr_photos_add_photo($photo);

			if (! $rsp['ok']){
				log_info("FAILED to add photo {$photo['id']} :"  . var_export($rsp, 1));
				return $rsp;
			}

			flickr_photos_lookup_add($photo['id'], $photo['user_id']);
		}

		# See also: lib_flickr_photos_upload.php

		if (! isset($more['donot_import_files'])){
			flickr_photos_import_photo_files($photo, $more);
		}

		flickr_photos_import_index_photo($photo, $more);

		# go!

		return okay(array(
			'photo' => $photo
		));
	}

	#################################################################

	function flickr_photos_import_index_photo(&$photo, $more=array()){

		# exif data

		if ($hasexif = flickr_photos_exif_has_exif($photo, $more)){

			$update = array(
				'hasexif' => 1
			);

			$rsp = flickr_photos_update_photo($photo, $update);

			# technically we'll have the old last_update date
			# but that shouldn't be a problem (20111121/straup)

			if ($rsp['ok']){
				$photo = array_merge($photo, $update);
			}
		}

		# solr (and places)

		if ($GLOBALS['cfg']['enable_feature_solr']){

			flickr_photos_search_index_photo($photo);

			if ($GLOBALS['cfg']['enable_feature_places']){

				if (($photo['woeid']) && ($GLOBALS['cfg']['places_prefetch_data'])){
					flickr_places_get_by_woeid($photo['woeid']);
				}
			}
		}

		# note the pass by ref
	}

	#################################################################

	# TO DO: this is not critical but it seems like it would be nice
	# to make it possible to indicate which of the available photo sizes
	# to download. Basically just an array in $GLOBALS['cfg'] that lists
	# photo 'extensions' as defined by flickr.photos.getSizes. This should
	# probably come *after* the storage (fs/s3) stuff is sorted out.
	# (20120320/straup)

	function flickr_photos_import_photo_files(&$photo, $more=array()){
		
		# TO DO: make things work with storage_s3 – specifically
		# no prefix (nested directories) for S3 thingies
		# (20130624/straup)
	
		$root = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}";

		$small = "{$root}_{$photo['secret']}_z.jpg";

		$ext = ($photo["originalsecret"]) ? $photo["originalformat"] : "jpg";

		if ($photo['originalsecret']){

			# This is probably really only necessary for
			# Cal's account (20111208/straup)

			$orig = ($ext) ? "{$root}_{$photo['originalsecret']}_o.{$ext}" : null;
		}

		else {
			$orig = "{$root}_{$photo['secret']}_b.{$ext}";
		}

		if ($photo['media'] == 1){

			# http://www.flickr.com/photos/straup/2378794972/play/site/3bfc8d2bb9/
			# http://www.flickr.com/photos/straup/2378794972/play/orig/5771b28b4b/

			$video = ($photo['originalsecret']) ? "orig/{$photo['originalsecret']}" : "site/{$photo['secret']}";
			$orig = "http://www.flickr.com/photos/{$nsid}/{$photo['id']}/play/{$video}";
		}

		# Note the relative paths – the parent tree is meant to be handled by
		# lib_storagemaster (20130528/straup)

		$dirname = flickr_photos_dirname($photo);

		$local_small = $dirname . basename($small);
		$local_orig = $dirname . basename($orig);

		# TO DO: use flickr_photos_basename(...)

		$local_info = str_replace("_o.{$photo['originalformat']}", "_i.json", $local_orig);
		$local_comments = str_replace("_o.{$photo['originalformat']}", "_c.json", $local_orig);

		# god how I wished we had implemented a system to records and pass back
		# to the API *what* had actually changed when a photo was updated; for
		# now we'll just assume that the photo hasn't been rotated or replaced...
		# (2011115/straup)

		$req = array();

		$small_exists = storage_file_exists($local_small);
		$orig_exists = storage_file_exists($local_orig);

		if (($more['force']) || (! $small_exists['ok'])){
			$req[] = array($small, $local_small);
		}

		if (($more['force']) || (! $orig_exists['ok'])){

			# see above

			if ($orig){
				$req[] = array($orig, $local_orig);
			}
		}

		# for now, just always fetch meta files because who knows
		# whether anything has changed; note the "json:foo:path"
		# syntax which are hints to tell the code to handle http_multi
		# responses (below) whether to inspect the contents of the
		# data returned by the flickr API

		if (! isset($more['skip_meta'])){

			# basic photo info

			# viewer id and not photo owner?
			$flickr_user = flickr_users_get_by_user_id($photo['user_id']);

			$method = 'flickr.photos.getInfo';

			$args = array(
				'auth_token' => $flickr_user['auth_token'],
				'photo_id' => $photo['id'],
			);

			list($url, $args) = flickr_api_call_build($method, $args);
			$api_call = $url . "?". http_build_query($args);

			$req[] = array($api_call, "json:info:{$local_info}");

			# fetch comments, which is to say check to see if there
			# are any new photos worth storing

			$fetch_comments = 1;

			if ($more['min_date']){

				$method = 'flickr.photos.comments.getList';

				$args = array(
					'photo_id' => $photo['id'],
					'min_comment_date' => $more['min_date'],
				);

				$rsp = flickr_api_call($method, $args);

				if (($rsp['ok']) && (! isset($rsp['rsp']['comments']['comment']))){
					$fetch_comments = 0;
				}
			}

			if ($fetch_comments){

				$method = 'flickr.photos.comments.getList';

				$args = array(
					'photo_id' => $photo['id'],
				);

				list($url, $args) = flickr_api_call_build($method, $args);
				$api_call = $url . "?". http_build_query($args);

				$req[] = array($api_call, "json:comments:{$local_comments}");
			}
		}

		# now go!

		# fetch all the bits using http_multi()

		if ($count = count($req)){
			_flickr_photos_import_fetch_multi($req);
		}

	}	

	#################################################################

	function _flickr_photos_import_fetch_multi(&$req, $retries=3){

		$multi = array();
		$failed = array();

		foreach ($req as $uris){
			list($remote, $local) = $uris;
			$multi[] = array('url' => $remote);
		}

		$count = count($multi);
		log_info("fetching {$count} URIs...");

		$rsp = http_multi($multi);

		for ($i=0; $i < $count; $i++){

			$_rsp = $rsp[$i];
			$_req = $req[$i];

			list($remote, $local) = $_req;

			log_info("{$local} ({$remote}): {$_rsp['ok']}");

			if (! $_rsp['ok']){

				$failed[] = $_req;

				$will_retry = ($retries) ? 1 : 0;

				log_info("failed to fetch {$remote}: {$rsp['error']} will retry: {$will_retry}");
				continue;
			} 

			# if $source then check to ensure we have something
			# worth writing to disk

			if (preg_match("/^json:(\w+):(.*)$/", $local, $m)){

				$data = $_rsp['body'];
				$source = $m[1];
				$local = $m[2];

				$to_check = array(
					'comments',
				);

				if (in_array($source, $to_check)){

					$json = json_decode($data, "as hash");

					if (! $json){
						continue;
					}
				}

				if ($source == 'comments'){

					if (! count($json['comments']['comment'])){
						continue;
					}
				}
			}

			else {
				$data = $_rsp['body'];
			}

			# This is necessary (at least) until we are using the
			# storagemaster_file_exists functions, above.
			# (20130527/straup)

			# $strg_local = str_replace($GLOBALS['cfg']['flickr_static_path'], "", $local);
			# log_info("store as {$strg_local}");
			# $strg_rsp = storage_put_file($strg_local, $data);

			$strg_rsp = storage_put_file($local, $data);

			log_info("wrote {$local} : {$strg_rsp['ok']}");
		}

		if ((count($failed)) && ($retries)){
			$retries = ($retries) ? $retries - 1 : 0;
			_flickr_photos_import_fetch_multi($failed, $retries);
		}
	}

	#################################################################

	function flickr_photos_import_get_recent($nsid, $more=array()){

		$flickr_user = flickr_users_get_by_nsid($nsid);
		$user = users_get_by_id($flickr_user['user_id']);

		if (! $user){
			return array(
				'ok' => 0,
				'error' => 'not a valid user',
			);
		}

		$method = 'flickr.photos.recentlyUpdated';

		if (! isset($more['min_date'])){
			$offset_days = 1;
			$offset = intval(((60 * 60 * 24) * $offset_days));
			$min_date = time() - $offset;
		}

		else {
			$min_date = intval($more['min_date']);
		}

		$args = array(
			'auth_token' => $flickr_user['auth_token'],
			'min_date' => $min_date,
			'extras' => 'original_format,tags,media,date_upload,date_taken,geo',
			'per_page' => 100,
			'page' => 1,
		);

		$pages = null;

		$imported = 0;

		# TO DO: capture dateupdate for each photo and return that
		# if there's a fatal error so that the FlickrBackups database
		# can be set with something other than 0 (20111206/straup)

		while ((! isset($pages)) || ($pages >= $args['page'])){

			# because the Flickr API has an annoying habit of
			# timing out and this causes an initial import of
			# photos to fail and be repeated in-toto over and
			# over again (20111206/straup)

			$tries = 1;
			$max_tries = 5;
			$ok = 0;

			while ((! $ok) && ($tries < $max_tries)){
				$rsp = flickr_api_call($method, $args);
				$ok = $rsp['ok'];

				$tries++;

				if ($ok){

					$photos = $rsp['rsp']['photos']['photo'];

					if (! is_array($photos)){
						$rsp = not_okay("no photos");
						$ok = 0;
					}
				}
			}

			if (! $ok){
				return $rsp;
			}

			if (! isset($pages)){
				$pages = $rsp['rsp']['photos']['pages'];
			}

			# TO DO: date update stuff (see above)

			foreach ($photos as $photo){
				flickr_photos_import_photo($photo, $more);
				$imported ++;
			}

			$args['page'] += 1;
		}

		return okay(array(
			'count_imported' => $imported,
		));
	}

	#################################################################

	function _flickr_photos_import_prepare_photo(&$user, $photo){

		$photo['user_id'] = $user['id'];

		unset($photo['owner']);
		unset($photo['ownername']);

		$fmt = "Y-m-d H:i:s";
		$photo['dateupload'] = gmdate($fmt, $photo['dateupload']);

		$ispublic = ($photo['ispublic']) ? 1 : 0;
		$isfamily = ($photo['isfamily']) ? 1 : 0;
		$isfriend = ($photo['isfriend']) ? 1 : 0;

		$perms_map = flickr_photos_permissions_map("string keys");

		if ($ispublic){
			$perms = $perms_map['public'];
		}

		else if (($isfamily) && ($isfriend)){
			$perms = $perms_map['friends and family'];
		}

		else if ($isfamily){
			$perms = $perms_map['family'];
		}

		else if ($isfriend){
			$perms = $perms_map['friends'];
		}

		else {
			$perms = $perms_map['private'];
		}

		$photo['perms'] = $perms;

		# echo "{$photo['id']} perms:{$perms} public:{$ispublic} friend:{$isfriend} family:{$isfamily}\n";

		unset($photo['ispublic']);
		unset($photo['isfamily']);
		unset($photo['isfriend']);

		# media (status)

		$photo['media'] = ($photo['media'] == 'photo') ? 0 : 1;
		unset($photo['media_status']);

		# Strictly speaking, I am probably most responsible for
		# the need to do this. I'm sorry... (20111121/straup)

		$photo['hasgeo'] = ($photo['accuracy']) ? 1 : 0;

		if (! $photo['hasgeo']){
			unset($photo['latitude']);
			unset($photo['longitude']);
			unset($photo['accuracy']);
		}

		if ($photo['hasgeo']){

			$geo_perms_map = flickr_geo_permissions_map("string keys");

			if ($photo['geo_is_public']){
				$geoperms = $geo_perms_map['public'];
			}

			else if ($photo['geo_is_contact']){
				$geoperms = $geo_perms_map['contacts'];
			}

			else if (($photo['geo_is_family']) && ($photo['geo_is_friend'])){
				$geoperms = $geo_perms_map['friends and family'];
			}

			else if ($photo['geo_is_friend']){
				$geoperms = $geo_perms_map['friends'];
			}

			else if ($photo['geo_is_family']){
				$geoperms = $geo_perms_map['family'];
			}

			else {
				$geoperms = $geo_perms_map['private'];
			}

			$photo['geoperms'] = $geoperms;
			$photo['geocontext'] = $photo['context'];

			unset($photo['place_id']);
			unset($photo['geo_is_family']);
			unset($photo['geo_is_friend']);
			unset($photo['geo_is_contact']);
			unset($photo['geo_is_public']);
		}

		if (isset($photo['context'])){
			unset($photo['context']);
		}

		if (isset($photo['date_faved'])){
			unset($photo['date_faved']);
		}

		return $photo;
	}

	#################################################################
	
	function _flickr_photos_import_flickr_urls($photo, $more=array()) {

		$root = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}";

		$small = "{$root}_{$photo['secret']}_z.jpg";

		$ext = ($photo["originalsecret"]) ? $photo["originalformat"] : "jpg";

		if ($photo['originalsecret']){

			# This is probably really only necessary for
			# Cal's account (20111208/straup)

			$orig = ($ext) ? "{$root}_{$photo['originalsecret']}_o.{$ext}" : null;
		}

		else {
			$orig = "{$root}_{$photo['secret']}_b.{$ext}";
		}

		if ($photo['media'] == 1) {

			# http://www.flickr.com/photos/straup/2378794972/play/site/3bfc8d2bb9/
			# http://www.flickr.com/photos/straup/2378794972/play/orig/5771b28b4b/

			$video = ($photo['originalsecret']) ? "orig/{$photo['originalsecret']}" : "site/{$photo['secret']}";
			$orig = "http://www.flickr.com/photos/{$nsid}/{$photo['id']}/play/{$video}";
		}
	
		return array('small' => $small, 'orig' => $orig, 'is_video' => $video);
	}
	
	
	function _flickr_photos_import_flickr_meta_urls($photo, $more=array()) {

		$req = array();

		# basic photo info

		if ($more['auth_token']) {
			$auth_token = $more['auth_token'];
		} else {
			$flickr_user = flickr_users_get_by_user_id($photo['user_id']);
			$auth_token = $flickr_user['auth_token'];
		}
		
		$method = 'flickr.photos.getInfo';

		$args = array(
			'auth_token' => $auth_token,
			'photo_id' => $photo['id'],
		);

		list($url, $args) = flickr_api_call_build($method, $args);
		$api_call = $url . "?". http_build_query($args);

		$req['info'] = $api_call;

		# fetch comments, which is to say check to see if there
		# are any new photos worth storing

		$fetch_comments = 1;

		if ($more['min_date']){

			$method = 'flickr.photos.comments.getList';

			$args = array(
				'photo_id' => $photo['id'],
				'min_comment_date' => $more['min_date'],
			);

			$rsp = flickr_api_call($method, $args);

			if (($rsp['ok']) && (! isset($rsp['rsp']['comments']['comment']))){
				$fetch_comments = 0;
			}
		}

		if ($fetch_comments){

			$method = 'flickr.photos.comments.getList';

			$args = array(
				'photo_id' => $photo['id'],
			);

			list($url, $args) = flickr_api_call_build($method, $args);
			$api_call = $url . "?". http_build_query($args);

			$req['comments'] = $api_call;
		}

		return $req;
	}
	
	function _flickr_photos_import_do_fetch_multi($reqs, $retries=3) {
		
		$multi = array();
		$failed = array();

		$success = array();

		foreach ($reqs as $uri){
			$multi[] = array('url' => $uri);
		}

		$multi_rsp = http_multi($multi);

		foreach ($multi_rsp as $rsp) {
			if ($rsp['ok']) {
				$success[] = $rsp;
			} else {
				$failed[] = $rsp;
			}
		}

		if (count($failed) and $retries > 0) {
			$retries -= 1;
			list ($success2, $failed) = _flickr_photos_import_do_fetch_multi($failed, $retries);
			$success = array_merge($success, $success2);
		}

		return array($success, $failed);
	}
