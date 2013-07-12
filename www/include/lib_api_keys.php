<?php

	loadlib("random");

	#################################################################

	function api_keys_roles_map($string_keys=0){

		$map = array(
			0 => 'general',
			1 => 'site',
		);

		if ($string_keys){
			$map = array_flip($map);
		}

		return $map;
	}

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

	function api_keys_get_keys($args=array()){

		$sql = "SELECT * FROM ApiKeys FORCE INDEX (by_role_created) WHERE role_id=0 ORDER BY created DESC";
		$rsp = db_fetch_paginated($sql, $args);

		return $rsp;
	}

	#################################################################

	# See this. It's called '_fetch_site_key' while the function below
	# it is called '_get_site_key'. It's a (possibly annoying but) important
	# distinction. The former is the one that retrieves a row from the
	# database and performs checks and deletes/creates/rotates keys as
	# needed. (20130508/straup)

	function api_keys_fetch_site_key(){

		$ttl = $GLOBALS['cfg']['api_site_keys_ttl'];

		$key = api_keys_get_site_key();
		$now = time();

		# TO DO: error handling/reporting...

		if (! $key){
			$rsp = api_keys_create_site_key();
			$key = ($rsp['ok']) ? $rsp['key'] : null;
		}

		else if ($now >= ($key['created'] + $ttl)){

			$delete_rsp = api_keys_delete_site_key($key);
			$create_rsp = api_keys_create_site_key();

			$key = ($create_rsp['ok']) ? $create_rsp['key'] : null;
		}

		else {}

		return $key;
	}

	#################################################################

	function api_keys_get_site_key(){

		$cache_key = "api_key_site_key";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$map = api_keys_roles_map('string keys');
		$role = $map['site'];

		$enc_role = AddSlashes($role);

		$sql = "SELECT * FROM ApiKeys WHERE role_id='{$enc_role}' AND deleted=0";
		$rsp = db_fetch($sql);

		$row = db_single($rsp);

		if ($rsp['ok']){
			cache_set($cache_key, $row);
		}

		return $row;
	}

	#################################################################

	function api_keys_get_site_keys($more=array()){

		$defaults = array(
			'ensure_active' => 1,
		);

		$more = array_merge($defaults, $more);

		$map = api_keys_roles_map('string keys');
		$role = $map['site'];

		$enc_role = AddSlashes($role);

		$sql = "SELECT * FROM ApiKeys WHERE role_id='{$enc_role}'";

		if ($more['ensure_active']){
			$sql .= " AND deleted=0";
		}

		$sql .= " ORDER BY created DESC";

		$rsp = db_fetch_paginated($sql, $more);
		return $rsp;
	}

	#################################################################

	function api_keys_create_site_key(){

		$user_id = 0;
		$id = dbtickets_create(64);

		$role_map = api_keys_roles_map('string keys');
		$role_id = $role_map['site'];

		$key = api_keys_generate_key();
		$secret = random_string(64);

		$now = time();

		$key_row = array(
			'id' => $id,
			'user_id' => $user_id,
			'role_id' => $role_id,
			'api_key' => $key,
			'app_secret' => $secret,
			'created' => $now,
			'last_modified' => $now,
			'app_title' => "{$GLOBALS['cfg']['site_name']} site key",
		);

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

	function api_keys_delete_site_key(&$key, $reason='expired'){

		$rsp = api_keys_delete($key, $reason);

		if ($rsp['ok']){
			cache_unset('api_key_site_key');
		}

		return $rsp;
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

		$role_map = api_keys_roles_map('string keys');
		$role_id = $role_map['general'];

		$key = api_keys_generate_key();
		$secret = random_string(64);

		$now = time();

		$key_row = array(
			'id' => $id,
			'user_id' => $user['id'],
			# 'role_id' => $role_id,
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
