<?php

	include("include/init.php");
	loadlib("flickr_users");
	loadlib("flickr_backups");

	login_ensure_loggedin("photos/upload");

	if (! $GLOBALS['cfg']['enable_feature_uploads']){
		error_disabled();
	}

	if (! $GLOBALS['cfg']['enable_feature_uploads_by_email']){
		error_disabled();
	}

	$is_registered = flickr_backups_is_registered_user($GLOBALS['cfg']['user']);
	$can_upload = $is_registered;

	if ($can_upload){
		$flickr_user = flickr_users_get_by_user_id($GLOBALS['cfg']['user']['id']);
		$can_upload = flickr_users_has_token_perms($flickr_user, "write");
	}

	if (($can_upload) && (! $user['uploadbyemail_address'])){

		$GLOBALS['cfg']['user']['uploadbyemail_address'] = 'fixme';
	}

	$GLOBALS['smarty']->assign("is_registered", $is_registered);
	$GLOBALS['smarty']->assign("can_upload", $can_upload);

	$GLOBALS['smarty']->display("page_account_uploadbyemail.txt");
	exit();	
?>
