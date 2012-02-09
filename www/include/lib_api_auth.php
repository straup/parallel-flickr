<?php

	#################################################################

	function api_auth_ensure_auth(&$method){

		if (! api_auth_has_auth($method)){
			api_output_error(403, 'Forbidden');
		}
	}

	#################################################################

	function api_auth_has_auth(&$method){

		return ($GLOBALS['cfg']['user']['id']) ? 1 : 0;

		# please write me...

		return 0;
	}

	#################################################################

	function api_auth_ensure_crumb(&$method, $ttl=0){

		if (! api_auth_has_valid_crumb($method, $ttl)){
			api_output_error(410, "Missing or invalid crumb");
		}
	}

	#################################################################

	function api_auth_has_valid_crumb(&$method, $ttl=0){

		$crumb = request_str("crumb");

		if (! $crumb){
			return 0;
		}

		$name = $method['name'];
		$ttl = (isset($method['crumb_ttl'])) ? $method['crumb_ttl'] : 0;

		if (! crumb_check("api", $ttl, $name)){
			return 0;
		}

		return 1;
	}

	#################################################################

?>
