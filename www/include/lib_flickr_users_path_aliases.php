<?php

	loadlib("flickr_api");

	#################################################################

	function flickr_users_path_aliases_get_by_alias($alias){

		$cache_key = "flickr_user_path_aliases_{$alias}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$enc_alias = AddSlashes($alias);

		$sql = "SELECT * FROM FlickrUsersPathAliases WHERE path_alias='{$enc_alias}'";
		$row = db_single(db_fetch($sql));

		cache_set($cache_key, $row, "cache locally");
		return $row;
	}

	#################################################################

	function flickr_users_path_aliases_current_for_user(&$user){

		$rsp = flickr_users_path_aliases_for_user($user);

		if (($rsp['ok']) && (count($rsp['rows']))){
			return $rsp['rows'][0]['path_alias'];
		}

		return null;
	}

	#################################################################

	function flickr_users_path_aliases_for_user(&$user){

		$cache_key = "flickr_user_path_aliases_user_{$user['id']}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$enc_id = AddSlashes($user['id']);

		$sql = "SELECT * FROM FlickrUsersPathAliases WHERE user_id='{$enc_id}' ORDER BY created DESC";
		$rsp = db_fetch($sql);

		cache_set($cache_key, $rsp, "cache locally");

		return $rsp;
	}

	#################################################################

	function flickr_users_path_aliases_is_available($alias){

		if (flickr_users_path_aliases_get_by_alias($alias)){
			return 0;
		}

		$method = "flickr.urls.lookupUser";
		$url = "http://www.flickr.com/photos/{$alias}";

		$args = array(
			'url' => $url,
		);

		$rsp = flickr_api_call($method, $args);
		return ($rsp['ok']) ? 0 : 1;
	}

	#################################################################

	function flickr_users_path_aliases_create(&$user, $alias){

		$rsp = flickr_users_path_aliases_for_user($user);
		$old_aliases = $rsp['rows'];

		#

		$enc_alias = AddSlashes($alias);
		$now = time();

		$row = array(
			'user_id' => $user['id'],
			'created' => $now,
			'path_alias' => $alias,
		);

		$insert = array();

		foreach ($row as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert('FlickrUsersPathAliases', $insert);

		if ($rsp['ok']){

			$rsp['path_alias'] = $row;

			foreach ($old_aliases as $old_alias){

				$update = array(
					'redirect_to' => $alias,
				);

				flickr_users_path_aliases_update($old_alias, $update);
			}

			$cache_key = "flickr_users_path_alias_user_{$user['id']}";
			cache_unset($cache_key);
		}

		return $rsp;
	}

	#################################################################

	function flickr_users_path_aliases_update(&$path_alias, &$update){

		$insert = array();

		foreach ($update as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$enc_alias = AddSlashes($path_alias['path_alias']);
		$where = "path_alias='{$enc_alias}'";

		$rsp = db_update('FlickrUsersPathAliases', $update, $where);

		if ($rsp['ok']){
			$cache_key = "flickr_users_path_alias_{$path_alias['path_alias']}";
			cache_unset($cache_key);
		}

		return $rsp;
	}

	#################################################################

?>
