<?php

	include("include/init.php");
	loadlib("flickr_urls");

	login_ensure_loggedin($_SERVER['REQUEST_URI']);

	$ctx = get_str("context");

	if ($ctx == 'faves'){
		$url = flickr_urls_faves_user($GLOBALS['cfg']['user']);
	}

	else {
		$url = flickr_urls_photos_user($GLOBALS['cfg']['user']);
	}

	header("location: {$url}");
	exit();
?>
