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

			$rsp = flickr_users_path_aliases_create($user, $flickr_user['path_alias']);

			# Okay, so this (mysql's duplicate key (1062) error) means that a
			# parallel-flickr user has managed to change their path alias to be
			# the same as something that a person on flickr chose *after* the
			# local user changed their path alias. So, because parallel-flickr
			# is not meant to be a global mirror of the entirety of flickr we're
			# going to flag the record for $flickr_user and update lib_flickr_urls
			# to only ever use that user's NSID when building URLs. This is not
			# a perfect solution in that if you did a GET on the path_alias in
			# question it would redirect to the local user who "stole" the path
			# alias from the (newer) flickr user but in a perverse kind of way it
			# is the right thing to do. But only if path_alias redirects are enabled
			# which is why we always store what flickr tells us against the FlickrUsers
			# table. (20111202/straup)

			if ((! $rsp['ok']) && ($rsp['error_code'] == 1062)){

				$alias = flickr_users_path_aliases_get_by_alias($flickr_user['path_alias']);

				# unless of course the two users are the same, which shouldn't
				# ever happen but that's what makes life interesting, right?

				if ($alias['user_id'] != $flickr_user['user_id']){

					$update = array(
						'path_alias_taken_by' => $alias['user_id'],
					);

					$update_rsp = flickr_users_update_user($flickr_user, $update);

					# note the caching below

					if ($update_rsp['ok']){
						$flickr_user = array_merge($flickr_user, $update);
					}
				}
			}
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

		$path = get_str("path");
		$nsid = get_str("nsid");

		if (($path) && ($GLOBALS['cfg']['enable_feature_path_alias_redirects'])){

			loadlib("flickr_users_path_aliases");

			$alias = flickr_users_path_aliases_get_by_alias($path);

			if (($alias) && ($alias['redirect_to'])){

				$new_path = urlencode($alias['redirect_to']);
				$redir = str_replace($path, $new_path, $_SERVER['REQUEST_URI']);
				header("location: {$redir}");
			}
		}

		if ($path){

			$flickr_user = flickr_users_get_by_path_alias($path);

			# see also: notes in flickr_users_create_user()
			# see also: inc_path_alias_conflict.txt

			if (($flickr_user) && ($GLOBALS['cfg']['enable_feature_path_alias_redirects'])){

				$other_flickr_user = _flickr_users_get_by_path_alias($path);
				$other_user = users_get_by_id($other_flickr_user['user_id']);

				$GLOBALS['smarty']->assign("path_alias_conflict", 1);
				$GLOBALS['smarty']->assign_by_ref("path_alias_other_user", $other_user);
				$GLOBALS['smarty']->assign_by_ref("path_alias_other_flickr_user", $other_flickr_user);
			}
		}

		else if ($nsid){
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

			return _flickr_users_get_by_path_alias($alias);
		}

		loadlib("flickr_users_path_aliases");

		if ($row = flickr_users_path_aliases_get_by_alias($alias)){
			return flickr_users_get_by_user_id($row['user_id']);
		}

		return null;
	}

	#################################################################

	# THIS IS A TERRIBLE NAME... CHANGE ME

	function _flickr_users_get_by_path_alias($alias){

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
