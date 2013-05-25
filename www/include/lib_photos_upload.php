<?php

	loadlib("flickr_users");
	loadlib("flickr_api");

	loadlib("flickr_photos_upload");
	loadlib("flickr_photos_import");
	loadlib("filtr");

	loadlib("photos_resize");

	#################################################################

	# Please rename me...

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

		# TO DO: filtr stuff...

		# TO DO: metadata extract

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

			else if ($p == 'fa'){
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

		$orig = "{$photo_id}_{$secret_orig}_o.{$format_orig}";
		$info = "{$photo_id}_{$secret_orig}_i.json";

		$root = $GLOBALS['cfg']['flickr_static_path'] . flickr_photos_id_to_path($photo_id) . "/";

		if (! file_exists($root)){
			mkdir($root, 0755, true);
		}

		$orig = $root . $orig;
		$info = $root . $info;

		# dumper(array($orig, $info));

		# Write the files to disk

		# TO DO: clean up if other stuff fails (?)

		if (! copy($file, $orig)){
			return array('ok' => 0, 'error' => "Failed to copy {$file} to {$orig}");
		}

		# Resize – put me in a function or something...

		# TO DO: make sure the photo isn't smaller that the
		# stuff listed in $resize

		$resize = array(
			640 => 'z',
		);

		foreach ($resize as $sz => $ext){

			$small_path = "{$photo_id}_{$secret}";

			if ($ext){
				$small_path .= "_{$ext}";
			}

			$small_path .= ".jpg";

			$small_path = $root . $small_path;

			$rsp = photos_resize($orig, $small_path, $sz);
		}

		# write the JSON

		$fh = fopen($info, 'w');
		fwrite($fh, json_encode($spr));
		fclose($fh);

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
		# original photo (20130523/straup)

		$rsp = photos_resize($tiny, $small, 300);

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

	# the end
