<?php

	loadlib("flickr_users");
	loadlib("flickr_api");

	#################################################################

	function flickr_photos_upload(&$user, $file, $args=array()){

		$flickr_user = flickr_users_get_by_user_id($user['id']);
		$flickr_perms = $flickr_user['token_perms'];

		$perms_map = flickr_api_authtoken_perms_map();

		if ($perms_map[$flickr_perms] != 'write'){
			return not_okay("insufficient perms");
		}

		$args['auth_token'] = $flickr_user['auth_token'];

		# default upload perms?

		$rsp = flickr_api_upload($file, $args);

		if (! $rsp['ok']){
			return $rsp;
		}

		if ((isset($args['async'])) && ($args['async'])){
			return $rsp;
		}

		# TO DO: archive the photo locally now that we have a photo ID
		# see also: bin/upload_by_email.php

		return $rsp;
	}

	#################################################################
?>
