<?php

	#################################################################

	loadlib("flickr_photos");
	loadlib("flickr_photos_permissions");
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
			return array(
				'ok' => 0,
				'error' => 'not a valid user',
			);
		}

		$method = 'flickr.photos.search';

		$args = array(
			'user_id' => $nsid,
			'auth_token' => $flickr_user['auth_token'],
			'extras' => 'original_format,tags,media,date_upload,date_taken,geo',
			'per_page' => 100,
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
				return array(
					'ok' => 0,
					'error' => 'no photos',
				);
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

		$user = flickr_users_ensure_user_account($photo['owner'], $photo['ownername']);

		if ((! $user) || (! $user['id'])){

			return array(
				'ok' => 0,
				'error' => 'failed to retrieve user (photo owner)',
			);
		}

		$photo = _flickr_photos_import_prepare_photo($user, $photo);

		# TO DO: error handling...

		echo "add photo {$photo['id']}\n";

		if ($_photo = flickr_photos_get_by_id($photo['id'])){

			# TO DO: make this less stupid...

			unset($photo['id']);
			flickr_photos_update_photo($_photo, $photo);

			$photo = flickr_photos_get_by_id($_photo['id']);
		}

		else {
			flickr_photos_add_photo($photo);
			flickr_photos_lookup_add($photo['id'], $photo['user_id']);
		}

		flickr_photos_import_photo_files($photo, $more);

		# exif data

		$more = array(
			'force' => 1,
		);

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

		# things that depend on solr (move to a separate function?)

		if ($GLOBALS['cfg']['enable_feature_solr']){
			flickr_photos_search_index_photo($photo);
		}

		if (($GLOBALS['cfg']['enable_feature_solr']) && ($GLOBALS['cfg']['enable_feature_places'])){

			if (($photo['woeid']) && ($GLOBALS['cfg']['places_prefetch_data'])){
				flickr_places_get_by_woeid($photo['woeid']);
			}
		}

		# go!

		return array(
			'ok' => 1,
			'photo' => $photo
		);
	}

	#################################################################

	function flickr_photos_import_photo_files(&$photo, $more=array()){

		$root = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}";

		$small = "{$root}_{$photo['secret']}_z.jpg";

		if ($photo['originalsecret']){
			$orig = "{$root}_{$photo['originalsecret']}_o.{$photo['originalformat']}";
		}

		else {
			$orig = "{$root}_{$photo['secret']}_b.jpg";
		}

		if ($photo['media'] == 1){

			# http://www.flickr.com/photos/straup/2378794972/play/site/3bfc8d2bb9/
			# http://www.flickr.com/photos/straup/2378794972/play/orig/5771b28b4b/

			$video = ($photo['originalsecret']) ? "orig/{$photo['originalsecret']}" : "site/{$photo['secret']}";
			$orig = "http://www.flickr.com/photos/{$nsid}/{$photo['id']}/play/{$video}";
		}

		#

		$path = $GLOBALS['cfg']['flickr_static_path'] . flickr_photos_id_to_path($photo['id']);

		if (! file_exists($path)){
			mkdir($path, 0755, true);
		}

		#

		$local_small = "{$path}/" . basename($small);
		$local_orig = "{$path}/" . basename($orig);

		$local_info = str_replace("_o.{$photo['originalformat']}", "_i.json", $local_orig);
		$local_comments = str_replace("_o.{$photo['originalformat']}", "_c.json", $local_orig);

		# god how I wished we had implemented a system to records and pass back
		# to the API *what* had actually changed when a photo was updated; for
		# now we'll just assume that the photo hasn't been rotated or replaced...
		# (2011115/straup)

		$req = array();

		if (($more['force']) || (! file_exists($local_small))){
			$req[] = array($small, $local_small);
		}

		if (($more['force']) || (! file_exists($local_orig))){
			$req[] = array($orig, $local_orig);
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

			dumper("fetching {$count} URIs for photo {$photo['id']}");

			$multi = array();

			foreach ($req as $uris){
				list($remote, $local) = $uris;
				$multi[] = array('url' => $remote);
			}

			$rsp = http_multi($multi);

			for ($i=0; $i < $count; $i++){

				$_rsp = $rsp[$i];
				$_req = $req[$i];

				if (! $_rsp['ok']){
					# make an error/warning here...
					continue;
				} 

				list($remote, $local) = $_req;

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

				_flickr_photos_import_store($local, $data);
				dumper("wrote {$local}");
			}
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
				return array(
					'ok' => 0,
					'error' => 'no photos',
				);
			}

			foreach ($photos as $photo){
				flickr_photos_import_photo($photo, $more);
				$imported ++;
			}

			$args['page'] += 1;
		}

		return array(
			'ok' => 1,
			'count_imported' => $imported,
		);
	}

	#################################################################

	function _flickr_photos_import_store($path, &$bits){

		$fh = fopen($path, "w");
		fwrite($fh, $bits);
		fclose($fh);

		return 1;
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

			if ($photo['geo_is_public']){
				$geoperms = 0;
			}

			else if ($photo['geo_is_contact']){
				$geoperms = 1;
			}

			else if (($photo['geo_is_family']) && ($photo['geo_is_friend'])){
				$geoperms = 4;
			}

			else if ($photo['geo_is_friend']){
				$geoperms = 2;
			}

			else if ($photo['geo_is_family']){
				$geoperms = 3;
			}

			else {
				$geoperms = 5;
			}

			$photo['geoperms'] = $geoperms;

			unset($photo['place_id']);
			unset($photo['geo_is_family']);
			unset($photo['geo_is_friend']);
			unset($photo['geo_is_contact']);
			unset($photo['geo_is_public']);
		}

		if (isset($photo['date_faved'])){
			unset($photo['date_faved']);
		}

		return $photo;
	}

	#################################################################
?>
