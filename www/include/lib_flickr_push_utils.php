<?php

	#################################################################

	# Yes, this makes me cry too...
	# (20120605/straup)

	function flickr_push_utils_item2spr(&$item){

		# Also, this is not finished yet

		$srp = array(
			'id' => 0,
			'owner' => $item['flickr']['author_nsid'],
			'secret' => '',
			'server' => '',
			'title' => $item['title'],
			'ispublic' => 0,
			'isfriend' => 0,
			'isfamily' => 0,
			'originalsecret' => '',
			'tags' => $item['media']['category'],
			'media' => '',
			'mediastatus' => '',
			'dateupload' => strtotime($item['issued']),
			'datetaken' => strtotime($item['flickr']['date_taken']),
			'datetakengranularity' => 0,
			'latitude' => 0,
			'longitude' => 0,
			'accuracy' => 0,
			'context' => 0,
			'place_id' => '',
			'woeid' => 0,
			'geo_is_family' => 0,
			'geo_is_friend' => 0,
			'geo_is_contact' => 0,
			'geo_is_public' => 0,
		);

		preg_match("!^tag:flickr.com,2005:/photo/(\d+)$!", $item['id'], $m);
		$spr['id'] = $m[1];

		if (isset($item['geo'])){
			$spr['latitude'] = $item['geo']['lat'];
			$spr['longitude'] = $item['geo']['long'];
		}

		if (isset($item['woe'])){
			$spr['woeid'] = $item['woe']['woeid'];
		}

		return $srp;
	}

	#################################################################
?>
