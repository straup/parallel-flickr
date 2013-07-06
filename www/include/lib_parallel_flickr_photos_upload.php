<?php

	loadlib("dbtickets_flickr");
	loadlib("random");

	loadlib("flickr_users");
	loadlib("flickr_api");

	loadlib("flickr_photos_upload");
	loadlib("flickr_photos_import");
	loadlib("filtr");

	loadlib("photos_utils");
	loadlib("storage");

	loadlib("exif");
	loadlib("geo_utils");

	#################################################################

	function parallel_flickr_photos_upload(&$user, $file, $args=array()){

		$ticket_user = $user;

		if ($id = $GLOBALS['cfg']['dbtickets_flickr_user_id']){
			$ticket_user = users_get_by_id($id);
		}

		$rsp = dbtickets_flickr_create($ticket_user);

		if (! $rsp['ok']){
			return $rsp;
		}

 		$flickr_user = flickr_users_get_by_user_id($user['id']);

		$photo_id = $rsp['id'];

		if (! isset($args['title'])){
			$args['title'] = "Untitled Upload #" . time();
		}

		# metadata

		$rsp = exif_read($file);
		$exif = ($rsp['ok']) ? $rsp['data'] : null;

		$geo = null;

		if ($exif){

			$rsp = photos_utils_auto_rotate($file, $exif);
			$file = $rsp['file'];

			$rsp = photos_utils_read_geo($exif);

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

		if ((! $format_orig) && ($exif)){
			$format_orig = "jpg";
		}

		if ((! $format_org) && (preg_match("/\.(\w+)$/", $file, $m))){
			$format_orig = strtolower($m[1]);
		}

		$media = 'photo';

		$now = time();
		$fmt = "Y-m-d H:i:s";

		$upload = $now;

		# TO DO: this is serioulsy weird in iphone photos and has not
		# been addressed yet (20130526/straup)

		$taken = (($exif) && (isset($exif['DateTimeOriginal']))) ? $exif['DateTimeOriginal'] : gmdate($fmt, $now);

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

		if ($perms = $args['perms']){
			$perms_hash = photos_utils_perms_strtohash($perms);
			$spr = array_merge($spr, $perms_hash);
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
				$perms_hash = photos_utils_geoperms_strtohash($g, 'flickr api');
				$spr = array_merge($spr, $perms_hash);
			}
		}

		# dumper($spr);
		# return array('ok' => 0);

		# TO DO: make functions for all this stuff
		# note: not checking for video-ness

		$mock_photo = array(
			'id' => $photo_id,
			'user_id' => $user['id'],
			'secret' => $secret,
			'originalsecret' => $secret_orig,
			'originalformat' => $format_orig,
		);

		$dirname = flickr_photos_dirname($mock_photo);

		$orig = flickr_photos_basename($mock_photo, array('size' => 'o'));
		$info = flickr_photos_basename($mock_photo, array('size' => 'i'));

		$orig = $dirname . $orig;
		$info = $dirname . $info;

		$bytes = storage_utils_path_to_bytes($file);
		$rsp = storage_put_file($orig, $bytes);

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
			1600 => 'h',
		);

		foreach ($resize as $dim => $sz){

			# TO DO: make sure the photo isn't smaller that the
			# stuff listed in $resize

			$small_basename = flickr_photos_basename($mock_photo, array('size' => $sz));
			$small_path = $dirname . $small_basename;

			$resized = sys_get_temp_dir() . "/" . $small_basename;

			$rsp = photos_utils_resize($file, $resized, $dim);

			if (! $rsp['ok']){
				continue;
			}

			$bytes = storage_utils_path_to_bytes($resized);
			$rsp = storage_put_file($small_path, $bytes);

			unlink($resized);

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

		# Please put all this stuff in a function or something...
		# (20130706/straup)

		$notify_flickr = ((is_array($args['notify'])) && (in_array('flickr', $args['notify'])) && (features_is_enabled("uploads_flickr_notifications"))) ? 1 : 0;

		if ($notify_flickr){

			$photo = flickr_photos_get_by_id($photo_id);
			$photo_url = flickr_urls_photo_page($photo);
			$desc = "<a href=\"{$photo_url}\">See also:</a>";

			$fl_args = array(
				'title' => "Untitled Pointer #{$photo_id}",
			);

			if ($desc){
				$fl_args['description'] = $desc;
			}

			foreach (array('ispublic', 'isfriend', 'isfamily') as $key){

				if (array_key_exists($key, $spr)){
					# sigh... yes (see above)
					$fl_key = str_replace("is", "is_", $key);
					$fl_args[$fl_key] = $spr[$key];
				}
			}

			$fl_rsp = parallel_flickr_photos_upload_tell_flickr($user, $file, $fl_args);
			$rsp['flickr'] = $fl_rsp;
		}

		$photo = flickr_photos_get_by_id($photo_id);
		$url = flickr_urls_photo_page($photo);

		$rsp['id'] = $photo_id;
		$rsp['url'] = $url;

		return $rsp;
	}

	#################################################################

	# TO DO: ensure filtr
	# TO DO: error handling

	function parallel_flickr_photos_upload_tell_flickr(&$user, $file, $args=array()){

		$tiny = tempnam(sys_get_temp_dir(), 'preview-t');
		$small = tempnam(sys_get_temp_dir(), 'preview-s');

		$rsp = photos_utils_resize($file, $tiny, 20);

		if (! $rsp['ok']){
			return $rsp;
		}

		# < 300px seems to cause pxl to give and return the
		# original photo but that's the spice of life right
		# (20130525/straup)

		$sz = ($args['size']) ? $args['size'] : rand(240, 320);

		$rsp = photos_utils_resize($tiny, $small, $sz);

		if (! $rsp['ok']){
			return $rsp;
		}

		$rsp = filtr('pxl', array($small));

		if (! $rsp['ok']){
			return $rsp;
		}

		$args['tags'] = '"zoom and enhance"';
		$preview = $rsp['path'];

		return flickr_photos_upload($user, $preview, $args);
	}

	#################################################################

	function parallel_flickr_photos_upload_tell_twitter(){
		# Please write me...
	}

	#################################################################

	# the end	
