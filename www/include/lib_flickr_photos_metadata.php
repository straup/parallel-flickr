<?php

	loadlib("flickr_photos");

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
