<?php

	#################################################################

	function api_auth_ensure_auth(&$method, $key_row=null){

		$type = $GLOBALS['cfg']['api_auth_type'];

		$auth_lib = "api_auth_{$type}";
		$auth_func = "api_auth_{$type}_has_auth";

		try {
			loadlib($auth_lib);
		}

		catch (Exception $e){
			return 0;
		}

		if (! function_exists($auth_func)){
			return 0;
		}

		$rsp = call_user_func_array($auth_func, array($method, $key_row));

		if (! $rsp['ok']){
			api_output_error($rsp['error_code'], $rsp['error']);
		}

		return $rsp;
	}

	#################################################################

	function api_auth_ensure_crumb(&$method, $ttl=0){

		if (! api_auth_has_valid_crumb($method, $ttl)){
			api_output_error(999, "Missing or invalid crumb");
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

	# the end
