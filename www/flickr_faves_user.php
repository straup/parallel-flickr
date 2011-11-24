<?php

	include("include/init.php");

	loadlib("flickr_users");
	loadlib("flickr_photos");
	loadlib("flickr_faves");
	loadlib("flickr_urls");
	loadlib("flickr_dates");

	#

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
	$GLOBALS['smarty']->assign("is_own", $is_own);

	#

	$more = array(
		'viewer_id' => $GLOBALS['cfg']['user']['id'],
		'page' => get_int32("page"),
	);

	if ($by_alias = get_str("by_alias")){
		$more['by_owner'] = flickr_users_get_by_path_alias($by_alias);
	}

	else if ($by_nsid = get_str("by_nsid")){
		$more['by_owner'] = flickr_users_get_by_nsid($by_nsid);
	}

	else {}

	$faves = flickr_faves_for_user($owner, $more);
	$photos = array();

	foreach ($faves['rows'] as $f){

		$photo = flickr_photos_get_by_id($f['photo_id']);
		$photo['owner'] = users_get_by_id($photo['user_id']);

		# quick hack until perms are denormalized into the FlickrFaves table
		$photo['canview'] = flickr_photos_permissions_can_view_photo($photo, $GLOBALS['cfg']['user']['id']);

		# going to leave this disable until I figure out what to
		# do about reciprical contacts hoohah...

		if ($is_own){
			$photo['canview'] = 1;
		}

		$photos[] = $photo;
	}

	$GLOBALS['smarty']->assign_by_ref("owner", $owner);
	$GLOBALS['smarty']->assign_by_ref("photos", $photos);

	$pagination_url = flickr_urls_faves_user($owner);
	$GLOBALS['smarty']->assign("pagination_url", $pagination_url);

	$GLOBALS['smarty']->display("page_flickr_faves_user.txt");
	exit();
?>
