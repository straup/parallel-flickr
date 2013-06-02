<?php

	#################################################################

	# Hey look! Running code!!

	$format = api_output_get_format();

	if (! $format){
		$format = $GLOBALS['cfg']['api']['default_format'];
	}

	loadlib("api_output_{$format}");

	#################################################################

	function api_output_get_format(){

		$format = null;
		$possible = null;
	
		if (request_isset('format')){
			$possible = request_str('format');
		}

		else {

			$headers = getallheaders();

			if (isset($headers['Accept'])){

				foreach (explode(",", $headers['Accept']) as $what){

					list($type, $q) = explode(";", $what, 2);

					if (preg_match("!^application/(\w+)$!", $type, $m)){
						$possible = $m[1];
						break;
					}
				}
			}
		}

		if ($possible){

			if (in_array($possible, $GLOBALS['cfg']['api']['formats'])){
				$format = $possible;
			}
		}

		return $format;
	}

	#################################################################

	# the end
