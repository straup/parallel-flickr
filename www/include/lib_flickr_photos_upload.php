<?php

	loadlib("flickr_users");
	loadlib("flickr_api");

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
		#    flickr_photos_add_photo functions to pre-fill the database
		#
		# 3) something about non-local (S3) filestores and blocking on
		#    uploads; something about pre-signed upload forms and
		#    callbacks in flickr_photos_upload.php if not using local
		#    FS; something about how that works for upload by email
		#
		# 4) it's time to create lib_storage, lib_storage_fs and
		#    reconcile it all with lib_storage_s3

		return $rsp;
	}

	#################################################################

?>
