<?php

	loadlib("flickr_photos_search");

	#################################################################

	# this one is still being worked out...

	function flickr_photos_cameras_for_user(&$user, $viewer_id=0, $make, $model='', $more=array()){

		$query = array(
			'photo_owner' => $user['id']
			'camera_make' => $make,
		);

		if ($model){
			$query['camera_model'] = $model;
		}

		$rsp = flickr_photos_search($query, $viewer_id, $more);
		return $rsp;
	}

	#################################################################

	# TO DO: allow $make or just use a separate function?

	function flickr_photos_cameras_for_user(&$user, $viewer_id=0, $more=array()){

		$query = array(
			'photo_owner' => $user['id']
		);

		# what we really want are "pivot facets" but those are not
		# available until solr 4.0; see also:
		# https://wiki.apache.org/solr/SimpleFacetParameters#Pivot_.28ie_Decision_Tree.29_Faceting
 
		$rsp = flickr_photos_search_facet($query, "camera_make", $viewer_id);

		if (! $rsp['ok']){
			return $rsp;
		}

		$facets = $rsp['facets'];
		$cameras = array();

		foreach ($facets as $make => $count){

			$query['camera_make'] = $make;
			$rsp = flickr_photos_search_facet($query, "camera_model", $viewer_id);

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
