<?php

	loadlib("dbtickets_flickr");
	loadlib("random");

	loadlib("flickr_users");
	loadlib("flickr_api");

	loadlib("flickr_photos_upload");
	loadlib("flickr_photos_import");
	loadlib("filtr");

	loadlib("photos_resize");

	loadlib("storage");
	loadlib("storage_storagemaster");

	loadlib("exif");
	loadlib("geo_utils");

	#################################################################

	function photos_upload(&$user, $file, $args=array()){

 		$flickr_user = flickr_users_get_by_user_id($user['id']);

		$rsp = dbtickets_flickr_create();

		if (! $rsp['ok']){
			return $rsp;
		}

		$photo_id = $rsp['id'];

		if (! isset($args['title'])){
			$args['title'] = "Untitled Upload #" . time();
		}

		# metadata

		$rsp = exif_read($file);
		$exif = ($rsp['ok']) ? $rsp['data'] : null;

		$geo = null;

		if ($exif){

			$rsp = photos_upload_auto_rotate($file, $exif);
			$file = $rsp['file'];

			$rsp = photos_upload_geo($exif);

			if ($rsp['ok']){
				$geo = $rsp;
			}
		}

		# Filtr ?

		$do_filtr = 0;

		if ((features_is_enabled("uploads_filtr")) && (isset($args['filtr']))){
			$do_filtr = filtr_is_valid_filtr($args['filtr']);
		}

		if ($do_filtr){

			$rsp = filtr($args['filtr'], array($file));

			if (! $rsp['ok']){
				return $rsp;
			}

			rename($rsp['path'], $file);
		}

		# Start storing files and shit

		$server = 0;
		$farm = 0;

		$secret = random_string(10);
		$secret_orig = random_string(10);

		$info = pathinfo($file);
		$format_orig = strtolower($info['extension']);

		$media = 'photo';

		$now = time();
		$fmt = "Y-m-d H:i:s";

		$upload = $now;
		$taken = ($exif) ? $exif['DateTimeOriginal'] : gmdate($fmt, $now);

		$spr = array(
			'id' => $photo_id,
			'owner' =>  $flickr_user['nsid'],
			'secret' =>  $secret,
			'server' => $server,
			'farm' => $farm,
			'title' => $args['title'],

			# See below

			'ispublic' => 0,	
			'isfriend' => 0,
			'isfamily' => 0,

			'originalsecret' =>  $secret_orig,
			'originalformat' => $format_orig,
			'media' => $media,
			'dateupload' => $upload,
			'datetaken' => $taken,
		);

		# This is not awesome but it will do for now...

		if ($p = $args['perms']){
		
			if ($p == 'p'){
				$spr['ispublic'] = 1;
			}

			else if ($p == 'fr'){
				$spr['isfriend'] = 1;
			}

			else if ($p == 'fa'){
				$spr['isfamily'] = 1;
			}

			else if ($p == 'ff'){
				$spr['isfriend'] = 1;
				$spr['isfamily'] = 1;
			}

			else {}
		}

		# $tags = array();
		# $spr['tags'] = join(" ", $tags);

		# See above inre: EXIF data...

		if ($geo){
			$spr['latitude'] = $geo['latitude'];
			$spr['longitude'] = $geo['longitude'];
			$spr['accuracy'] = $geo['accuracy'];
			$spr['context'] = $geo['context'];
			$spr['woeid'] = $geo['woeid'];
			$spr['geo_is_public'] = 0;
			$spr['geo_is_contact'] = 0;
			$spr['geo_is_friend'] = 0;
			$spr['geo_is_family'] = 0;

			if ($g = $args['geoperms']){

				if ($g == 'p'){
					$spr['geo_is_public'] = 1;
				}

				else if ($g == 'c'){
					$spr['geo_is_contact'] = 1;
					$spr['geo_is_friend'] = 1;
					$spr['geo_is_family'] = 1;
				}

				else if ($g == 'fr'){
					$spr['geo_is_friend'] = 1;
				}

				else if ($g == 'fa'){
					$spr['geo_is_family'] = 1;
				}

				else if ($g == 'ff'){
					$spr['geo_is_friend'] = 1;
					$spr['geo_is_family'] = 1;
				}

				else {}
			}
		}

		# TO DO: geoperms (see above)

		# dumper($spr);
		# return array('ok' => 0);

		# TO DO: make functions for all this stuff
		# note: not checking for video-ness

		$root = flickr_photos_id_to_path($photo_id) . "/";

		$orig = "{$photo_id}_{$secret_orig}_o.{$format_orig}";
		$info = "{$photo_id}_{$secret_orig}_i.json";

		$orig = $root . $orig;
		$info = $root . $info;

		$bytes = photos_upload_path_to_bytes($file);

		$rsp = storage_put_file($orig, $bytes);
		# dumper($rsp);

		if (! $rsp['ok']){
			return $rsp;
		}

		# Resize – put me in a function or something or more
		# likely a whole other image daemon service...

		$resize = array(
			# 75 => 'sq',
			# 150 => 'q',
			100 => 't',
			# 240 => 's',
			# 320 => 'n',
			# 500 => '',
			640 => 'z',
			800 => 'c',
			# 1024 => 'l',
			# 1600 => 'h',
		);

		foreach ($resize as $sz => $ext){

			# TO DO: make sure the photo isn't smaller that the
			# stuff listed in $resize

			$small_fname = "{$photo_id}_{$secret}";

			if ($ext){
				$small_fname .= "_{$ext}";
			}

			$small_fname .= ".jpg";
			$small_path = $root . $small_fname;

			$resized = sys_get_temp_dir() . "/" . $small_fname;

			$rsp = photos_resize($file, $resized, $sz);

			if (! $rsp['ok']){
				continue;
			}

			$bytes = photos_upload_path_to_bytes($resized);
			unlink($resized);

			$rsp = storage_put_file($small_path, $bytes);

			if (! $rsp['ok']){
				return $rsp;
			}
		}

		# write the JSON

		$spr_json = json_encode($spr);
		$rsp = storage_put_file($info, $spr_json);

		# Add to the database

		$more = array(
			'donot_import_files' => 1
		);

		$ph_rsp = flickr_photos_import_photo($spr, $more);
		$rsp['archived_ok'] = $ph_rsp['ok'];

		# Preview

		if ($args['preview']){

			$fl_args = array(
				'title' => "Untitled Pointer #{$photo_id}",
			);

			foreach (array('ispublic', 'isfriend', 'isfamily') as $key){

				if (array_key_exists($key, $spr)){
					# sigh... yes (see above)
					$fl_key = str_replace("is", "is_", $key);
					$fl_args[$fl_key] = $spr[$key];
				}
			}

			$fl_rsp = photos_upload_flickr_preview($user, $file, $fl_args);
			$rsp['flickr'] = $fl_rsp;
		}

		return $rsp;
	}

	#################################################################

	# TO DO: ensure filtr
	# TO DO: error handling
	# TO DO: a better name

	function photos_upload_flickr_preview(&$user, $file, $args=array()){

		$tiny = tempnam(sys_get_temp_dir(), 'preview-t');
		$small = tempnam(sys_get_temp_dir(), 'preview-s');

		$rsp = photos_resize($file, $tiny, 20);

		if (! $rsp['ok']){
			return $rsp;
		}

		# < 300px seems to cause pxl to give and return the
		# original photo but that's the spice of life right
		# (20130525/straup)

		$sz = ($args['size']) ? $args['size'] : rand(240, 320);

		$rsp = photos_resize($tiny, $small, $sz);

		if (! $rsp['ok']){
			return $rsp;
		}

		$rsp = filtr('pxl', array($small));

		if (! $rsp['ok']){
			return $rsp;
		}

		$preview = $rsp['path'];

		return flickr_photos_upload($user, $preview, $args);
	}

	#################################################################

	function photos_upload_auto_rotate($file, $exif){

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

	# please rename me...

	function photos_upload_geo($exif){

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

		# TO DO: reverse geocoding...

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

	function photos_upload_path_to_bytes($path){

		$size = filesize($path);
		$fh = fopen($path, "rb");
		$bytes = fread($fh, $size);
		fclose($fh);
		return $bytes;
	}

	#################################################################

	# the end
