<?php

	#################################################################

	loadlib("flickr_api");
	loadlib("flickr_photos");
	loadlib("flickr_photos_geo");
	loadlib("api_utils_flickr");

	#################################################################

	function api_flickr_photos_geo_setContext(){

		$flickr_user = api_utils_flickr_ensure_token_perms($GLOBALS['cfg']['user'], 'write');

		$photo_id = post_str("photo_id");

		if (! $photo_id){
			api_output_error(999, "Missing photo ID");
		}

		$photo = flickr_photos_get_by_id($photo_id);

		if (! $photo['id']){
			api_output_error(999, "Invalid photo ID");
		}

		if ($photo['user_id'] != $GLOBALS['cfg']['user']['id']){
			api_output_error(999, "Insufficient permissions");
		}

		if (! $photo['hasgeo']){
			api_output_error(999, "Photo is not geotagged");
		}

		$context = post_str("context");

		if (! $context){
			api_output_error(999, "Missing context");
		}

		if (! flickr_photos_geo_is_valid_context($context)){
			api_output_error(999, "Invalid context");
		}

		if ($photo['geocontext'] == $context){
			api_output_ok();
		}

		# Sanity check photo and context here...

		$method = 'flickr.photos.geo.setContext';

		$args = array(
			'photo_id' => $photo_id,
			'context' => $context,
			'auth_token' => $flickr_user['auth_token'],
		);

		$rsp = flickr_api_call($method, $args);

		if (! $rsp['ok']){
			api_output_error(999, $rsp['error']);
		}

		$update = array(
			'geocontext' => $context,
		);

		$rsp = flickr_photos_update_photo($photo, $update);

		if (! $rsp['ok']){
			api_output_error(999, $rsp['error']);
		}

		api_output_ok();
	}

	#################################################################
?>
