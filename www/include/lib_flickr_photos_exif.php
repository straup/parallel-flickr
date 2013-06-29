<?php

	#################################################################

	loadlib("flickr_photos");
	loadlib("exif_tools");
	
	#################################################################

	function flickr_photos_exif_has_exif(&$photo, $more=array()){

		if ((isset($photo['hasexif'])) && (! isset($more['force']))){
			return $photo['hasexif'];
		}
 
		$rsp = flickr_photos_exif_read($photo);
		return $rsp['ok'];
	}

	#################################################################

	# TO DO: account for things stored on S3 (20130629/straup)

	function flickr_photos_exif_read(&$photo){

		$map = flickr_photos_media_map();

		if ($map[$photo['media']] == 'video'){
			return not_okay("video does not contain EXIF data");
		}

		$path = flickr_photos_path($photo, array('size' => 'o'));

		if (! preg_match("/\.jpe?g$/i", $path)){
			return not_okay("not a JPEG photo");
		}

		if (! storage_file_exists($path, array('boolean' => 1))){
			return not_okay("original photo not found");
		}

		# TO DO: make this work with the S3 stuff (20130629/straup)
		# can this read things with URI schemes ?

		# abs_path is temporary (see above)
		$path = flickr_photos_path($photo, array('size' => 'o', 'abs_path' => 1));

		$exif = exif_read_data($path);

		if (! $exif){
			return not_okay("failed to read EXIF data");
		}

		# TO DO: expand EXIF tag values

		$to_simplejoin = array(
			'SubjectLocation',
			'GPSLatitude',
			'GPSLongitude',
			'GPSTimeStamp',
		);

		foreach ($to_simplejoin as $tag){

			if (is_array($exif[$tag])){
				$exif[$tag] = implode(",", $exif[$tag]);
			}
		}

		# TO DO: work out how/where individual EXIF tags get
		# "prettified" ...

		ksort($exif);

		return okay(array("rows" => $exif));
	}

	#################################################################

	# the end
