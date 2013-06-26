<?php

	#################################################################

	function exif_read($path){

		$type = mime_content_type($path);

		if ($type != 'image/jpeg'){
			return array('ok' => 0, "error" => "not a JPEG file");
		}

		$exif = exif_read_data($path);

		if (! $exif){
			return array('ok' => 0, "error" => "failed to read EXIF data");
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

		return array(
			"ok" => 1,
			"data" => $exif,
		);

	}

	#################################################################

	# the end
