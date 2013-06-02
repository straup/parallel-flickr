<?php

 	#################################################################

	function api_spec_utils_example_for_method($method){

		$fname = str_replace(".", "_", $method);
		$template = "page_api_example_{$fname}.json";

		if (! $GLOBALS['smarty']->template_exists($template)){
			return array('ok'=> 0, 'error' => 'no example defined for {$method} method');
		}

		return array(
			'ok' => 1,
			'example' => $GLOBALS['smarty']->fetch($template),
		);
	}

 	#################################################################

	# the end
