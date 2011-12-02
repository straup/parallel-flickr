<?php

	#################################################################

	function flickr_users_path_aliases_get_by_alias($alias){

		$enc_alias = AddSlashes($alias);

		$sql = "SELECT * FROM FlickrUsersPathAliases WHERE path_alias='{$enc_alias}'";
		return db_single(db_fetch($sql));
	}

	#################################################################

	function flickr_users_path_aliases_for_user(&$user){

		$enc_id = AddSlashes($user['id']);

		$sql = "SELECT * FROM FlickrUsersPathAliases WHERE user_id='{$enc_id}' ORDER BY created DESC";
		return db_fetch($sql);
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
		}

		return $rsp;
	}

	#################################################################

	function flickr_users_path_aliases_update(&$path_alias, &$update){

		$insert = array();

		foreach ($update as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$where = "path_alias='{$enc_alias}'";

		$rsp = db_update('FlickrUsersPathAliases', $update, $where);
		return $rsp;
	}

	#################################################################

	function _flickr_users_path_aliases_redirect_to(&$user, $new_path_alias){

	}

	#################################################################

?>
