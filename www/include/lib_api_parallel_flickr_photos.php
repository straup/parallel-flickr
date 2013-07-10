<?php

	loadlib("photos_upload");
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

		if (! features_is_enabled("uploads")){
			api_output_error(999, "uploads are disabled");
		}

		if (! features_is_enabled("uploads_by_api")){
			api_output_error(999, "uploads are disabled");
		}

		if (! $_FILES['photo']){
			api_output_error(999, "missing photo");
		}

		if ($_FILES['photo']['error']){
			api_output_error(999, "server error: {$_FILES['photo']['error']}");
		}

		$send_to = post_str("destination");
		$send_to = photos_upload_resolve_sendto($send_to);

		$rsp = photos_upload_can_upload($GLOBALS['cfg']['user'], $send_to);

		if (! $rsp['ok']){
			api_output_error(999, $rsp['error']);
		}

		$file = $_FILES['photo']['tmp_name'];

		$args = array(
			'send_to' => $send_to,
		);

		# description;tags aren't actually used or imported
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

		if ($notify = post_str("notify")){
			$notify = photos_upload_resolve_notifications($notify);
			$args['notify'] = $notify;
		}

		# TO DO: check $dest but also check $GLOBALS['cfg'] ...

		$rsp = photos_upload($GLOBALS['cfg']['user'], $file, $args);

		if (file_exists($file)){
			unlink($file);
		}

		if (! $rsp['ok']){
			api_output_error(999, $rsp['error']);
		}

		$out = array(
			'photo' => $rsp['photo'],
		);

		api_output_ok($out);
		exit();
	}

	#################################################################

	function api_parallel_flickr_photos_delete(){

		api_output_error(999, "Why are you trying to call this");

		$id = post_int32("id");

		if (! $id){
			api_output_error(999, "Missing photo ID");
		}

		$photo = flickr_photos_get_by_id($id);

		if (! $photo){
			api_output_error(999, "Invalid photo ID");
		}

		if ($photo['user_id'] != $GLOBALS['cfg']['user']['id']){
			api_output_error(999, "Insufficient permissions");
		}

		$rsp = flickr_photos_delete_photo($photo);

		if (! $rsp['ok']){
			api_output_error(999, $rsp['error']);
		}

		api_output_ok();
	}

	#################################################################

	# the end
