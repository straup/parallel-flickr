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

	function flickr_photos_lookup_photo($id){

		$enc_id = AddSlashes($id);

		$sql = "SELECT * FROM FlickrPhotosLookup WHERE photo_id='{$enc_id}'";
		return db_single(db_fetch($sql));
	}

	#################################################################
?>
