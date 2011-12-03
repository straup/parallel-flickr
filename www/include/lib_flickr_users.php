<?php

	# note: this has parallel-flickr specific tweaks that are not part
	# of flamework-flickrapp (20111202/straup)

	#################################################################

	function flickr_users_create_user($flickr_user){

		$hash = array();

		foreach ($flickr_user as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$rsp = db_insert('FlickrUsers', $hash);

		if (! $rsp['ok']){
			return null;
		}

		# hey look! this is parallel-flickr specific and not part
		# of flamework-flickrapp (20111202/straup)

		# just add the path_alias to the FlickrUsersPathAliases
		# table even if the feature flag is disabled so that it
		# will do the right thing if/when it is enabled.
		# (20111202/straup)

		if ($flickr_user['path_alias']){
			loadlib("flickr_users_path_aliases");
			$user = users_get_by_id($flickr_user['user_id']);
			flickr_users_path_aliases_create($user, $flickr_user['path_alias']);
		}

		$cache_key = "flickr_user_{$flickr_user['nsid']}";
		cache_set($cache_key, $flickr_user, "cache locally");

		$cache_key = "flickr_user_{$flickr_user['user_id']}";
		cache_set($cache_key, $flickr_user, "cache locally");

		return $flickr_user;
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

	function flickr_users_get_by_url($error_404=1){

		if (($path = get_str("path")) && ($GLOBALS['cfg']['enable_feature_path_alias_redirects'])){

			loadlib("flickr_users_path_aliases");

			$alias = flickr_users_path_aliases_get_by_alias($path);

			if (($alias) && ($alias['redirect_to'])){

				$new_path = urlencode($alias['redirect_to']);
				$redir = str_replace($path, $new_path, $_SERVER['REQUEST_URI']);
				header("location: {$redir}");
			}
		}

		if ($path = get_str("path")){
			$flickr_user = flickr_users_get_by_path_alias($path);
		}

		else if ($nsid = get_str("nsid")){
			$flickr_user = flickr_users_get_by_nsid($nsid);
		}

		if ((! $flickr_user) && ($error_404)){
			error_404();
		}

		return $flickr_user;
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

		if (! $GLOBALS['cfg']['enable_feature_path_alias_redirects']){

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

		loadlib("flickr_users_path_aliases");

		if ($row = flickr_users_path_aliases_get_by_alias($alias)){
			return flickr_users_get_by_user_id($row['user_id']);
		}

		return null;
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
