<?php

	loadlib("solr");
	loadlib("solr_utils");

	loadlib("flickr_places");

	loadlib("flickr_photos");
	loadlib("flickr_photos_permissions");
	loadlib("flickr_geo_permissions");

	#################################################################

	function flickr_photos_places_for_user(&$user, &$place, $viewer_id=0, $more=array()){

		if (! flickr_places_is_valid_placetype($place['place_type'])){
			return not_ok("not a valid placetype");
		}

		$q = array(
			"photo_owner" => $user['id'],
			$place['place_type'] => $place['woeid'],
		);

		$q = solr_utils_hash2query($q, " AND ");

		# THIS IS NOT AWESOME. PERMISSIONS IN SOLR SHOULD
		# PROBABLY JUST ALL BE PRE-COMPUTED AND STORED THE
		# SAME WAY MACHINETAGS ARE.... (20111119/straup)

		$fq = array();

		if ($perms = flickr_photos_permissions_photos_where($user['id'], $viewer_id)){

			$count = count($perms);

			for ($i=0; $i < $count; $i++){
				$perms[$i] = "photo_perms:" . urlencode($perms[$i]);
			}

			$fq[] = implode(" OR ", $perms);
		}

		if ($perms = flickr_geo_permissions_photos_where($user['id'], $viewer_id)){

			$count = count($perms);

			for ($i=0; $i < $count; $i++){
				$perms[$i] = "geo_perms:" . urlencode($perms[$i]);
			}

			$fq[] = implode(" OR ", $perms);
		}

		$params = array(
			'q' => $q,
			'rows' => 10,
			# TO DO: figure out why this results in ordering weirdness...
			'sort' => 'date_taken desc',
		);

		if (count($fq)){
			$params['fq'] = $fq;
		}

		$rsp = solr_select($params, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$photos = array();

		foreach ($rsp['rows'] as $row){
			$photo = flickr_photos_get_by_id($row['photo_id']);

			# TO DO: make sure this is safe; it should be by the
			# time we get here (20111119/straup)

			$photo['can_view_geo'] = 1;
			$photos[] = $photo;
	
			# TO DO: solr properties?	
		}

		$rsp['rows'] = $photos;
		return $rsp;
	}

	#################################################################
?>
