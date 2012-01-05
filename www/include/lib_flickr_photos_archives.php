<?php

	loadlib("flickr_photos_search");
	loadlib("flickr_photos_permissions");
	loadlib("dates_utils");

	#################################################################

	function flickr_photos_archives_timepies_for_user(&$user, $facet, $start, $end, $gap, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
			'mincount' => 0,
		);

		$more = array_merge($defaults, $more);

		$query = array(
			'user_id' => $user['id'],
		);

		# this kind of thing will probably need to be moved in to
		# one or more helper functions but it's not clear how or
		# where yet... (20111125/straup)

		if (($gap == "+1YEAR") || ($gap == "+1MONTH")){
			list($yyyy, $ignore) = explode("-", $start, 2);
			$start = "{$yyyy}-01-01 00:00:00";

			list($yyyy, $ignore) = explode("-", $end, 2);
			$end = "{$yyyy}-12-31 23:59:59";
		}

		$rsp = flickr_photos_search_facet_dates($query, $facet, $start, $end, $gap, $more);

		if ($rsp['ok']){

			$trimmed = array();

			foreach ($rsp[$facet] as $dt => $count){

				# see above inre: helper functions

				$ts = strtotime($dt);

				if ($gap == "+1YEAR"){
					$fmt = "Y";
				}

				else if ($gap == "+1MONTH"){
					$fmt = "Y-m";
				}

				else {
					$fmt = "Y-m-d";
				}

				$dt = gmdate($fmt, $ts);
				$trimmed[$dt] = $count;
			}

			$rsp[$facet] = $trimmed;
		}

		return $rsp;
	}

	#################################################################

	function flickr_photos_archives_for_user_and_year(&$user, $year, $more=array()){

		list($start, $end) = dates_utils_between($year);
		return flickr_photos_archives_for_user_and_range($user, $start, $end, $more);
	}

	#################################################################

	function flickr_photos_archives_for_user_and_month(&$user, $year, $month, $more=array()){

		list($start, $end) = dates_utils_between($year, $month);
		return flickr_photos_archives_for_user_and_range($user, $start, $end, $more);
	}

	#################################################################

	function flickr_photos_archives_for_user_and_day(&$user, $year, $month, $day, $more=array()){

		list($start, $end) = dates_utils_between($year, $month, $day);
		return flickr_photos_archives_for_user_and_range($user, $start, $end, $more);
	}

	#################################################################

	function flickr_photos_archives_for_user_and_range(&$user, $start, $end, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);

		$date_col = ($more['context'] == 'posted') ? 'dateupload' : 'datetaken';

		$cluster_id = $user['cluster_id'];
		$enc_user = AddSlashes($user['id']);

		$enc_start = AddSlashes($start);
		$enc_end = AddSlashes($end);

		# TO DO: indexes probably...

		$sql = "SELECT * FROM FlickrPhotos WHERE user_id='{$enc_user}' AND `{$date_col}` BETWEEN";
		$sql .= " '{$enc_start}' AND '{$enc_end}'";

		if ($perms = flickr_photos_permissions_photos_where($user['id'], $more['viewer_id'])){
			$str_perms = implode(",", $perms);
			$sql .= " AND perms IN ({$str_perms})";
		}

		$sql .= " ORDER BY `{$date_col}` ASC";

		$rsp = db_fetch_paginated_users($cluster_id, $sql, $more);

		$rsp['date_range'] = "{$start};{$end}";
		$rsp['date_column'] = $date_col;

		return $rsp;
	}

	#################################################################

	function flickr_photos_archives_bookends_for_user_and_date(&$user, $date, $more=array()){

		$before = flickr_photos_archives_previous_date_for_user($user, $date, $more);
		$after = flickr_photos_archives_next_date_for_user($user, $date, $more);

		return array($before, $after);
	}

	#################################################################

	function flickr_photos_archives_previous_date_for_user(&$user, $date, $more=array()){

		$cluster_id = $user['cluster_id'];

		$enc_user = AddSlashes($user['id']);
		$enc_date = AddSlashes($date);

		$date_col = ($more['context'] == 'posted') ? 'dateupload' : 'datetaken';

		$sql = "SELECT DATE_FORMAT({$date_col}, '%Y-%m-%d') as ymd FROM FlickrPhotos WHERE user_id='{$enc_user}'";
		$sql .= " AND DATE_FORMAT({$date_col}, '%Y-%m-%d') != DATE_FORMAT('{$date}', '%Y-%m-%d')";
		$sql .= " AND `{$date_col}` < '{$enc_date}' ORDER BY {$date_col} DESC LIMIT 1";

		$rsp = db_fetch_users($cluster_id, $sql);

		if ($rsp = db_single($rsp)){
			return $rsp['ymd'];
		}

		return;
	}

	#################################################################

	function flickr_photos_archives_next_date_for_user(&$user, $date, $more=array()){

		$cluster_id = $user['cluster_id'];

		$enc_user = AddSlashes($user['id']);
		$enc_date = AddSlashes($date);

		$date_col = ($more['context'] == 'posted') ? 'dateupload' : 'datetaken';

		$sql = "SELECT DATE_FORMAT({$date_col}, '%Y-%m-%d') as ymd FROM FlickrPhotos WHERE user_id='{$enc_user}'";
		$sql .= " AND DATE_FORMAT({$date_col}, '%Y-%m-%d') != DATE_FORMAT('{$date}', '%Y-%m-%d')";
		$sql .= " AND `{$date_col}` > '{$enc_date}' ORDER BY `{$date_col}` ASC LIMIT 1";

		$rsp = db_fetch_users($cluster_id, $sql);

		if ($rsp = db_single($rsp)){
			return $rsp['ymd'];
		}

		return;
	}

	#################################################################

	function flickr_photos_archives_years_for_user(&$user, $more=array()){

		$cluster_id = $user['cluster_id'];
		$enc_user = AddSlashes($user['id']);

		$date_col = ($more['context'] == 'posted') ? 'dateupload' : 'datetaken';

		$sql = "SELECT DISTINCT(DATE_FORMAT({$date_col}, '%Y')) AS year FROM FlickrPhotos WHERE user_id='{$enc_user}'";

		$rsp = db_fetch_users($cluster_id, $sql);
		$years = array();

		if ($rsp['ok']){

			foreach ($rsp['rows'] as $r){
				$years[] = $r['year'];
			}
		}

		sort($years);
		return $years;
	}

	#################################################################

	function flickr_photos_archives_months_for_user(&$user, $year, $more=array()){

		$cluster_id = $user['cluster_id'];
		$enc_user = AddSlashes($user['id']);

		list($start, $end) = dates_utils_between($year);

		$enc_start = AddSlashes($start);
		$enc_end = AddSlashes($end);

		$date_col = ($more['context'] == 'posted') ? 'dateupload' : 'datetaken';

		$sql = "SELECT DISTINCT(DATE_FORMAT({$date_col}, '%m')) AS month FROM FlickrPhotos WHERE user_id='{$enc_user}'";
		$sql .= " AND `{$date_col}` BETWEEN '{$enc_start}' AND '{$enc_end}'";

		$rsp = db_fetch_users($cluster_id, $sql);
		$months = array();

		if ($rsp['ok']){

			foreach ($rsp['rows'] as $r){
				$months[] = $r['month'];
			}
		}

		sort($months);
		return $months;
	}

	#################################################################

	function flickr_photos_archives_days_for_user(&$user, $year, $month, $more=array()){

		$cluster_id = $user['cluster_id'];
		$enc_user = AddSlashes($user['id']);

		list($start, $end) = dates_utils_between($year, $month);

		$enc_start = AddSlashes($start);
		$enc_end = AddSlashes($end);

		$date_col = ($more['context'] == 'posted') ? 'dateupload' : 'datetaken';

		$sql = "SELECT DISTINCT(DATE_FORMAT({$date_col}, '%d')) AS day FROM FlickrPhotos WHERE user_id='{$enc_user}'";
		$sql .= " AND `{$date_col}` BETWEEN '{$enc_start}' AND '{$enc_end}'";

		$rsp = db_fetch_users($cluster_id, $sql);
		$days = array();

		if ($rsp['ok']){

			foreach ($rsp['rows'] as $r){
				$days[] = $r['day'];
			}
		}

		sort($days);
		return $days;
	}

	#################################################################
?>
