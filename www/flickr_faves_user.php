<?php

	include("include/init.php");

	loadlib("flickr_users");
	loadlib("flickr_photos");
	loadlib("flickr_faves");
	loadlib("flickr_urls");
	loadlib("flickr_dates");

	#

	$viewer = $GLOBALS['cfg']['user'];

	$flickr_user = flickr_users_get_by_url();
	$owner = users_get_by_id($flickr_user['user_id']);

	$is_own = ($owner['id'] == $viewer['id']) ? 1 : 0;
	$GLOBALS['smarty']->assign("is_own", $is_own);

	#

	$more = array(
		'viewer_id' => $viewer['id'],
		'page' => get_int32("page"),
	);

	if ($by_alias = get_str("by_alias")){

		if ($by_flickr_user = flickr_users_get_by_path_alias($by_alias)){
			$more['by_owner'] = users_get_by_id($by_flickr_user['user_id']);
		}
	}

	else if ($by_nsid = get_str("by_nsid")){

		if ($by_flickr_user = flickr_users_get_by_nsid($by_nsid)){
			$more['by_owner'] = users_get_by_id($by_flickr_user['user_id']);
		}
	}

	else {}

	$by_owner = (isset($more['by_owner'])) ? $more['by_owner'] : null;

	$faves = flickr_faves_for_user($owner, $more);
	$photos = array();

	foreach ($faves['rows'] as $f){

		$photo = flickr_photos_get_by_id($f['photo_id']);
		$photo['owner'] = users_get_by_id($photo['user_id']);

		# quick hack until perms are denormalized into the FlickrFaves table
		$photo['canview'] = flickr_photos_permissions_can_view_photo($photo, $viewer['id']);

		# going to leave this disable until I figure out what to
		# do about reciprical contacts hoohah...

		if ($is_own){
			$photo['canview'] = 1;
		}

		$photos[] = $photo;
	}

	$GLOBALS['smarty']->assign_by_ref("owner", $owner);
	$GLOBALS['smarty']->assign_by_ref("by_owner", $by_owner);

	$GLOBALS['smarty']->assign_by_ref("photos", $photos);

	$pagination_url = flickr_urls_faves_user($owner, $by_owner);
	$GLOBALS['smarty']->assign("pagination_url", $pagination_url);

	$GLOBALS['smarty']->display("page_flickr_faves_user.txt");
	exit();
?>
