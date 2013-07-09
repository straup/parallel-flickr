<?php

	include("include/init.php");
	loadlib("flickr_photos");
	loadlib("flickr_photos_exif");

	# ensure logged in; parse out photo url

	$photo_id = get_int64("id");
	$path = get_str("path");

	$url = "/photos/$path/$photo_id/";

	login_ensure_loggedin($url);

	#
	
	$photo = flickr_photos_get_by_id($photo_id);

	if (! $photo['id']){
		error_404();
	}

	if ($photo['user_id'] != $GLOBALS['cfg']['user']['id']){
		error_403();
	}

	if ($photo['deleted']){
		error_410();
	}

	# if (! flickr_photos_permissions_can_view_photo($photo, $GLOBALS['cfg']['user']['id'])){
	# 	error_403();
	# }

	$owner = users_get_by_id($photo['user_id']);
	$photo['owner'] = $owner;

	$GLOBALS['smarty']->assign_by_ref("photo", $photo);

	#

	$rsp = flickr_photos_exif_read($photo);

	if ($rsp['ok']){
		$GLOBALS['smarty']->assign_by_ref("exif", $rsp['rows']);
	}

	else {
		$GLOBALS['smarty']->assign("error", $rsp['error']);
	}

	$GLOBALS['smarty']->display("page_flickr_photo_exif.txt");
	exit();
?>
