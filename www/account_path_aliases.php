<?php

	include("include/init.php");
	loadlib("flickr_users_path_aliases");

	login_ensure_loggedin("/account/url/");

	# TO DO: feature flag

	$crumb_key = 'backups';
	$smarty->assign("crumb_key", $crumb_key);

	$crumb_ok = crumb_check($crumb_key);

	if ($crumb_ok){

		$ok = 1;

		$new_alias = post_str("path_alias");
		$new_alias = filter_strict($new_alias);
		$new_alias = trim($new_alias);

		if (! $new_alias){
			$ok = 0;
		}

		if (($ok) && (flickr_users_path_aliases_get_by_alias($new_alias))){
			$ok = 0;
		}

		if ($ok){

			$rsp = flickr_users_path_aliases_create($GLOBALS['cfg']['user'], $new_alias);

			if (! $rsp['ok']){
				$ok = 0;
			}
		}

		$GLOBALS['smarty']->assign("ok", $ok);
	}

	$aliases = flickr_users_path_aliases_for_user($GLOBALS['cfg']['user']);
	$GLOBALS['smarty']->assign_by_ref("aliases", $aliases['rows']);

	$GLOBALS['smarty']->display("page_account_path_aliases.txt");
	exit();
?>
