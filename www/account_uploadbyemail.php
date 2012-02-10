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

	if ($can_upload){

		$crumb_key = 'uploadbyemail';
		$crumb_ok = crumb_check($crumb_key);

		$GLOBALS['smarty']->assign("crumb_key", $crumb_key);

		$do_reset = 0;

		if (($crumb_ok) && (post_str("create"))){
			$GLOBALS['smarty']->assign("is_new", 1);
			$do_reset = 1;
		}

		else if (($crumb_ok) && (post_str("reset"))){
			$GLOBALS['smarty']->assign("is_reset", 1);
			$do_reset = 1;
		}

		else {}

		# TO DO: error checking/handling

		if ($do_reset){
			$rsp = users_reset_uploadbyemail_address($GLOBALS['cfg']['user']);
			$GLOBALS['cfg']['user']['uploadbyemail_address'] = $rsp['uploadbyemail_address'];
		}
	}

	$GLOBALS['smarty']->assign("is_registered", $is_registered);
	$GLOBALS['smarty']->assign("can_upload", $can_upload);

	$GLOBALS['smarty']->display("page_account_uploadbyemail.txt");
	exit();	
?>
