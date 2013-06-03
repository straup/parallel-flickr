<?php

	loadlib("flickr_faves");
	loadlib("api_parallel_flickr_utils");

	#################################################################

	function api_parallel_flickr_favorites_getList(){

		$owner = $GLOBALS['cfg']['user'];

		$args = array();
		api_utils_ensure_pagination_args($args);

		$rsp = flickr_faves_for_user($owner, $args);
		$photos = array();

		foreach ($rsp['rows'] as $row){
			$photo = flickr_photos_get_by_id($row['photo_id']);
			$spr = api_parallel_flickr_utils_photo2spr($photo);

			$spr['date_faved'] = gmdate("Y-m-d G:i:s", $row['date_faved']);
			$photos[] = $spr;
		}

		$out = array(
			'photos' => $photos,
		);

		api_utils_ensure_pagination_results($out, $rsp['pagination']);
		api_output_ok($out);
	}

	#################################################################

	# the end
