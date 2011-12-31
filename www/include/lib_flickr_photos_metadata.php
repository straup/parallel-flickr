<?php

	loadlib("flickr_photos");

	#################################################################

	# see also: flickr_photos_update; this is necessary when we are
	# updating the solr db with new bits that the user may have changed
	# locally because the solr import stuff reads from the photos.getInfo
	# json files that have been written to disk (20111230/straup)

	function flickr_photos_metadata_refresh(&$photo){

		loadlib("flickr_api");
		loadlib("flickr_users");
		loadlib("flickr_photos_import");

		$flickr_user = flickr_users_get_by_user_id($photo['user_id']);

		$root = $GLOBALS['cfg']['flickr_static_path'];
		$path = flickr_photos_id_to_path($photo['id']) . "/";
		$fname = "{$photo['id']}_{$photo['originalsecret']}_i.json";

		$local = $root . $path . $fname;

		$method = 'flickr.photos.getInfo';

		$args = array(
			'photo_id' => $photo['id'],
			'auth_token' => $flickr_user['auth_token'],
		);

		$rsp = flickr_api_call($method, $args);

		if ($rsp['ok']){

			# don't look now but we're calling private functions
			_flickr_photos_import_store($local, $rsp['body']);

			$cache_key = "photos_meta_{$photo['id']}";
			cache_unset($cache_key);
		}

		return $rsp;
	}

	#################################################################

	function flickr_photos_metadata_load(&$photo){

		$cache_key = "photos_meta_{$photo['id']}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$root = $GLOBALS['cfg']['flickr_static_path'];
		$path = flickr_photos_id_to_path($photo['id']) . "/";
		$fname = "{$photo['id']}_{$photo['originalsecret']}_i.json";

		$meta = $root . $path . $fname;

		if (! file_exists($meta)){
			return array('ok' => 0, 'error' => 'missing meta file');
		}

		$fh = fopen($meta, "r");
		$data = fread($fh, filesize($meta));
		fclose($fh);

		$data = json_decode($data, "as hash");

		# guh... just in case stuff has been double
		# json encoded; this was a by-product of moving
		# over to the http_multi stuff and not realizing
		# what I was doing (20111114/straup)

		if (($data) && (! is_array($data))){
			$data = json_decode($data, "as hash");
		}

		if (! $data){
			return array(
				'ok' => 0,
				'error' => 'failed to decode'
			);
		}

		$rsp = array(
			'ok' => 1,
			'data' => $data
		);

		cache_set($cache_key, $rsp);
		return $rsp;
	}

	#################################################################
?>
