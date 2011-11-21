<?php

	loadlib("solr");
	loadlib("solr_utils");

	loadlib("flickr_photos");
	loadlib("flickr_photos_permissions");

	#################################################################

	function flickr_photos_cameras_for_user_facet(&$user, $facet, $viewer_id=0, $more=array()){

		$q = array(
			"photo_owner" => $user['id'],
		);

		$q = solr_utils_hash2query($q, " AND ");

		$params = array(
			'q' => $q,
			"facet" => "on",
			"facet.field" => $facet,
		);

		if ($fq = _flickr_photos_cameras_perms($user, $viewer_id)){
			$params['fq'] = $fq;
		}

		$rsp = solr_facet($params, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		return $rsp;
	}

	#################################################################

	function _flickr_photos_cameras_perms_fq(&$user, $viewer_id){

		if (($user['id']) && ($user['id'] == $viewer_id)){
			return;
		}

		$fq = array();

		if ($perms = flickr_photos_permissions_photos_where($user['id'], $viewer_id)){

			$count = count($perms);

			for ($i=0; $i < $count; $i++){
				$perms[$i] = "photo_perms:" . urlencode($perms[$i]);
			}

			$fq[] = implode(" OR ", $perms);
		}

		return $fq;
	}

	#################################################################
?>
