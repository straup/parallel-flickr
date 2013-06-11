<?php

	#################################################################

	function flickr_photos_lookup_add($photo_id, $user_id){

		$lookup = array(
			'photo_id' => $photo_id,
			'user_id' => $user_id,
		);

		$insert = array();

		foreach ($lookup as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert('FlickrPhotosLookup', $insert);

		if ($rsp['ok']){
			$rsp['lookup'] = $lookup;
		}

		return $rsp;
	}

	#################################################################

	function flickr_photos_lookup_photo($id, $more=array()){

		# sudo find me a better name...

		$defaults = array(
			'allow_deleted' => 0
		);

		$more = array_merge($defaults, $more);

		$enc_id = AddSlashes($id);

		$sql = "SELECT * FROM FlickrPhotosLookup WHERE photo_id='{$enc_id}'";
		$rsp = db_fetch($sql);
		$row = db_single($rsp);

		return ((! $row['deleted']) || ($more['allow_deleted'])) ? $row : null;
	}

	#################################################################

	function flickr_photos_lookup_delete(&$lookup){

		$update = array(
			'deleted' => time(),
		);

		return flickr_photos_lookup_update($lookup, $update);
	}

	#################################################################

	function flickr_photos_lookup_update(&$lookup, $update){

		$hash = array();

		foreach ($update as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$enc_id = AddSlashes($lookup['photo_id']);
		$where = "photo_id='{$enc_id}'";

		$rsp = db_update('FlickrPhotosLookup', $hash, $where);

		if ($rsp['ok']){
			$lookup = array_merge($lookup, $update);
			$rsp['lookup'] = $lookup;
		}

		return $rsp;
	}

	#################################################################

	# the end
