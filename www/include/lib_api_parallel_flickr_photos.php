<?php

	loadlib("photos_upload");
	loadlib("flickr_photos_upload");
	loadlib("api_parallel_flickr_utils");

	#################################################################

	function api_parallel_flickr_photos_getList(){

		$owner = $GLOBALS['cfg']['user'];

		$args = array();
		api_utils_ensure_pagination_args($args);

		$rsp = flickr_photos_for_user($owner, $args);
		$photos = array();

		foreach ($rsp['rows'] as $row){
			$photos[] = api_parallel_flickr_utils_photo2spr($row);
		}

		$out = array(
			'photos' => $photos,
		);

		api_utils_ensure_pagination_results($out, $rsp['pagination']);
		api_output_ok($out);
	}

	#################################################################

	function api_parallel_flickr_photos_upload(){

		if (! $GLOBALS['cfg']['enable_feature_uploads']){
			api_output_error(999, "uploads are disabled");
		}

		if (! $_FILES['photo']){
			api_output_error(999, "missing photo");
		}

		if ($_FILES['photo']['error']){
			api_output_error(999, "server error: {$_FILES['photo']['error']}");
		}

		# TO DO: check Flickr auth token permissions here (again)

		# TO DO: check $dest (below) and ensure that the relevant
                # feature flags are enabled (20130630/straup)

		$file = $_FILES['photo']['tmp_name'];

		$args = array();

		# FIX ME: pull in title, etc.

		$args['perms'] = post_str("perms");
		$args['geoperms'] = post_str("geoperms");
		$args['filtr'] = post_str("filtr");

		$dest = post_str("destination");

		# TO DO: privacy settings for this function need to be updated
		# to reflect the stuff in photos_upload – (20130526/straup)

		if ($dest == 'fl'){
			$rsp = flickr_photos_upload($GLOBALS['cfg']['user'], $file, $args);
		}

		else {
			$args['preview'] = ($dest=='pf') ? 0 : 1;
			$rsp = photos_upload($GLOBALS['cfg']['user'], $file, $args);
		}

		unlink($file);

		if (! $rsp['ok']){
			api_output_error(999, $rsp['error']);
		}

		$photo = array(
			'id' => $rsp['id'],
			'url' => $rsp['url'],
		);

		$out = array(
			'photo' => $photo
		);

		api_output_ok($out);
		exit();
	}

	#################################################################

	# the end
