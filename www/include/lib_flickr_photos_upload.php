<?php

	loadlib("flickr_users");
	loadlib("flickr_api");

	loadlib("flickr_photos_import");
	loadlib("filtr");

	#################################################################

	function flickr_photos_upload(&$user, $file, $args=array()){

		# It is assumed that you've called photos_upload_can_upload
		# by the time you get here (20130706/straup)

		$flickr_user = flickr_users_get_by_user_id($user['id']);
		$args['auth_token'] = $flickr_user['auth_token'];

		if (! isset($args['title'])){
			$args['title'] = "Untitled Upload #" . time();
		}

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

			$args['tags'] .= " filtr:process={$args['filtr']}";
		}

		$more = array();

		if (isset($args['http_timeout'])){
			$more['http_timeout'] = $args['http_timeout'];
		}

		# default upload perms?
		$rsp = flickr_api_upload($file, $args, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		if ((isset($args['async'])) && ($args['async'])){
			return $rsp;
		}

		if (! features_is_enabled("uploads_flickr_archive")){
			return $rsp;
		}

		$rsp['archived_ok'] = 0;

		# Archive the photo locally now that we have a photo ID.
		# There are a few things to note about doing this:
		#
		# 0) put this all in a function somewhere so that it can be
		#    called by things that are uploading asynchronously; also
		#    PHP timing out
		#
		# 1) because the only thing the Flickr API returns is a photo ID
		#    we have to first call photos.getInfo and to get the photo
		#    secret and then call [UPDATE ME TO REFLECT REALITY] and store
		#    the original photo.
		#
		# 2) since we'll have photos getInfo we could both write that to
		#    disk and rebuild the SRP (with the relevant extras) and
		#    call the _flickr_photos_import_prepare_photo and the
		#    flickr_photos_add_photo functions to pre-fill the database.
		#
		# 3) something about offline tasks so that we don't have to block
		#    on calls to the Flickr API; also remote filesystems (see
		#    below); something about a poorman's OLT system.

		$photo_id = $rsp['photo_id'];

		$info_args = array(
			'photo_id' => $photo_id,
			'auth_token' => $flickr_user['auth_token'],
		);

		$info_rsp = flickr_api_call('flickr.photos.getInfo', $info_args);

		if ($info_rsp['ok']){

			$photo = $info_rsp['rsp']['photo'];

			$spr = array(
				'id' => $photo_id,
				'owner' =>  $flickr_user['nsid'],
				'secret' =>  $photo['secret'],
				'server' => $photo['server'],
				'farm' => $photo['farm'],
				'title' => $photo['title']['_content'],
				'ispublic' => $photo['visibility']['ispublic'],
				'isfriend' => $photo['visibility']['isfriend'],
				'isfamily' => $photo['visibility']['isfamily'],
				'originalsecret' =>  $photo['originalsecret'],
				'originalformat' => $photo['originalformat'],
				'media' => $photo['media'],
				'dateupload' => $photo['dates']['posted'],
				'datetaken' => $photo['dates']['taken'],
			);

			$tags = array();
	
			# TO DO: escaping...

			foreach ($photo['tags']['tag'] as $t){
				$tags[] = $t['raw'];
			}

			$spr['tags'] = join(" ", $tags);

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

			# TO DO: make functions for all this stuff
			# note: not checking for video-ness

			$root = $GLOBALS['cfg']['flickr_static_path'];
			$dirname = flickr_photos_dirname($photo);

			$orig = flickr_photos_basename($photos, array('size' => 'o'));
			$info = flickr_photos_basename($photos, array('size' => 'i'));

			$orig = $root . $dirname . $orig;
			$info = $root . $dirname . $info;

			# TO DO: merge this in to flickr_photos_import_photo

			$more = array(
				'donot_import_files' => 1
			);

			# see this: we're passing $spr not $photo
			$ph_rsp = flickr_photos_import_photo($spr, $more);

			$rsp['archived_ok'] = $ph_rsp['ok'];

			# $rsp['debug'] = $ph_rsp;
			# $rsp['o'] = $orig;
			# $rsp['i'] = $info;
		}

		# 4) something about non-local (S3) filestores and blocking on
		#    uploads; something about pre-signed upload forms and
		#    callbacks in flickr_photos_upload.php if not using local
		#    FS; something about how that works for upload by email
		#
		# 5) it's time to create lib_storage, lib_storage_fs and
		#    reconcile it all with lib_storage_s3

		$photo = flickr_photos_get_by_id($photo_id);
		$url = flickr_urls_photo_page_flickr($photo);

		$rsp['id'] = $photo_id;
		$rsp['url'] = $url;

		return $rsp;
	}

	#################################################################

	# the end
