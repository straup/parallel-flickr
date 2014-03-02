<?php

	include("include/init.php");

	loadlib("flickr_photos_archives");
	loadlib("flickr_photos_utils");

	loadlib("dates_utils");

	$year = get_int32("year");

	if (! $year){
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

	$rsp = flickr_photos_archives_for_user_and_year($owner, $year, $more);
	flickr_photos_utils_inflate_photo_rows($rsp['rows']);

	$photos = $rsp['rows'];
	
	flickr_photos_utils_assign_can_view_geo($photos, $GLOBALS['cfg']['user']['id']);

	$years = flickr_photos_archives_years_for_user($owner, $more);
	$count_years = count($years);

	for ($i=0; $i < $count_years; $i++){

		if ($years[$i] != $year){
			continue;
		}

		$next_year = $years[$i+1];
		$previous_year = $years[$i-1];
		break;
	}

	$months = dates_utils_months();

	$user_months = flickr_photos_archives_months_for_user($owner, $year, $more);

	$GLOBALS['smarty']->assign("next_year", $next_year);
	$GLOBALS['smarty']->assign("previous_year", $previous_year);

	if (! $previous_year){

		$ymd = "{$year}-01-01";

		if ($previous_ymd = flickr_photos_archives_previous_date_for_user($owner, $ymd, $more)){
			$GLOBALS['smarty']->assign("previous", explode("-", $previous_ymd));
		}
	}

	if (! $next_year){

		$ymd = "{$year}-12-31";

		if ($next_ymd = flickr_photos_archives_next_date_for_user($owner, $ymd, $more)){
			$GLOBALS['smarty']->assign("next", explode("-", $next_ymd));
		}
	}

	$GLOBALS['smarty']->assign("months", $months);
	$GLOBALS['smarty']->assign("user_months", $user_months);

	$GLOBALS['smarty']->assign_by_ref("photos", $photos);
	$GLOBALS['smarty']->assign("year", $year);

	$pagination_url = flickr_urls_photos_user_archives($owner, $user_context);
	$pagination_url .= "{$year}/";

	$GLOBALS['smarty']->assign("pagination_url", $pagination_url);

	$GLOBALS['smarty']->display("page_flickr_photos_user_archives_year.txt");
	exit();
?>
