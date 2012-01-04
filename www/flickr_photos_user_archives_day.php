<?php

	include("include/init.php");

	loadlib("flickr_photos_archives");
	loadlib("flickr_photos_utils");

	$year = get_int32("year");
	$month = get_str("month");
	$day = get_str("day");

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

	$months = dates_utils_months();
	$GLOBALS['smarty']->assign_by_ref("months", $months);

	if (count($photos)){

		flickr_photos_utils_assign_can_view_geo($photos, $GLOBALS['cfg']['user']['id']);

		$user_days = flickr_photos_archives_days_for_user($owner, $year, $month, $more);

		$count_days = count($user_days);

		for ($i=0; $i < $count_days; $i++){

			if ($user_days[$i] != $day){
				continue;
			}

			$next_day = $user_days[$i+1];
			$previous_day = $user_days[$i-1];
			break;
		}

		$GLOBALS['smarty']->assign("next_day", $next_day);
		$GLOBALS['smarty']->assign("previous_day", $previous_day);

		$ymd = implode("-", array($year, $month, $day));

		if (! $previous_day){
			if ($previous_ymd = flickr_photos_archives_previous_date_for_user($owner, $ymd, $more)){
				$GLOBALS['smarty']->assign("previous", explode("-", $previous_ymd));
			}
		}

		if (! $next_day){
			if ($next_ymd = flickr_photos_archives_next_date_for_user($owner, $ymd, $more)){
				$GLOBALS['smarty']->assign("next", explode("-", $next_ymd));
			}
		}

	}

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
