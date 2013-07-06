<?php

	include("include/init.php");
	loadlib("flickr_users");
	loadlib("flickr_backups");

	login_ensure_loggedin("photos/upload");

	features_ensure_enabled(array(
		"uploads", "uploads_by_web", "uploads_by_api"
	));

	$is_registered = flickr_backups_is_registered_user($GLOBALS['cfg']['user']);
	$can_upload = $is_registered;

	# TO DO: reconcile w/ photos_upload_can_upload - specifically he
	# part where we don't know where we're trying to send the photo
	# (20130706/straup)

	# TO DO: check to see if we're trying to upload things locally and
        # ensure that we have 'delete' permissions – assuming of course that
	# we're doing the dbtickets_flickr trick. Which we are absent of any
	# other ticketing / ID magic (20130630/straup)
	
	if ($can_upload){
		$flickr_user = flickr_users_get_by_user_id($GLOBALS['cfg']['user']['id']);
		$can_upload = flickr_users_has_token_perms($flickr_user, "write");
	}

	# See this? We don't actually handle uploads here – they are shuttled
	# off to the API in page itself (20130630/straup)

	if ($can_upload){
		$crumb = crumb_generate("api", "parallel.flickr.photos.upload");
		$GLOBALS['smarty']->assign("crumb", $crumb);
	}

	$GLOBALS['smarty']->assign_by_ref("filtrs", $GLOBALS['cfg']['filtr_valid_filtrs']);

	$GLOBALS['smarty']->assign("is_registered", $is_registered);
	$GLOBALS['smarty']->assign("can_upload", $can_upload);

	$GLOBALS['smarty']->display("page_flickr_photos_upload.txt");
	exit();

?>
