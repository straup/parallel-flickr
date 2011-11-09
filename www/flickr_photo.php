<?php

	include("include/init.php");

	loadlib("flickr_photos");
	loadlib("flickr_photos_metadata");
	loadlib("flickr_photos_permissions");

	loadlib("flickr_users");
	loadlib("flickr_urls");

	$photo_id = get_int32("id");

	if (! $photo_id){
		error_404();
	}

	$photo = flickr_photos_get_by_id($photo_id);

	if (! $photo['id']){
		error_404();
	}

	if ($photo['deleted']){
		$GLOBALS['smarty']->display("page_photo_deleted.txt");
		exit();
	}

	if (! flickr_photos_permissions_can_view_photo($photo, $GLOBALS['cfg']['user']['id'])){
		error_403();
	}

	$perms_map = flickr_photos_permissions_map();
	$photo['str_perms'] = $perms_map[$photo['perms']];

	$GLOBALS['smarty']->assign_by_ref("photo", $photo);

	$owner = users_get_by_id($photo['user_id']);
	$GLOBALS['smarty']->assign_by_ref("owner", $owner);

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$GLOBALS['smarty']->assign("is_own", $is_own);

	$bookends = flickr_photos_get_bookends($photo, $GLOBALS['cfg']['user']['id']);
	$GLOBALS['smarty']->assign_by_ref("before", $bookends['before']);
	$GLOBALS['smarty']->assign_by_ref("after", $bookends['after']);

	# $meta = flickr_photos_metadata_load($photo);
	# $GLOBALS['smarty']->assign_by_ref("metadata", $meta['data']);

	$GLOBALS['smarty']->display("page_flickr_photo.txt");
	exit();
?>
