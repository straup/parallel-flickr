<?php

	loadlib("flickr_users");
	loadlib("flickr_api");

	loadlib("flickr_photos_upload");
	loadlib("flickr_photos_import");
	loadlib("filtr");

	loadlib("photos_resize");

	loadlib("storage");
	loadlib("storage_storagemaster");

	#################################################################

	function photos_upload(&$user, $file, $args=array()){

		loadlib("dbtickets_flickr");
		loadlib("random");

 		$flickr_user = flickr_users_get_by_user_id($user['id']);

		$rsp = dbtickets_flickr_create();

		if (! $rsp['ok']){
			return $rsp;
		}

		$photo_id = $rsp['id'];

		if (! isset($args['title'])){
			$args['title'] = "Untitled Upload #" . time();
		}

		# TO DO: metadata extract

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

			if (features_is_enabled("uploads_shoutout")){
				$args['tags'] .= " filtr:process={$args['filtr']}";
			}

		}

		$server = 0;
		$farm = 0;

		$secret = random_string(10);
		$secret_orig = random_string(10);

		$format_orig = 'jpg';	# FIX ME...
		$media = 'photo';

		$now = time();
		$fmt = "Y-m-d H:i:s";

		$upload = $now;
		$taken = gmdate($fmt, $now);	# read from exif or something

		$spr = array(
			'id' => $photo_id,
			'owner' =>  $flickr_user['nsid'],
			'secret' =>  $secret,
			'server' => $server,
			'farm' => $farm,
			'title' => $args['title'],

			# TO DO: fix these and the others... oh god...
			# do I have to build a permissions system?
			# (20130520/straup)

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
				$spr['isfriend'] = 0;
				$spr['isfamily'] = 0;
			}

			else if ($p == 'fr'){
				$spr['ispublic'] = 0;
				$spr['isfriend'] = 1;
				$spr['isfamily'] = 0;
			}

			else if ($p == 'fa'){
				$spr['ispublic'] = 0;
				$spr['isfriend'] = 0;
				$spr['isfamily'] = 1;
			}

			else if ($p == 'ff'){
				$spr['ispublic'] = 0;
				$spr['isfriend'] = 1;
				$spr['isfamily'] = 1;
			}

			else {}
		}

		# $tags = array();
		# $spr['tags'] = join(" ", $tags);

		# See above inre: EXIF data...

		/*
		$hasgeo = (isset($photo['location'])) ? 1 : 0;

		if ($hasgeo){
			$spr['latitude'] = $photo['location']['latitude'];
			$spr['longitude'] = $photo['location']['longitude'];
			$spr['accuracy'] = $photo['location']['accuracy'];
			$spr['context'] = $photo['location']['context'];
			$spr['woeid'] = $photo['location']['woeid'];
			$spr['geo_is_public'] = $photo['geoperms']['ispublic'];
			$spr['geo_is_contact'] = $photo['geoperms']['iscontact'];
			$spr['geo_is_friend'] = $photo['geoperms']['isfriend'];
			$spr['geo_is_family'] = $photo['geoperms']['isfamily'];
		}
		*/

		# dumper($spr);

		# TO DO: make functions for all this stuff
		# note: not checking for video-ness

		$root = flickr_photos_id_to_path($photo_id) . "/";

		$orig = "{$photo_id}_{$secret_orig}_o.{$format_orig}";
		$info = "{$photo_id}_{$secret_orig}_i.json";

		$orig = $root . $orig;
		$info = $root . $info;

		# dumper(array($orig, $info));

		$bytes = photos_upload_path_to_bytes($file);

		$rsp = storage_put_file($orig, $bytes);
		# dumper($rsp);

		if (! $rsp['ok']){
			return $rsp;
		}

		# Resize – put me in a function or something or more
		# likely a whole other image daemon service...

		$resize = array(
			100 => 't',
			640 => 'z',
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

		# TO DO: all the stuff that's commented out in the actual
		# upload to flickr code... (20130520/straup)

		$more = array(
			'donot_import_files' => 1
		);

		# see this: we're passing $spr not $photo
		$ph_rsp = flickr_photos_import_photo($spr, $more);

		# dumper($ph_rsp);

		$rsp['archived_ok'] = $ph_rsp['ok'];

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

	function photos_upload_path_to_bytes($path){

		$size = filesize($path);
		$fh = fopen($path, "rb");
		$bytes = fread($fh, $size);
		fclose($fh);
		return $bytes;
	}

	#################################################################

	# the end
