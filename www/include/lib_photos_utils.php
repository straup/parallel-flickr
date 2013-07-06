<?php

	#################################################################

	function photos_utils_strperms_to_hash($perms, $is_flickr_api=0){
		return photos_utils_perms_strtohash($perms, $is_flickr_api);
	}

	function photos_utils_perms_strtohash($perms, $is_flickr_api=0){

		$public = ($is_flickr_api) ? "is_public" : "ispublic";
		$friend = ($is_flickr_api) ? "is_friend" : "isfriend";
		$family = ($is_flickr_api) ? "is_family" : "isfamily";

		$hash = array();
		$hash[ $public ] = 0;
		$hash[ $friend ] = 0;
		$hash[ $family ] = 0;

		if ($perms == 'p'){
			$hash[ $public ] = 1;
		}

		else if ($perms == 'fr'){
			$hash[ $friend ] = 1;
		}

		else if ($perms == 'fa'){
			$hash[ $family ] = 1;
		}

		else if ($perms == 'ff'){
			$hash[ $friend ] = 1;
			$hash[ $family ] = 1;
		}

		else {}

		return $hash;
	}

	#################################################################

	function photos_utils_geoperms_strtohash($perms, $is_flickr_api=0){

		$public = ($is_flickr_api) ? "geo_is_public" : "geo_ispublic";
		$contact = ($is_flickr_api) ? "geo_is_contact" : "geo_iscontact";
		$friend = ($is_flickr_api) ? "geo_is_friend" : "geo_isfriend";
		$family = ($is_flickr_api) ? "geo_is_family" : "geo_isfamily";

		$hash = array();
		$hash[ $public ] = 0;
		$hash[ $contact ] = 0;
		$hash[ $friend ] = 0;
		$hash[ $family ] = 0;

		if ($perms == 'p'){
			$hash[ $public ] = 1;
		}

		else if ($perms == 'c'){
			$hash[ $contact ] = 1;
			$hash[ $friend ] = 1;
			$hash[ $family ] = 1;
		}

		else if ($perms == 'fr'){
			$hash[ $friend ] = 1;
		}

		else if ($perms == 'fa'){
			$hash[ $family ] = 1;
		}

		else if ($perms == 'ff'){
			$hash[ $friend ] = 1;
			$hash[ $family ] = 1;
		}

		else {}

		return $hash;
	}

	#################################################################

	function photos_utils_auto_rotate($file, $exif){

		$orientation = $exif['Orientation'];

		$map = array(
			3 => 180,
			6 => -90,
			8 => 90
		);

		if (! isset($map[$orientation])){
			return array('ok' => 0, 'error' => 'Unsupported orientation', 'file' => $file);
		}

		$angle = $map[$orientation];

		$im = imagecreatefromjpeg($file);
		$im = imagerotate($im, $angle, 0);

		imagejpeg($im, $file);

		return array('ok' => 1, 'file' => $file);
	}

	#################################################################

	function photos_utils_read_geo($exif){

		$lat_dms = $exif['GPSLatitude'];
		$lon_dms = $exif['GPSLongitude'];

		if ((! $lat_dms) || (! $lon_dms)){
			return array('ok' => 0);
		}

		$lat_ref = $exif['GPSLatitudeRef'];
		$lon_ref = $exif['GPSLongitudeRef'];

		$lat = geo_utils_exif_gps_to_decimal($lat_dms, $lat_ref);
		$lon = geo_utils_exif_gps_to_decimal($lon_dms, $lon_ref);

		# TO DO: image direction

		return array(
			'ok' => 1,
			'latitude' => $lat,
			'longitude' => $lon,
			'accuracy' => 18,
			'context' => 0,
			'woeid' => 0,
		);
	}

	#################################################################

	# Where $sz is the maximum dimension on either side

	function photos_utils_resize($src, $dest, $sz, $more=array()){

		$type = mime_content_type($src);

		if (! $type){
			return array('ok' => 0, 'error' => 'unable to determine mime-type');
		}

		if (! preg_match("/^image\/(gif|jpeg|png)$/", $type, $m)){
			return array('ok' => 0, 'error' => 'invalid or unsupported image');
		}

		$ext = $m[1];
		$func = "imagecreatefrom{$ext}";

		$im = call_user_func($func, $src);

		if (! $im){
			return array('ok' => 0, 'error' => 'unable to determine mime-type');
		}

		list($w, $h, $type, $attr) = getimagesize($src);

		if ($w > $h){
			$ratio = $sz / $w;
			$width = $sz;
			$height = $h * $ratio;
		}

		else {
			$ratio = $sz / $h;
			$height = $sz;
			$width = $w * $ratio;
		}

		# dumper("W,H: {$w},{$h} W,H:{$width},{$height}");

		$resized = imagecreatetruecolor($width, $height);

		imagecopyresampled($resized, $im, 0, 0, 0, 0, $width, $height, $w, $h);

		imagejpeg($resized, $dest);

		return array(
			'ok' => 1,
		);
	}

	#################################################################

	# the end
