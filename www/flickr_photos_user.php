<?php

	include("include/init.php");

	loadlib("flickr_users");
	loadlib("flickr_photos");
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

	#

	$more = array(
		'page' => get_int32("page"),
	);

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$GLOBALS['smarty']->assign("is_own", $is_own);

	$photos = flickr_photos_for_user($owner, $GLOBALS['cfg']['user']['id'], $more);

	$GLOBALS['smarty']->assign_by_ref("owner", $owner);
	$GLOBALS['smarty']->assign_by_ref("photos", $photos['rows']);

	$pagination_url = flickr_urls_photos_user($owner);
	$GLOBALS['smarty']->assign("pagination_url", $pagination_url);

	$GLOBALS['smarty']->display("page_flickr_photos_user.txt");
	exit();
?>
