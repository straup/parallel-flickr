<?php

	#################################################################

	loadlib("flickr_api");
	loadlib("api_utils_flickr");

	#################################################################

	function api_flickr_photos_geo_setContext(){

		api_utils_flickr_ensure_token_perms($GLOBALS['cfg']['user'], 'write');

		$photo_id = post_str("photo_id");

		if (! $photo_id){
			api_output_error(999, "Missing photo ID");
		}

		$context = post_str("context");

		if (! $context){
			api_output_error(999, "Missing context");
		}

		api_output_error(999, "Because I said so");

		# Sanity check photo and context here...

		$method = 'flickr.photos.get.setContext';

		$args = array(
			'photo_id' => $photo_id,
			'context' => $context,
		);

		$rsp = flickr_api_call($method, $args);

		if (! $rsp['ok']){
			api_output_error(999, $rsp['error']);
		}

		# update db here...

		api_output_ok();
	}

	#################################################################
?>
