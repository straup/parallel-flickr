<?php

	loadlib("flickr_users");

	#################################################################

	function flickr_photos_upload(&$user, $file, $args=array()){

		$flickr_user = flickr_users_get_by_user_id($user['id']);
		$args['auth_token'] = $fl_user['auth_token'];

		# default upload perms?

		$rsp = flickr_api_upload($file, $args);

		if (! $rsp['ok']){
			return $rsp;
		}

		# import photo here
	}

	#################################################################
?>
