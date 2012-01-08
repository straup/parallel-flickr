<?php

	loadlib("flickr_faves");
	loadlib("flickr_api");
	loadlib("api_utils_flickr");

	#################################################################

	function api_flickr_favorites_add(){

		$flickr_user = api_utils_flickr_ensure_token_perms($GLOBALS['cfg']['user'], 'write');

		$photo_id = post_int64("photo_id");

		if (! $photo_id){
			api_output_error(999, "Missing photo ID");
		}

		# just silently ignore things that have already been faved

		if (! flickr_faves_is_faved_by_user($GLOBALS['cfg']['user'], $photo_id)){

			$method = 'flickr.favorites.add';

			$args = array(
				'photo_id' => $photo_id,
				'auth_token' => $flickr_user['auth_token'],
			);

			$rsp = flickr_api_call($method, $args);

			if ((! $rsp['ok']) && ($rsp['error_code'] != 3)){
				api_output_error(999, $rsp['error']);
			}
		}

		$out = array(
			'photo_id' => $photo_id,
		);

		api_output_ok($out);
	}

	#################################################################

	# NOTE: this does not delete faves from parallel-flickr because
	# that side of things hasn't been tackled yet (20120108/straup)

	function api_flickr_favorites_remove(){

		$flickr_user = api_utils_flickr_ensure_token_perms($GLOBALS['cfg']['user'], 'write');

		$photo_id = post_int64("photo_id");

		if (! $photo_id){
			api_output_error(999, "Missing photo ID");
		}

		$method = 'flickr.favorites.remove';

		$args = array(
			'photo_id' => $photo_id,
			'auth_token' => $flickr_user['auth_token'],
		);

		$rsp = flickr_api_call($method, $args);

		# Just ignore if not in faves already...

		if ((! $rsp['ok']) && ($rsp['error_code'] != '1')){
			api_output_error(999, $rsp['error']);
		}

		$out = array(
			'photo_id' => $photo_id,
		);

		api_output_ok($out);
	}

	#################################################################

?>
