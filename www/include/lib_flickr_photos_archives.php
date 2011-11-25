<?php

	loadlib("flickr_photos_search");

	#################################################################

	function flickr_photos_archives_timepies_for_user(&$user, $facet, $start, $end, $gap, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
			'mincount' => 0,
		);

		$more = array_merge($defaults, $more);

		$query = array(
			'photo_owner' => $user['id'],
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
?>
