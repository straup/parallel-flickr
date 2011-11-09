<?php

	#################################################################

	function flickr_contacts_relationship_map($string_keys=0){

		$map = array(
			1 => 'contact',
			2 => 'friends',
			3 => 'family',
			4 => 'friends and family',
		);

		if ($string_keys){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	function flickr_contacts_add_contact($contact){

		$user = users_get_by_id($contact['user_id']);
		$cluster_id = $user['cluster_id'];

		$insert = array();

		foreach ($contact as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert_users($cluster_id, 'FlickrContacts', $insert);

		# check for duplicate insert here

		if (! $rsp['ok']){
			return null;
		}

		return $contact;
	}

	#################################################################

	function flickr_contacts_get_contact($user_id, $contact_id){

		$user = users_get_by_id($user_id);
		$cluster_id = $user['cluster_id'];

		$enc_user = AddSlashes($user_id);
		$enc_contact = AddSlashes($contact_id);

		$sql = "SELECT * FROM FlickrContacts WHERE user_id='{$enc_user}' AND contact_id='{$enc_contact}'";
		return db_single(db_fetch_users($cluster_id, $sql));
	}

	#################################################################

	function flickr_contacts_for_user(&$user, $more=array()){

		$cluster_id = $user['cluster_id'];
		$enc_user = AddSlashes($user['id']);

		# FIX ME: dates for when the relationship was created
		# ...or at least some sort of ordering

		# FIX ME: photo count

		$sql = "SELECT * FROM FlickrContacts WHERE user_id='{$enc_user}'";

		return db_fetch_paginated_users($cluster_id, $sql, $more);
	}

	#################################################################
?>
