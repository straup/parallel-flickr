<?php

	#################################################################

	# note: it may be the case that we are calling this function after
	# a user has faved a photo (either on flickr or, more likely, by
	# calling the local flickr.favorites.add API method from one of the
	# push pages) but before the backup_faves.php script has been run;
	# it's not been decided how to handle this yet (20120108/straup)

	function flickr_faves_is_faved_by_user(&$user, $photo_id){

		$cluster_id = $user['cluster_id'];
		$enc_user = AddSlashes($user['id']);
		$enc_photo = AddSlashes($photo_id);

		$sql = "SELECT photo_id FROM FlickrFaves WHERE photo_id='{$enc_photo}' AND user_id='{$enc_user}'";
		$rsp = db_single(db_fetch_users($cluster_id, $sql));

		return ($rsp) ? 1 : 0;
	}

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

	function flickr_faves_count_for_user(&$user, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);

		$cluster_id = $user['cluster_id'];
		$enc_user = AddSlashes($user['id']);

		# TO DO: perms

		$sql = "SELECT COUNT(photo_id) AS cnt FROM FlickrFaves WHERE user_id='{$enc_user}' {$extra}";
		$row = db_single(db_fetch_users($cluster_id, $sql));

		return $row['cnt'];
	}

	#################################################################

	# TO DO: either suppress the 1062 errors or fetch the various
	# rows below. It's not really a big deal except that the errors
	# get spewed to STDOUT and are confusing and a bit angst-making
	# (20120105/straup)

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

		if ((! $rsp['ok']) && ($rsp['error_code'] != 1062)){
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

		if ((! $rsp['ok']) && ($rsp['error_code'] != 1062)){
			return $rsp;
		}

		# TO DO: index/update the photo in solr and insert $viewer['id']
		# into the faved_by column (20111123/straup)

		return okay();
	}

	#################################################################
?>
