<?php

	#################################################################

	function flickr_push_photos_record(&$user, $photo_data){

		$cluster = $user['cluster_id'];

		$photo_data['created'] = time();

		$insert = array();

		foreach ($photo_data as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert_users($cluster, 'FlickrPushPhotos', $insert);

		if ((! $rsp['ok']) && ($rsp['error_code'] == 1062)){

			$enc_sub = AddSlashes($photo_data['subscription_id']);
			$enc_photo = AddSlashes($photo_data['photo_id']);

			$where = "`subscription_id`='{$enc_sub}' AND `photo_id`='{$enc_photo}'";
			$sql = "DELETE FROM `FlickrPushPhotos` WHERE {$where}";

			$rsp = db_write_users($cluster, $sql);

			if ($rsp['ok']){
				$rsp = db_insert_users($cluster, 'FlickrPushPhotos', $insert);
			}
		}

		if ($rsp['ok']){
			$rsp['photo'] = $photo_data;
		}

		return $rsp;
	}

	#################################################################

?>
