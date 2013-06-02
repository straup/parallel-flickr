<?php

	loadlib("random");

	#################################################################

	function api_keys_get_by_id($id){

		$cache_key = "api_key_id_{$id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$enc_id = AddSlashes($id);

		$sql = "SELECT * FROM ApiKeys WHERE id='{$enc_id}'";

		$rsp = db_fetch($sql);
		$row = db_single($rsp);

		if ($rsp['ok']){
			cache_set($cache_key, $row);
		}

		return $row;
	}

	#################################################################

	function api_keys_get_by_key($key){

		$cache_key = "api_key_key_{$key}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$enc_key = AddSlashes($key);

		$sql = "SELECT * FROM ApiKeys WHERE api_key='{$enc_key}'";

		$rsp = db_fetch($sql);
		$row = db_single($rsp);

		if ($rsp['ok']){
			cache_set($cache_key, $row);
		}

		return $row;
	}

	#################################################################

	function api_keys_for_user(&$user, $more=array()){

		$enc_user = AddSlashes($user['id']);

		$sql = "SELECT * FROM ApiKeys WHERE user_id='{$enc_user}' AND deleted=0 ORDER BY created DESC";
		$rsp = db_fetch_paginated($sql, $more);

		return $rsp;
	}

	#################################################################

	function api_keys_create($user_id, $title, $description, $callback=''){

		$user = users_get_by_id($user_id);

		$id = dbtickets_create(64);

		$key = api_keys_generate_key();
		$secret = random_string(64);

		$now = time();

		$key_row = array(
			'id' => $id,
			'user_id' => $user['id'],
			'api_key' => $key,
			'app_secret' => $secret,
			'created' => $now,
			'last_modified' => $now,
			'app_title' => $title,
			'app_description' => $description,
			'app_callback' => $callback,
		);

		# TO DO: callbacks and other stuff (what?)

		$insert = array();

		foreach ($key_row as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert('ApiKeys', $insert);

		if ($rsp['ok']){
			$rsp['key'] = $key_row;
		}

		return $rsp;
	}

	#################################################################

	function api_keys_update(&$key, $update){

		$update['last_modified'] = time();

		$insert = array();

		foreach ($update as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$enc_id = AddSlashes($key['id']);
		$where = "id='{$enc_id}'";

		$rsp = db_update('ApiKeys', $insert, $where);

		if ($rsp['ok']){

			api_keys_purge_cache($key);
		
			$key = array_merge($key, $update);
			$rsp['key'] = $key;
		}

		return $rsp;
	}

	#################################################################

	function api_keys_disable(&$key){
		$update = array('disabled' => time());
		return api_keys_update($key, $update);
	}

	#################################################################

	function api_keys_enable(&$key){
		$update = array('disabled' => 0);
		return api_keys_update($key, $update);
	}

	#################################################################

	function api_keys_delete(&$key, $reason=''){

		loadlib("api_oauth2_access_tokens");
		$rsp = api_oauth2_access_tokens_delete_for_key($key);

		if (! $rsp['ok']){
			return $rsp;
		}

		$update = array('deleted' => time());
		return api_keys_update($key, $update);
	}

	#################################################################

	function api_keys_undelete(&$key){
		$update = array('deleted' => 0);
		return api_keys_update($key, $update);
	}

	#################################################################

	function api_keys_purge_cache(&$key){

		$cache_keys = array(
			"api_key_id_{$key['id']}",
			"api_key_key_{$key['api_key']}",
		);

		foreach ($cache_keys as $cache_key){
			cache_unset($cache_key);
		}
	}

	#################################################################

	function api_keys_generate_key(){
		$key = md5(random_string(100) . time());
		return $key;
	}

	#################################################################

	# the end
