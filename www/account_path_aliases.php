<?php

	include("include/init.php");
	loadlib("flickr_users_path_aliases");

	login_ensure_loggedin("/account_path_aliases.php");

	$crumb_key = 'backups';
	$smarty->assign("crumb_key", $crumb_key);

	$crumb_ok = crumb_check($crumb_key);

	if ($crumb_ok){

	}

	$aliases = flickr_users_path_aliases_for_user($GLOBALS['cfg']['user']);

dumper($aliases);

	$GLOBALS['smarty']->display("page_account_path_aliases.txt");
	exit();
?>
