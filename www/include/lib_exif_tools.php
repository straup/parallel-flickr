<?php

	#################################################################

	function exif_tools_scrub_string($str){

		return sanitize(trim($str), 'str');
	}

	#################################################################

	function exif_tools_rational2float($value){

		$parts = explode('/', $value);
		$count = count($parts);

		if (! $count){
			$ret = 0;
		}

		else if ($count == 1){
			$ret = $parts[0];
		}

		else {
			$ret = ($parts[1]) ? floatval($parts[0]) / floatval($parts[1]) : $parts[0];	
		}

		return floatval($ret);
	}

	#################################################################


	function exif_tools_explode_gps_altitude($altitude, $ref=null){

		$altitude = exif_tools_rational2float($altitude);

		# 1 = Below Sea Level

		if ($ref == 1){
			$altitude = - $altitude;
		}

		return $altitude;
	}

	#################################################################

	function exif_tools_explode_gps_img_direction($direction, $ref=null){

		$direction = exif_tools_rational2float($direction);

		if ($ref == 'M'){
			# uh...
		}

		return $direction;
	}

	#################################################################

?>
