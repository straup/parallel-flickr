<?php

	#################################################################

	function flickr_photos_geo_is_valid_context($context){

		$map = flickr_photos_geo_context_map();
		return (isset($map[$context])) ? 1 : 0;
	}

	#################################################################

	function flickr_photos_geo_context_map($string_keys=0){

		$map = array(
			'0' => 'not defined',
			'1' => 'indoors',
			'2' => 'outdoors',
		);

		if ($string_keys){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################
?>
