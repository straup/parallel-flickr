<?php

	#################################################################

	function flickr_photos_geo_corrections_create($correction){

		$user = users_get_by_id($correction['user_id']);

		if (! $user['id']){
			return not_okay("Invalid user ID");
		}

		$cluster_id = $user['cluster_id'];

		$correction['created'] = time();

		$insert = array();
	
		foreach ($correction as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert_users($cluster_id, 'FlickrPhotosGeoCorrections', $insert);

		if ($rsp['ok']){
			$rsp['correction'] = $correction;
		}

		return $rsp;
	}

	#################################################################
?>
