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

		$dest = post_str("destination");

		if (! photos_upload_can_upload($GLOBALS['cfg']['user'], $dest)){
			# api_output_error(999, "Insufficient upload permissions");
		}

		$file = $_FILES['photo']['tmp_name'];

		$args = array();

		# title;description;tags aren't actually used or imported
		# in photos_upload yet (20130701/straup)

		$args['title'] = post_str("title");
		$args['description'] = post_str("description");
		$args['tags'] = post_str("tags");

		# TO DECIDE: cast all perms in to is_FOO strings like we do for the
		# call the flickr_photos_upload or just treat that as a flickr-only
		# thing (20130701/straup)

		$args['perms'] = post_str("perms");
		$args['geoperms'] = post_str("geoperms");

		$args['filtr'] = post_str("filtr");

		# TO DO: check $dest but also check $GLOBALS['cfg'] ...

		if ($dest == 'fl'){

			$perms = $args['perms'];
			unset($args['perms']);

			$perms_hash = photos_upload_strperms_to_hash($perms, "flickr api");
			$args = array_merge($args, $perms_hash);

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
