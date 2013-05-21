<?php

	loadlib("flickr_api");

	########################################################################
	
	function dbtickets_flickr_create(){

		$tmp_dir = sys_get_temp_dir();
		$tmp_file = tempnam($tmp_dir, "dbtickets_flickr") . ".gif";

		$fh = fopen($tmp_file, 'wb');
		fwrite($fh, base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw=='));
		fclose($fh);

		$auth_token = $GLOBALS['cfg']['dbtickets_flickr_auth_token'];

		$args = array(
			'auth_token' => $auth_token,
		);

		$more = array(
			'http_timeout' => 10,
		);

		$rsp = flickr_api_upload($tmp_file, $args, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$photo_id = $rsp['photo_id'];

		$args = array(
			'photo_id' => $photo_id,
			'auth_token' => $auth_token,
		);

		$rsp = flickr_api_call('flickr.photos.delete', $args);

		return array(
			'ok' => 1,
			'id' => $photo_id,
		);
	}

	########################################################################

	# the end
