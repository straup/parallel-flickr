<?php

	include("include/init.php");
	loadlib("flickr_api");
	loadlib("flickr_users");

	login_ensure_loggedin($_SERVER['REQUEST_URI']);

	features_ensure_enabled("flickr_api_change_perms");

	$flickr_user = flickr_users_get_by_user_id($GLOBALS['cfg']['user']['id']);

	$crumb_key = 'flickr_auth_token';
	$GLOBALS['smarty']->assign("crumb_key", $crumb_key);

	$perms = request_str("perms");

	$perms_map = flickr_api_authtoken_perms_map();
	$perms_map_str = flickr_api_authtoken_perms_map('string keys');

	$GLOBALS['smarty']->assign_by_ref("perms_map", $perms_map);

	if (! $perms){
		$perms = 'read';
	}

	elseif (! isset($perms_map_str[$perms])){

		$GLOBALS['error'] = 'invalid_perm';
		$GLOBALS['smarty']->display("page_account_flickr_auth.txt");
		exit();
	}

	else {}

	if ($flickr_user['auth_token']){

		# Perms are the same; just carry on...

		if ($flickr_user['token_perms'] == $perms_map_str[$perms]){

			$redir = get_str("redir");

			if (! $redir){
				$redir = $GLOBALS['cfg']['abs_root_url'];
			}

			header("location: {$redir}");
			exit();
		}

		# confirm token perms change

		if ((! crumb_check($crumb_key)) || (! post_str("confirm"))){

			$old_perms = $perms_map[$flickr_user['token_perms']];

			$GLOBALS['smarty']->assign("old_perms", $old_perms);
			$GLOBALS['smarty']->assign("new_perms", $perms);

			$more_permissive = ($perms_map_str[$perms] > $flickr_user['token_perms']) ? 1 : 0;
			$GLOBALS['smarty']->assign("more_permissive", $more_permissive);

			$GLOBALS['smarty']->display("page_account_flickr_auth.txt");
			exit();
		}

	}

	#

	$now = time();
	$crumb = implode(":", array($GLOBALS['cfg']['user']['id'], $now));

	$enc_crumb = crypto_encrypt($crumb, $GLOBALS['cfg']['flickr_api_secret']);

	$extra = array(
		'crumb' => $enc_crumb,
	);

	if ($redir = get_str('redir')){
		$extra['redir'] = $redir;
	}

	$url = flickr_api_auth_url($perms, $extra);

	header("location: {$url}");
	exit();
?>
