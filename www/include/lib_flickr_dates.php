<?php

	#################################################################

	function flickr_dates_verbosify_iso8601($str){

		$ts = strtotime($str);

		# See this? No TZ wrangling...

		$day = date("l F d Y", $ts);
		$hour = date("H", $ts);

		if (($hour >= 0) && ($hour < 6)){
			$when = "after midnight";
		}

		elseif (($hour >= 6) && ($hour < 8)){
			$when = "in the wee small hours of the morning";
		}

		elseif (($hour >= 8) && ($hour < 12)){
			$when = "in the morning";
		}

		elseif (($hour >= 12) && ($hour < 14)){
			$when = "around noon";
		}

		elseif (($hour >= 14) && ($hour < 18)){
			$when = "in the afternoon";
		}

		elseif (($hour >= 18) && ($hour < 20)){
			$when = "in the evening";
		}

		else {
			$when = "at night";
		}

		return "{$day}, {$when}";
	}

	#################################################################

?>
