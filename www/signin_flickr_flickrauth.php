<?php

	include("include/init.php");
	loadlib("flickr_api");

	$redir = (get_str('redir')) ? get_str('redir') : '/';

	# Some basic sanity checking like are you already logged in?

	if ($GLOBALS['cfg']['user']['id']){
		header("location: {$redir}");
		exit();
	}

	if (! $GLOBALS['cfg']['enable_feature_signin']){
		$GLOBALS['smarty']->display("page_signin_disabled.txt");
		exit;
	}

	# Build a URL with the perms for the auth token we're requesting
	# and send the user there. Rocket science, I know...

	$extra = array();

	if ($redir = get_str('redir')){
		$extra['redir'] = $redir;
	}

	$perms = $GLOBALS['cfg']['flickr_api_perms'];

	$url = flickr_api_auth_url($perms, $extra);

	header("location: {$url}");
	exit();
?>
