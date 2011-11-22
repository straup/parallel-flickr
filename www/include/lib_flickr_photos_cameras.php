<?php

	loadlib("flickr_photos_search");

	#################################################################

	function flickr_photos_cameras_photos_for_user(&$user, $make, $model='', $more=array()){

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);

		$query = array(
			'photo_owner' => $user['id'],
			'camera_make' => $make,
		);

		if ($model){
			$query['camera_model'] = $model;
		}

		return flickr_photos_search($query, $more);
	}

	#################################################################

	function flickr_photos_cameras_models_for_user(&$user, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);

		$query = array(
			'photo_owner' => $user['id']
		);

		if (isset($more['make'])){
			$query['camera_make'] = $more['make'];
		}

		# what we really want are "pivot facets" but those are not
		# available until solr 4.0; see also:
		# https://wiki.apache.org/solr/SimpleFacetParameters#Pivot_.28ie_Decision_Tree.29_Faceting
 
		$rsp = flickr_photos_search_facet($query, "camera_make", $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$facets = $rsp['facets'];
		$cameras = array();

		foreach ($facets as $make => $count){

			$query['camera_make'] = $make;
			$rsp = flickr_photos_search_facet($query, "camera_model", $more);

			# throw an error?

			if (! $rsp['ok']){
				continue;
			}

			$cameras[$make] = array(
				'total' => $count,
				'models' => $rsp['facets'],
			);
		}

		return array(
			'ok' => 1,
			'cameras' => $cameras,
		);
	}

	#################################################################

?>
