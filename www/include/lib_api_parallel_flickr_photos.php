<?php

	loadlib("photos_upload");
	loadlib("flickr_photos_upload");

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

		$file = $_FILES['photo']['tmp_name'];

		$args = array();

		# FIX ME: pull in title, etc.

		$args['perms'] = post_str("perms");
		$args['geoperms'] = post_str("geoperms");
		$args['filtr'] = post_str("filtr");

		$dest = post_str("destionation");

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
