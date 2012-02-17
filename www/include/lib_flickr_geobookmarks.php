<?php

	#################################################################

	function flickr_geobookmarks_add($bookmark){

		$user = users_get_by_id($bookmark['user_id']);
		$cluster_id = $user['cluster_id'];

		$insert = array();

		foreach ($bookmark as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert_users($cluster_id, 'FlickrGeoBookmarks', $insert);

		if ($rsp['ok']){
			$rsp['bookmark'] = $bookmark;
		}

		return $rsp;
	}

	#################################################################

	function flickr_geobookmarks_purge_for_user(&$user){

		$cluster_id = $user['cluster_id'];
		$enc_id = AddSlashes($user['id']);

		$sql = "DELETE FROM FlickrGeoBookmarks WHERE user_id='{$enc_id}'";
		$rsp = db_write_users($cluster_id, $sql);

		return $rsp;
	}

	#################################################################
?>
