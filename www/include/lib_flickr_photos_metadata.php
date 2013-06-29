<?php

	loadlib("flickr_photos");

	#################################################################

	function flickr_photos_metadata_path(&$photo, $more=array()){

		$more['abs_path'] = 1;
		$more['size'] = 'i';

		return flickr_photos_path($photo, $more);
	}

	#################################################################

	# This isn't called anywhere (yet). See notes inre updates and Solr
	# in (lib) flickr_photos_update (20111230/straup)

	function flickr_photos_metadata_refresh(&$photo){

		$rsp = flickr_photos_metadata_fetch($photo);

		if ($rsp['ok']){

			$meta = flickr_photos_metadata_path($photo);

			$cache_key = "photos_meta_{$photo['id']}";
			cache_unset($cache_key);
		}

		return $rsp;
	}

	#################################################################

	# See notes inre updates and Solr in (lib) flickr_photos_update
	# (20111230/straup)

	function flickr_photos_metadata_fetch(&$photo, $inflate=0){

		loadlib("flickr_api");
		loadlib("flickr_users");

		$flickr_user = flickr_users_get_by_user_id($photo['user_id']);

		$method = 'flickr.photos.getInfo';

		$args = array(
			'photo_id' => $photo['id'],
			'auth_token' => $flickr_user['auth_token'],
		);

		$more = array();

		if (! $inflate){
			$more['raw'] = 1;
		}

		$rsp = flickr_api_call($method, $args, $more);

		if ($rsp['ok']){

			$data = ($inflate) ? $rsp['rsp'] : $rsp['body'];

			$rsp = okay(array(
				'data' => $data,
			));
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

		$meta = flickr_photos_metadata_path($photo);

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
