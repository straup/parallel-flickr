<?php

	#################################################################

	function flickr_users_create_user($user){

		$hash = array();

		foreach ($user as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$rsp = db_insert('FlickrUsers', $hash);

		if (!$rsp['ok']){
			return null;
		}

		$cache_key = "flickr_user_{$user['nsid']}";
		cache_set($cache_key, $user, "cache locally");

		$cache_key = "flickr_user_{$user['id']}";
		cache_set($cache_key, $user, "cache locally");

		return $user;
	}

	#################################################################

	function flickr_users_update_user(&$flickr_user, $update){

		$hash = array();
		
		foreach ($update as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$enc_id = AddSlashes($flickr_user['user_id']);
		$where = "user_id='{$enc_id}'";

		$rsp = db_update('FlickrUsers', $hash, $where);

		if ($rsp['ok']){

			$flickr_user = array_merge($flickr_user, $update);

			$cache_key = "flickr_user_{$flickr_user['nsid']}";
			cache_unset($cache_key);

			$cache_key = "flickr_user_{$flickr_user['user_id']}";
			cache_unset($cache_key);
		}

		return $rsp;
	}

	#################################################################

	function flickr_users_get_by_nsid($nsid){

		$cache_key = "flickr_user_{$nsid}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$enc_nsid = AddSlashes($nsid);

		$sql = "SELECT * FROM FlickrUsers WHERE nsid='{$enc_nsid}'";
		$rsp = db_fetch($sql);
		$user = db_single($rsp);

		cache_set($cache_key, $user, "cache locally");
		return $user;
	}

	#################################################################

	function flickr_users_get_by_path_alias($alias){

		$cache_key = "flickr_user_alias_{$alias}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$enc_alias = AddSlashes($alias);

		$sql = "SELECT * FROM FlickrUsers WHERE path_alias='{$enc_alias}'";
		$user = db_single(db_fetch($sql));

		cache_set($cache_key, $user, "cache locally");
		return $user;
	}

	#################################################################

	function flickr_users_get_by_user_id($user_id){

		$cache_key = "flickr_user_{$user_id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$enc_id = AddSlashes($user_id);

		$sql = "SELECT * FROM FlickrUsers WHERE user_id='{$enc_id}'";

		$rsp = db_fetch($sql);
		$user = db_single($rsp);

		cache_set($cache_key, $user, "cache locally");
		return $user;
	}

	#################################################################

	function flickr_users_ensure_user_account($nsid, $username=''){

		$flickr_user = flickr_users_get_by_nsid($nsid);

		if ($flickr_user){
			$user = users_get_by_id($flickr_user['user_id']);
			return $user;
		}

		loadlib("random");
		$password = random_string(32);

		if ($username == ''){
			$username = $nsid;
		}

		# TO DO: error handling

		$user = users_create_user(array(
			"username" => $username,
			"email" => "{$username}@donotsend-flickr.com",
			"password" => $password,
		));

		$flickr_user = flickr_users_create_user(array(
			'user_id' => $user['id'],
			'nsid' => $nsid,
			# note the lack of an auth_token
		));

		return $user;
	}

	#################################################################

?>
