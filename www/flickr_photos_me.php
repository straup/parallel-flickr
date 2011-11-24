<?php

	include("include/init.php");
	loadlib("flickr_urls");

	login_ensure_loggedin($_SERVER['REQUEST_URI']);

	$ctx = get_str("context");

	$map = array(
		'faves' => 'flickr_urls_faves_user',
		'places' => 'flickr_urls_photos_user_places',
		'cameras' => 'flickr_urls_photos_user_cameras',
	);

	if (isset($map[$ctx]) && function_exists($map[$ctx])){
		$url = call_user_func($map[$ctx], &$GLOBALS['cfg']['user']);
	}

	else {
		$url = flickr_urls_photos_user($GLOBALS['cfg']['user']);
	}

	header("location: {$url}");
	exit();
?>
