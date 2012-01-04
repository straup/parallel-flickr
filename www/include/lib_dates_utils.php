<?php

	#################################################################

	function dates_utils_months(){

		$map = array();

		foreach (range(1, 12) as $i){
			$month = sprintf("%02d", $i);
			$timestamp = mktime(0, 0, 0, $month);
			$map[$month] = date("F", $timestamp);
		}

		return $map;
	}

	#################################################################

	function dates_utils_between($year, $month=null, $day=null){

		if (($month) && ($day)){
			$start = "{$year}-{$month}-{$day} 00:00:00";
			$end = "{$year}-{$month}-{$day} 23:59:59";
		}

		else if ($month){

			# see also: http://php.net/manual/en/function.cal-days-in-month.php

			$month = sprintf("%02d", $month);

			$ts = mktime(0, 0, 0, $month + 1, 1, $year);
			$last_dom = sprintf("%02d", date("d", $ts -1));

			$start = "{$year}-{$month}-01 00:00:00";
			$end = "{$year}-{$month}-{$last_dom} 23:59:59";
		}

		else {
			$start = "{$year}-01-01 00:00:00";
			$end = "{$year}-12-31 23:59:59";
		}

		return array($start, $end);
	}

	#################################################################
?>
