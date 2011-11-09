<?php

	#################################################################

	loadlib("flickr_photos");
	loadlib("flickr_photos_lookup");
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

				flickr_photos_import_photo($photo);
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

		#

		$req = array();

		if (($more['force']) || (! file_exists($local_small))){
			$req[] = array($small, $local_small);
		}

		if (($more['force']) || (! file_exists($local_orig))){
			$req[] = array($orig, $local_orig);
		}

		# fetch the metadata

		if (($more['force']) || (! file_exists($local_info))){

			# viewer id and not photo owner?
			$flickr_user = flickr_users_get_by_user_id($photo['user_id']);

			$method = 'flickr.photos.getInfo';

			$args = array(
				'auth_token' => $flickr_user['auth_token'],
				'photo_id' => $photo['id'],
			);

			list($url, $args) = flickr_api_call_build($method, $args);
			$api_call = $url . "?". http_build_query($args);

			$req[] = array($api_call, "json_encode:{$local_info}");
		}

		# fetch all the bits using http_multi()

		if ($count = count($req)){

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

				if (preg_match("/^json_encode:(.*)$/", $local, $m)){
					$data = json_encode($_rsp['body']);
					$local = $m[1];
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

		if ($ispublic){
			$perms = 0;
		}

		else if (($isfamily) && ($isfriend)){
			$perms = 4;
		}

		else if ($isfamily){
			$perms = 3;
		}

		else if ($isfriend){
			$perms = 2;
		}

		else {
			$perms = 5;
		}

		$photo['perms'] = $perms;

		# echo "{$photo['id']} perms:{$perms} public:{$ispublic} friend:{$isfriend} family:{$isfamily}\n";

		unset($photo['ispublic']);
		unset($photo['isfamily']);
		unset($photo['isfriend']);

		# media (status)

		$photo['media'] = ($photo['media'] == 'photo') ? 0 : 1;
		unset($photo['media_status']);

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
