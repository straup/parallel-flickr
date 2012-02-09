<?php

	include("include/init.php");
	loadlib("flickr_users");

	login_ensure_loggedin();

	$flickr_user = flickr_users_get_by_user_id($GLOBALS['cfg']['user']['id']);
	$can_upload = flickr_users_has_token_perms($flickr_user, "write");

	if ($can_upload){
		# check that user is registered backup user...
	}

	if ($can_upload){
		$crumb = crumb_generate("api", "flickr.photos.upload");
		$GLOBALS['smarty']->assign("crumb", $crumb);
	}

	$GLOBALS['smarty']->assign("can_upload", $can_upload);

	$GLOBALS['smarty']->display("page_flickr_photos_upload.txt");
	exit();

?>
