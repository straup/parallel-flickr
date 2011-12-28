<?php

	loadlib("flickr_users");
	loadlib("flickr_api");

	##############################################################################

	function api_utils_flickr_ensure_token_perms(&$user, $str_perms){

		$perms_map = flickr_api_authtoken_perms_map();

		$flickr_user = flickr_users_get_by_user_id($user['id']);

		if ($perms_map[$flickr_user['token_perms']] != $str_perms){
			api_output_error(999, "Insufficient Flickr API permissions");
		}

		return $flickr_user;
	}

	##############################################################################

?>

