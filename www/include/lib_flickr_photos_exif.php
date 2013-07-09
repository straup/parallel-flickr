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

	# OMG - please cache me (20130629/straup)

	function flickr_photos_exif_read(&$photo){

		$map = flickr_photos_media_map();

		if ($map[$photo['media']] == 'video'){
			return array('ok' => 0, 'error' => 'video does not contain EXIF data');
		}

		$path = flickr_photos_path($photo, array('size' => 'o'));

		if (! preg_match("/\.jpe?g$/i", $path)){
			return array('ok' => 0, 'error' => 'not a JPEG photo');
		}

		if (! storage_file_exists($path, array('boolean' => 1))){
			return array('ok' => 0, 'error' => 'original photo not found');
		}

		# This is absolutely not awesome but it's what required to 
		# support the S3 stuff... (20130629/straup)

		$rsp = storage_get_file($path);

		if (! $rsp){
			return $rsp;
		}

		try {
			$tmpdir = sys_get_temp_dir();
			$tmpfile = tempnam($tmpdir, "exif-{$photo['id']}");

			$fh = fopen($tmpfile, 'w');
			fwrite($fh, stream_get_contents($rsp['fh']));
			fclose($fh);

			$exif = exif_read_data($tmpfile);
			unlink($tmpfile);
		}

		catch (Exception $e){
			return array('ok' => 0, 'error' => $e);
		}

		# abs_path is temporary (see above)
		# $path = flickr_photos_path($photo, array('size' => 'o', 'abs_path' => 1));
		# $exif = exif_read_data($path);

		if (! $exif){
			return array('ok' => 0, 'error' => 'failed to read EXIF data');
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

		return array('ok' => 1, 'rows' => $exif);
	}

	#################################################################

	# the end
