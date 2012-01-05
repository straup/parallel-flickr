<?php

	include("include/init.php");

	loadlib("flickr_photos_archives");
	loadlib("flickr_photos_utils");

	$year = get_int32("year");
	$month = get_str("month");	# preserve the leading 0, if present

	if ((! $year) || (! $month)){
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

	$rsp = flickr_photos_archives_for_user_and_month($owner, $year, $month, $more);
	$photos = $rsp['rows'];

	$months = dates_utils_months();
	$GLOBALS['smarty']->assign("months", $months);

	flickr_photos_utils_assign_can_view_geo($photos, $GLOBALS['cfg']['user']['id']);

	$days = dates_utils_days_for_month($year, $month);

	$user_months = flickr_photos_archives_months_for_user($owner, $year, $more);
	$user_days = flickr_photos_archives_days_for_user($owner, $year, $month, $more);

	$count_months = count($user_months);

	for ($i=0; $i < $count_months; $i++){

		if ($user_months[$i] != $month){
			continue;
		}

		$next_month = $user_months[$i+1];
		$previous_month = $user_months[$i-1];
		break;
	}

	$GLOBALS['smarty']->assign("next_month", $next_month);
	$GLOBALS['smarty']->assign("previous_month", $previous_month);

	$GLOBALS['smarty']->assign("days", $days);
	$GLOBALS['smarty']->assign("user_days", $user_days);

	if (! $previous_month){

		$ymd = implode("-", array($year, $month, 1));

		if ($previous_ymd = flickr_photos_archives_previous_date_for_user($owner, $ymd, $more)){
			$GLOBALS['smarty']->assign("previous", explode("-", $previous_ymd));
		}
	}

	if (! $next_month){

		$last_dom = dates_utils_last_dom($year, $month);
		$ymd = implode("-", array($year, $month, $last_dom));

		if ($next_ymd = flickr_photos_archives_next_date_for_user($owner, $ymd, $more)){
			$GLOBALS['smarty']->assign("next", explode("-", $next_ymd));
		}
	}

	$GLOBALS['smarty']->assign_by_ref("photos", $photos);

	$pagination_url = flickr_urls_photos_user_archives($owner, $user_context);
	$pagination_url .= "{$year}/{$month}/";

	$GLOBALS['smarty']->assign("pagination_url", $pagination_url);

	$GLOBALS['smarty']->assign("year", $year);
	$GLOBALS['smarty']->assign("month", $month);

	$GLOBALS['smarty']->display("page_flickr_photos_user_archives_month.txt");
	exit();
?>
