<?php

	include("include/init.php");
	loadlib("flickr_api");
	loadlib("flickr_users");

	error_404();

	login_ensure_loggedin($_SERVER['REQUEST_URI']);

	$flickr_user = flickr_users_get_by_user_id($GLOBALS['cfg']['user_id']);

	$crumb_key = 'flickr_auth_token';
	$GLOBALS['smarty']->assign("crumb_key", $crumb_key);

	$perms = request_str("perms");

	$perms_map = flickr_api_authtoken_perms_map('string keys');
	$GLOBALS['smarty']->assign_by_ref("perms_map", $perms_map);

	if (! $perms){
		$perms = 'read';
	}

	elseif (! isset($perms_map[$perms])){

		$GLOBALS['smarty']->display("page_flickr_get_auth_token.txt");
		exit();
	}

	else {}

	if ($flickr_user['auth_token']){

		if ($flickr_user['token_perms'] == $perms_map[$perms])){
			# do something
		}

		# confirm token perms change

		if ((! crumb_check($crumb_key)) || (! post_str("confirm"))){

			$GLOBALS['smarty']->display("page_flickr_get_auth_token.txt");
			exit();
		}

	}

	# Build a URL with the perms for the auth token we're requesting
	# and send the user there. Rocket science, I know...

	$extra = array(
		# some sort of flag/test not to create a new user...
	);

	if ($redir = get_str('redir')){
		$extra['redir'] = $redir;
	}

	$perms = $GLOBALS['cfg']['flickr_api_perms'];

	$url = flickr_api_auth_url($perms, $extra);

	header("location: {$url}");
	exit();
?>
