<?php

	include("include/init.php");

	loadlib("flickr_photos_archives");
	loadlib("flickr_photos_utils");

	$year = get_int32("year");
	$month = get_int32("month");
	$day = get_int32("day");

	if ((! $year) || (! $month) || (! $day)){
		error_404();
	}

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

	$GLOBALS['smarty']->assign_by_ref("owner", $owner);
	$GLOBALS['smarty']->assign("is_own", $is_own);

	$more = array(
		'viewer_id' => $GLOBALS['cfg']['user']['id'],
		'page' => get_int32("page"),
	);

	$user_context = get_str("context");
	$user_context = ($user_context == 'posted') ? 'posted' : 'taken';
	$GLOBALS['smarty']->assign("context", $user_context);

	$more['context'] = $user_context;

	$rsp = flickr_photos_archives_for_user_and_day($owner, $year, $month, $day, $more);
	$photos = $rsp['rows'];

	flickr_photos_utils_assign_can_view_geo($photos, $GLOBALS['cfg']['user']['id']);

	$GLOBALS['smarty']->assign_by_ref("owner", $owner);
	$GLOBALS['smarty']->assign_by_ref("photos", $photos);

	$pagination_url = flickr_urls_photos_user_archives($owner, $user_context);
	$pagination_url .= "{$year}/{$month}/{$day}/";

	$GLOBALS['smarty']->assign("pagination_url", $pagination_url);

	$GLOBALS['smarty']->assign("year", $year);
	$GLOBALS['smarty']->assign("month", $month);
	$GLOBALS['smarty']->assign("day", $day);

	$GLOBALS['smarty']->display("page_flickr_photos_user_archives_day.txt");
	exit();
?>
