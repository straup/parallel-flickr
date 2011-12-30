<?php

	#
	# $Id$
	#

	define("GEO_UTILS_R_M", 3963.1676);
	define("GEO_UTILS_R_KM", 6378.1);
	define("GEO_UTILS_KM_PER_M", (GEO_UTILS_R_M / GEO_UTILS_R_KM));

	#################################################################

	function geo_utils_prepare_coordinate($coord, $collapse=1){

		$coord = geo_utils_trim_coordinate($coord);

		if ($collapse){
			$coord = geo_utils_collapse_coordinate($coord);
		}

		return $coord;
	}

	#################################################################

	function geo_utils_expand_coordinate($coord, $multiplier=1000000){

		return $coord / $multiplier;
	}

	#################################################################

	function geo_utils_collapse_coordinate($coord, $multiplier=1000000){

		return $coord * $multiplier;
	}

	#################################################################

	function geo_utils_trim_coordinate($coord, $offset=6){

		$fmt = "%0{$offset}f";

		return sprintf($fmt, $coord);
	}

	#################################################################

	function geo_utils_is_valid_latitude($lat){

		if (! is_numeric($lat)){
			return 0;
		}

		$lat = floatval($lat);

		if (($lat < -90.) || ($lat > 90.)){
			return 0;
		}

		return 1;
	}

	#################################################################

	function geo_utils_is_valid_longitude($lon){

		if (! is_numeric($lon)){
			return 0;
		}

		$lon = floatval($lon);

		if (($lon < -180.) || ($lont > 180.)){
			return 0;
		}

		return 1;
	}

	#################################################################

	function geo_utils_distance($lat1, $lon1, $lat2, $lon2, $unit='m'){

		$theta = $lon1 - $lon2; 
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
		$dist = acos($dist); 
		$dist = rad2deg($dist);

		$miles = $dist * 60 * 1.1515;

		return ($unit == 'm') ? $miles : $miles * 1.609344; 
	}

	#################################################################

	function geo_utils_bbox_from_point($lat, $lon, $dist, $unit='m'){

		$sw = geo_utils_move_point($lat, $lon, 225, $dist, $unit);
		$ne = geo_utils_move_point($lat, $lon, 45, $dist, $unit);

		return array_merge($sw, $ne);
	}

	#################################################################

	# http://www.richardpeacock.com/sites/default/files/getDueCoords.php__0.txt

	function geo_utils_move_point($lat, $lon, $bearing, $dist, $unit='m'){

		$radius = GEO_UTILS_R_M;

		if ($unit == 'km'){
			$radius = GEO_UTILS_R_KM;
			$dist = $dist * GEO_UTILS_KM_PER_M;
		}

		$new_lat = rad2deg(asin(sin(deg2rad($lat)) * cos($dist / $radius) + cos(deg2rad($lat)) * sin($dist / $radius) * cos(deg2rad($bearing))));
	      	
		$new_lon = rad2deg(deg2rad($lon) + atan2(sin(deg2rad($bearing)) * sin($dist / $radius) * cos(deg2rad($lat)), cos($dist / $radius) - sin(deg2rad($lat)) * sin(deg2rad($new_lat))));

		return array($new_lat, $new_lon);
	}

	#################################################################

?>
