<?php

	include("include/init.php");

	loadlib("flickr_photos_upload");
	loadlib("api_output");

	# FIX ME: use API hooks

	login_ensure_loggedin();

	$crumb_key = 'upload';
	$crumb_ok = crumb_check($crumb_key);

	if (! $crumb_ok){
		api_output_error(999, "missing crumb");
	}

	if (! $_FILES['photo']){
		api_output_error(999, "missing photo");
	}

	if ($_FILES['photo']['error']){
		api_output_error(999, "server error: {$_FILES['photo']['error']}");
	}

	$file = $_FILES['photo']['tmp_name'];

	# FIX ME: pull in title, etc.

	$args = array();

	$rsp = flickr_photos_upload($GLOBALS['cfg']['user'], $file, $args);

	if (! $rsp['ok']){
		api_output_error(999, $rsp['error']);
	}

	api_output_ok($rsp);
	exit();
?>
