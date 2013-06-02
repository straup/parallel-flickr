<?php

	# sad face...
	# http://www.rfc-editor.org/rfc/rfc6749.txt

	include("include/init.php");

	features_ensure_enabled("api");
	features_ensure_enabled("api_delegated_auth");

	loadlib("api_keys");
	loadlib("api_keys_utils");

	loadlib("api_oauth2_grant_tokens");
	loadlib("api_oauth2_access_tokens");

	#

	function local_send_json($json, $is_error=0){

		if ($is_error){
			header("HTTP/1.1 400 Bad Request");
		}

		header("Content-Type: application/json;charset=UTF-8");
		header("Cache-Control: no-store");
		header("Pragma: no-cache");

		echo json_encode($json);
		exit();
	}

	#

	$key_more = array(
		'ensure_isown' => 0
	);

	$key_row = api_keys_utils_get_from_url($key_more);
	$GLOBALS['smarty']->assign_by_ref("key", $key_row);

	$ok = 1;
	$error = null;

	# Basics (redirect URLs)

	if (($ok) && (! $key_row['app_callback'])){
		error_403();
	}

	# Basics (everything else)

	$grant = get_str("grant_type");
	$code = get_str("code");

	if ((! $code) || (! $grant)){
		$error = "invalid_request";
		$ok = 0;
	}

	if (($ok) && ($grant != "authorization_code")){
		$error = "invalid_grant";
		$ok = 0;	
	}

	if (! $ok){

		$rsp = array('error' => $error);
		local_send_json($rsp);
		exit();
	}

	# Sort out the grant tokens

	$grant_token = api_oauth2_grant_tokens_get_by_code($code);

	if (($ok) && (! $grant_token)){
		$error = "invalid_grant 1";
		$ok = 0;
	}

	if (($ok) && ($grant_token['code'] != $code)){
		$error = "invalid_grant 2";
		$ok = 0;
	}

	if (($ok) && ($grant_token['api_key_id'] != $key_row['id'])){
		$error = "invalid_client 3";
		$ok = 0;
	}

	if (($ok) && (! api_oauth2_grant_tokens_is_timely($grant_token))){
		$error = "invalid_grant 4";
		$ok = 0;
	}

	if ($ok){

		$user = users_get_by_id($grant_token['user_id']);

		if ((! $user) || ($user['deleted'])){
			$error = "invalid_request 5";
			$ok = 0;
		}		
	}

	if (! $ok){

		$rsp = array('error' => $error);
		local_send_json($rsp);
		exit();
	}

	# Purge the grant

	api_oauth2_grant_tokens_delete($grant_token);

	# Generate the access token (check to make sure one doesn't already exist)

	$access_token = api_oauth2_access_tokens_get_for_user_and_key($user, $key_row);

	if (! $access_token){

		$perms = $grant_token['perms'];
		$ttl = $grant_token['ttl'];

		$rsp = api_oauth2_access_tokens_create($key_row, $user, $perms, $ttl);

		if (! $rsp['ok']){

			$rsp = array('error' => 'server_error');
			local_send_json($rsp);
			exit();
		}

		$access_token = $rsp['token'];		
	}

	# Okay, soup for you!

	$perms_map = api_oauth2_access_tokens_permissions_map();
	$scope = $perms_map[$access_token['perms']];

	$rsp = array(
		'access_token' => $access_token['access_token'],
		# 'token_type' => 'OMGWTF... see section 7.1',
		'scope' => $scope,
		'expires' => $access_token['expires'],
	);

	local_send_json($rsp);
	exit();
?>
