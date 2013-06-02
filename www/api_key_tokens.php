<?php

	include("include/init.php");
	loadlib("api_keys");
	loadlib("api_oauth2_access_tokens");

	features_ensure_enabled("api");

	login_ensure_loggedin();

	$api_key = get_str("api_key");

	if (! $api_key){
		error_404();
	}

	$key_row = api_keys_get_by_key($api_key);

	if (! $key_row){
		error_404();
	}

	if ($key_row['deleted']){
		error_410();
	}

	if ($key_row['user_id'] != $GLOBALS['cfg']['user']['id']){
		error_403();
	}

	$more = array();

	if ($page = get_int32("page")){
		$more['page'] = $page;
	}

	$rsp = api_oauth2_access_tokens_for_key($key_row, $more);
	$tokens = array();

	foreach ($rsp['rows'] as $row){
		$row['user'] = users_get_by_id($row['user_id']);
		$tokens[] = $row;
	}

	$GLOBALS['smarty']->assign_by_ref("key", $key_row);
	$GLOBALS['smarty']->assign_by_ref("tokens", $tokens);

	$perms_map = api_oauth2_access_tokens_permissions_map();
	$GLOBALS['smarty']->assign_by_ref("permissions", $perms_map);
	
	$GLOBALS['smarty']->display("page_api_key_tokens.txt");
	exit();

?>
