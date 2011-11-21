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

		# OMGWTF: When sorting by date_taken|posted the results
		# are basically anything but sorted. It's unclear to me
		# whether this is a known Lucene thing or ... what? I
		# suppose it might make sense to store dates as INTs but
		# then we lose the ability to do date facteing, for calendar
		# pages sometime in the future. So for now we'll just sort
		# by photo ID since it accomplishes the same thing...
		# (20111121/straup)
		#
		# see also: http://phatness.com/2009/11/sorting-by-date-with-solr/

		$params = array(
			'q' => $q,
			'sort' => 'photo_id desc',
		);

		if ($fq = _flickr_photos_places_perms_fq($user, $viewer_id)){
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

	function flickr_photos_places_for_user_facet(&$user, $facet, $viewer_id=0, $more=array()){

		$q = array(
			"photo_owner" => $user['id'],
		);

		$q = solr_utils_hash2query($q, " AND ");

		$params = array(
			'q' => $q,
			"facet" => "on",
			"facet.field" => $facet,
		);

		if ($fq = _flickr_photos_places_perms_fq($user, $viewer_id)){
			$params['fq'] = $fq;
		}

		$rsp = solr_facet($params, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		return $rsp;
	}

	#################################################################

	function _flickr_photos_places_perms_fq(&$user, $viewer_id){

		if (($user['id']) && ($user['id'] == $viewer_id)){
			return;
		}

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

		return $fq;
	}

	#################################################################
?>
