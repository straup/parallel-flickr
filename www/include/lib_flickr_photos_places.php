<?php

	loadlib("flickr_places");
	loadlib("flickr_photos_search");

	#################################################################

	function flickr_photos_places_for_user(&$user, &$place, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);
		$more['enforce_geoperms'] = 1;

		if (! flickr_places_is_valid_placetype($place['place_type'])){
			return not_okay("not a valid placetype");
		}

		$query = array(
			"user_id" => $user['id'],
			$place['place_type'] => $place['woeid'],
		);

		if (isset($more['geocontext'])){
			$query['geocontext'] = $more['geocontext'];
		}

		return flickr_photos_search($query, $more);
	}

	#################################################################

	function flickr_photos_places_for_user_facet(&$user, $facet, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);
		$more['enforce_geoperms'] = 1;

		$query = array(
			"user_id" => $user['id'],
		);

		if (isset($more['geocontext'])){
			$query['geocontext'] = $more['geocontext'];
		}

		return flickr_photos_search_facet($query, $facet, $more);
	}

	#################################################################

	function flickr_photos_places_contexts_for_user_and_place(&$user, &$place, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);
		$more['enforce_geoperms'] = 1;

		if (! flickr_places_is_valid_placetype($place['place_type'])){
			return not_okay("not a valid placetype");
		}

		$query = array(
			"user_id" => $user['id'],
			$place['place_type'] => $place['woeid'],
		);

		$map = flickr_photos_geo_context_map();
		$contexts = array();

		foreach ($map as $ctx => $ignore){
			$contexts[$ctx] = 0;
		}

		$rsp = flickr_photos_search_facet($query, 'geocontext', $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		foreach ($rsp['facets'] as $ctx => $count){
			$contexts[$ctx] = $count;
		}

		return okay(array(
			'contexts' => $contexts
		));
	}

	#################################################################
?>
