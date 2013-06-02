<?php

	#################################################################

	# TO DO: put me in the config?
	# (20121103/straup)

	function api_oauth2_access_tokens_permissions_map($string_keys=0){

		$map = array(
			'0' => 'login',
			'1' => 'read',
			'2' => 'write',
		);

		if ($string_keys){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	function api_oauth2_access_tokens_ttl_map($string_keys=0){

		$map = array(
			'0' => 'until I revoke it',
			'3600' => 'for one hour',
			'21600' => 'for six hours',
			'86400' => 'for a day',
			'604800' => 'for one week',
			'2592000' => 'for a month',
		);

		if ($string_keys){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	function api_oauth2_access_tokens_is_valid_permission($perm, $str_perm=0){
		$map = api_oauth2_access_tokens_permissions_map($str_perm);
		return (isset($map[$perm])) ? 1 : 0;
	}

	#################################################################

	function api_oauth2_access_tokens_get_by_token($token){

		$cache_key = "oauth2_access_token_{$token}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$enc_token = AddSlashes($token);

		$sql = "SELECT * FROM OAuth2AccessTokens WHERE access_token='{$enc_token}'";
		$rsp = db_fetch($sql);
		$row = db_single($rsp);

		if ($rsp['ok']){
			cache_set($cache_key, $row);
		}

		return $row;
	}

	#################################################################

	function api_oauth2_access_tokens_for_user(&$user, $more=array()){

		$enc_user = AddSlashes($user['id']);

		$sql = "SELECT * FROM OAuth2AccessTokens WHERE user_id='{$enc_user}' AND (expires=0 OR expires > UNIX_TIMESTAMP(NOW())) ORDER BY created DESC";
		$rsp = db_fetch_paginated($sql, $more);

		return $rsp;		
	}

	#################################################################

	function api_oauth2_access_tokens_for_key(&$key, $more=array()){

		$enc_key = AddSlashes($key['id']);

		$sql = "SELECT * FROM OAuth2AccessTokens WHERE api_key_id='{$enc_key}' AND (expires=0 OR expires > UNIX_TIMESTAMP(NOW())) ORDER BY created DESC";
		$rsp = db_fetch_paginated($sql, $more);

		return $rsp;		
	}

	#################################################################

	function api_oauth2_access_tokens_count_for_key(&$key){

		$more = array(
			'per_page' => 1,
		);

		$rsp = api_oauth2_access_tokens_for_key($key, $more);
		return $rsp['pagination']['total_count'];
	}

	#################################################################

	function api_oauth2_access_tokens_get_for_user_and_key(&$user, &$key){

		$cache_key = "oauth2_access_token_uk_{$user['id']}_{$key['id']}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			# return $cache['data'];
		}

		$enc_user = AddSlashes($user['id']);
		$enc_key = AddSlashes($key['id']);

		$sql = "SELECT * FROM OAuth2AccessTokens WHERE user_id='{$enc_user}' AND api_key_id='{$enc_key}'  AND (expires=0 OR expires > UNIX_TIMESTAMP(NOW()))";

		$rsp = db_fetch($sql);
		$row = db_single($rsp);

		if ($rsp['ok']){
			cache_set($cache_key, $row);
		}

		return $row;
	}

	#################################################################

	function api_oauth2_access_tokens_create(&$key, &$user, $perms, $ttl=0){

		$id = dbtickets_create(64);

		$token = api_oauth2_access_tokens_generate_token();
		$now = time();

		$row = array(
			'id' => $id,
			'perms' => $perms,
			'api_key_id' => $key['id'],
			'user_id' => $user['id'],
			'access_token' => $token,
			'created' => $now,
			'last_modified' => $now,
		);

		if (intval($ttl) > 0){
			$row['expires'] = $now + $ttl;
		}

		$insert = array();

		foreach ($row as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert('OAuth2AccessTokens', $insert);

		if ($rsp['ok']){
			$rsp['token'] = $row;
		}

		return $rsp;
	}

	#################################################################

	function api_oauth2_access_tokens_update(&$token, $update){

		$update['last_modified'] = time();

		$insert = array();

		foreach ($update as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$enc_id = AddSlashes($token['id']);
		$where = "id='{$enc_id}'";

		$rsp = db_update('OAuth2AccessTokens', $update, $where);

		if ($rsp['ok']){

			api_oauth2_access_tokens_purge_cache($token);

			$token = array_merge($token, $update);
			$rsp['token'] = $token;
		}

		return $rsp;
	}

	#################################################################

	# THERE IS NO UNDO...

	function api_oauth2_access_tokens_delete(&$token){

		$enc_id = AddSlashes($token['id']);
		$sql = "DELETE FROM OAuth2AccessTokens WHERE id='{$enc_id}'";

		$rsp = db_write($sql);

		if ($rsp['ok']){
			api_oauth2_access_tokens_purge_cache($token);
		}

		return $rsp;
	}

	#################################################################

	function api_oauth2_access_tokens_delete_for_key(&$key){

		$enc_key = AddSlashes($key['id']);
		$sql = "DELETE FROM OAuth2AccessTokens WHERE api_key_id='{$enc_key}'";

		# TO DO: purge caches - iterate over all the things?
		# (20121103/straup)

		$rsp = db_write($sql);
		return $rsp;
	}

	#################################################################

	function api_oauth2_access_tokens_purge_cache(&$token){

		$cache_keys = array(
			"oauth2_access_token_{$token['access_token']}",
			"oauth2_access_token_uk_{$token['user_id']}_{$token['api_key_id']}",
		);

		foreach ($cache_keys as $key){
			cache_unset($key);
		}
	}

	#################################################################

	function api_oauth2_access_tokens_generate_token(){
		$token = md5(random_string(100) . time());
		return $token;
	}

	#################################################################

	# the end
