<?php

 	#################################################################

	function api_spec_utils_example_for_method($method){

		$path = FLAMEWORK_INCLUDE_DIR . "config.api.examples/{$method}.json";

		if (! file_exists($path)){
			return not_okay("no example defined for {$method} method");
		}

		return okay(array(
			'example' => file_get_contents($path)
		));
	}

 	#################################################################

?>
