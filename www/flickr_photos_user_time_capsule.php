<?php

	include("include/init.php");

	loadlib("flickr_users");
	loadlib("flickr_photos");

	if ($path = get_str("path")){
		$flickr_user = flickr_users_get_by_path_alias($path);
	}

	else if ($nsid = get_str("nsid")){
		$flickr_user = flickr_users_get_by_nsid($nsid);
	}

	if (! $flickr_user){
		error_404();
	}

	$owner = users_get_by_id($flickr_user['user_id']);

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;

	# get the date, today

	# get the date, last year

	# get photos for a date

	$photos = array();

	$GLOBALS['smarty']->assign_by_ref("owner", $owner);
	$GLOBALS['smarty']->assign_by_ref("photos", $photos['rows']);

	# $GLOBALS['smarty']->assign("date_now", 99999999);
	# $GLOBALS['smarty']->assign("date_then", 99999999);

	# permalink to archive(d) URL
	# TODO: archives

	$GLOBALS['smarty']->display("page_flickr_photos_user_time_capsule.txt");
	exit();
?>
