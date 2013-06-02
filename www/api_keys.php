<?php

	include("include/init.php");
	loadlib("api_keys");

	features_ensure_enabled("api");

	login_ensure_loggedin();

	$more = array();

	if ($page = get_int32("page")){
		$more['page'] = $page;
	}

	$rsp = api_keys_for_user($GLOBALS['cfg']['user'], $more);
	$keys = $rsp['rows'];

	$GLOBALS['smarty']->assign_by_ref("keys", $keys);

	$GLOBALS['smarty']->display("page_api_keys.txt");
	exit();

?>
