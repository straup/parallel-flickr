<?php

	#################################################################

	function flickr_faves_for_user(&$user, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);

		$cluster_id = $user['cluster_id'];
		$enc_user = AddSlashes($user['id']);

		# TO DO: PERMISSIONS
		$extra = "";

		# TO DO: INDEXES

		$sql = "SELECT * FROM FlickrFaves WHERE user_id='{$enc_user}'";

		if ($owner = $more['by_owner']){
			$enc_owner = AddSlashes($owner['id']);
			$sql .= " AND owner_id='{$enc_owner}'";
		}

		$sql .= " {$extra} ORDER BY date_faved DESC";

		return db_fetch_paginated_users($cluster_id, $sql, $more);
	}

	#################################################################

	function flickr_faves_add_fave(&$viewer, &$photo, $date_faved=0){

		if (! $date_faved){
			$date_faved = time();
		}

		$cluster_id = $viewer['cluster_id'];

		$fave = array(
			'user_id' => $viewer['id'],
			'photo_id' => $photo['id'],
			'owner_id' => $photo['user_id'],
			'date_faved' => $date_faved,
		);

		$insert = array();

		foreach ($fave as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert_users($cluster_id, 'FlickrFaves', $insert);

		if (! $rsp['ok']){
			return $rsp;
		}

		# now update the photo owner side of things

		$owner = users_get_by_id($photo['user_id']);

		$cluster_id = $owner['cluster_id'];

		$fave = array(
			'user_id' => $owner['id'],
			'photo_id' => $photo['id'],
			'viewer_id' => $viewer['id'],
		);

		$insert = array();

		foreach ($fave as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert_users($cluster_id, 'FlickrFavesUsers', $insert);

		if (! $rsp['ok']){
			return $rsp;
		}

		# TO DO: index/update the photo in solr and insert $viewer['id']
		# into the faved_by column (20111123/straup)

		return array(
			'ok' => 1
		);
	}

	#################################################################
?>
