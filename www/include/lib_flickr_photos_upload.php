<?php

	loadlib("flickr_users");
	loadlib("flickr_api");

	loadlib("flickr_photos");
	loadlib("flickr_photos_import");

	#################################################################

	function flickr_photos_upload(&$user, $file, $args=array()){

		$flickr_user = flickr_users_get_by_user_id($user['id']);
		$flickr_perms = $flickr_user['token_perms'];

		$perms_map = flickr_api_authtoken_perms_map();

		if ($perms_map[$flickr_perms] != 'write'){
			return not_okay("insufficient perms");
		}

		$args['auth_token'] = $flickr_user['auth_token'];

		# default upload perms?

		$rsp = flickr_api_upload($file, $args);

		if (! $rsp['ok']){
			return $rsp;
		}

		if ((isset($args['async'])) && ($args['async'])){
			return $rsp;
		}

		# TO DO: archive the photo locally now that we have a photo ID

		# There are a few things to note about doing this:
		#
		# 1) because the only thing the Flickr API returns is a photo ID
		#    we have to first call photos.getInfo and to get the photo
		#    secret and then call flickr_photos_id_to_path and store the
		#    original photo.
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
				'tags' => join(" ", $photo['tags']['tag']),
				'media' => $photo['media'],
				# "media_status": "ready",
				'dateupload' => $photo['dates']['posted'],
				'datetaken' => $photo['dates']['taken'],
				# "datetakengranularity": 0,
			);
	
			$hasgeo = (isset($photo['location'])) ? 1 : 0;

			$spr['latitude'] = ($hasgeo) ? $photo['location']['latitude'] : 0;
			$spr['longitude'] =  ($hasgeo) ? $photo['location']['longitude'] : 0;
			$spr['accuracy'] =  ($hasgeo) ? $photo['location']['accuracy'] : 0;
			$spr['context'] =  ($hasgeo) ? $photo['location']['context'] : 0;
			$spr['woeid'] =  ($hasgeo) ? $photo['location']['woeid'] : 0;
			$spr['geo_is_public'] = ($hasgeo) ? $photo['geoperms']['ispublic'] : 0;
			$spr['geo_is_contact'] = ($hasgeo) ? $photo['geoperms']['iscontact'] : 0;
			$spr['geo_is_friend'] = ($hasgeo) ? $photo['geoperms']['isfriend'] : 0;
			$spr['geo_is_family'] = ($hasgeo) ? $photo['geoperms']['isfamily'] : 0;

			# See all of this stuff? It's a hodge-podge of functions pulled
			# from the import/backup code. This should be cleaned up where
			# possible even if that just means making "private" functions
			# public and x_wrapper_my_wrapper-ing others (20120210/straup)

			$photo = _flickr_photos_import_prepare_photo($user, $spr);

			# note: not checking for video-ness

			$orig = "{$photo['originalsecret']}_{$photo['id']}_o.{$photo['originalformat']}";
			$info = "{$photo['originalsecret']}_{$photo['id']}_i.json";

			$root = $GLOBALS['cfg']['flickr_static_path'] . flickr_photos_id_to_path($photo['id']);

			# oh yeah, right... the www server will need to be able
			# to write to the static files directory....grrrnnnn

			if (! file_exists($root)){
				# mkdir($root, 0755, true);
			}

			$orig = $root . $orig;
			$info = $root . $info;

			# $rsp['photo'] = $photo;
			# $rsp['o'] = $orig;
			# $rsp['i'] = $info;

			# add the photo to the database
			# flickr_photos_add_photo($photo);
			# flickr_photos_lookup_add($photo['id'], $photo['user_id']);

			# copy the original into place; note the lack of a thumbnail
			# copy(file, $orig);

			# copy the basic metadata into place
			# $json = json_encode($info_rsp);
			# _flickr_photos_import_store($info, $json);
		}

		# 4) something about non-local (S3) filestores and blocking on
		#    uploads; something about pre-signed upload forms and
		#    callbacks in flickr_photos_upload.php if not using local
		#    FS; something about how that works for upload by email
		#
		# 5) it's time to create lib_storage, lib_storage_fs and
		#    reconcile it all with lib_storage_s3

		return $rsp;
	}

	#################################################################

?>
