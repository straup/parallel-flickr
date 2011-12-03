<?php

	include("include/init.php");

	loadlib("flickr_users");
	loadlib("flickr_photos");
	loadlib("flickr_urls");
	loadlib("flickr_dates");

	loadlib("flickr_geo_permissions");

	#

	$flickr_user = flickr_users_get_by_url();
	$owner = users_get_by_id($flickr_user['user_id']);

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$GLOBALS['smarty']->assign("is_own", $is_own);

	#

	$more = array(
		'page' => get_int32("page"),
		'viewer_id' => $GLOBALS['cfg']['user']['id'],
	);

	$photos = flickr_photos_for_user($owner, $more);

	$count = count($photos['rows']);

	for ($i=0; $i < $count; $i++){
		$ph = $photos['rows'][$i];
		$ph['can_view_geo'] = ($ph['hasgeo'] && flickr_geo_permissions_can_view_photo($ph, $GLOBALS['cfg']['user']['id'])) ? 1 : 0;
		$photos['rows'][$i] = $ph;
	}

	$GLOBALS['smarty']->assign_by_ref("owner", $owner);
	$GLOBALS['smarty']->assign_by_ref("photos", $photos['rows']);

	$pagination_url = flickr_urls_photos_user($owner);
	$GLOBALS['smarty']->assign("pagination_url", $pagination_url);

	$GLOBALS['smarty']->display("page_flickr_photos_user.txt");
	exit();
?>
