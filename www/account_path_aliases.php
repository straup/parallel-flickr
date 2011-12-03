<?php

	include("include/init.php");
	loadlib("flickr_users_path_aliases");

	if (! $GLOBALS['cfg']['enable_feature_path_alias_redirects']){
		error_disabled();
	}

	login_ensure_loggedin("/account/url/");

	$crumb_key = 'pathalias';
	$smarty->assign("crumb_key", $crumb_key);

	$crumb_ok = crumb_check($crumb_key);

	if ($crumb_ok){

		$ok = 1;

		$new_alias = post_str("path_alias");
		$new_alias = filter_strict($new_alias);
		$new_alias = trim($new_alias);

		if (! $new_alias){
			$GLOBALS['smarty']->assign("error", "invalid alias");
			$ok = 0;
		}

		if (($ok) && (! flickr_users_path_aliases_is_available($new_alias))){
			$GLOBALS['smarty']->assign("error", "alias taken");
			$ok = 0;
		}

		if ($ok){

			if (post_str("confirm")){

				$rsp = flickr_users_path_aliases_create($GLOBALS['cfg']['user'], $new_alias);

				if (! $rsp['ok']){
					$GLOBALS['smarty']->assign("error", "db error");
					$ok = 0;
				}
			}

			else {
				$GLOBALS['smarty']->assign("step", "confirm");
				$GLOBALS['smarty']->assign("path_alias", $new_alias);
			}
		}
	}

	$aliases = flickr_users_path_aliases_for_user($GLOBALS['cfg']['user']);
	$GLOBALS['smarty']->assign_by_ref("aliases", $aliases['rows']);

	$GLOBALS['smarty']->display("page_account_path_aliases.txt");
	exit();
?>
