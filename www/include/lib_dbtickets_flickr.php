<?php

	loadlib("flickr_api");

	########################################################################

	function dbtickets_flickr_create(&$user){

 		$flickr_user = flickr_users_get_by_user_id($user['id']);
		$auth_token = $flickr_user['auth_token'];

		$whoami = getmyuid();

		$tmp_dir = sys_get_temp_dir();
		$tmp_file = "{$tmp_dir}/dbtickets_flickr-{$whoami}.gif";

		if (! file_exists($tmp_file)){
			$fh = fopen($tmp_file, 'wb');
			fwrite($fh, base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw=='));
			fclose($fh);
		}

		$args = array(
			'auth_token' => $auth_token,
			'is_public' => 0,
			'title' => 'flickr:push=ignore',
		);

		$more = array(
			'http_timeout' => 30,
		);

		$rsp = flickr_api_upload($tmp_file, $args, $more);

		# unlink($tmp_file);

		if (! $rsp['ok']){
			return $rsp;
		}

		$photo_id = $rsp['photo_id'];

		$args = array(
			'photo_id' => $photo_id,
			'auth_token' => $auth_token,
		);

		# This will require full delete perms for the user...

		$rsp = flickr_api_call('flickr.photos.delete', $args);

		return array(
			'ok' => 1,
			'id' => $photo_id,
		);
	}

	########################################################################

	# the end
