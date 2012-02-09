<?php

 	#################################################################

	# THIS IS NOT AWESOME. PLEASE MAKE ME BETTER.
	# (ON THE OTHER HAND, IT WORKS...)

	$api_config = FLAMEWORK_INCLUDE_DIR . "config.api.json";
	$fh = fopen($api_config, "r");
	$data = fread($fh, filesize($api_config));
	fclose($fh);

	$GLOBALS['cfg']['api'] = json_decode($data, "as hash");

	#################################################################

	loadlib("api_auth");
	loadlib("api_keys");
	loadlib("api_output");
	loadlib("api_utils");

	#################################################################

	function api_dispatch($method){

		if (! $GLOBALS['cfg']['enable_feature_api']){
			api_output_error(999, 'API disabled');
		}

		$method = filter_strict($method);
		$enc_method = htmlspecialchars($method);

		$methods = $GLOBALS['cfg']['api']['methods'];

		if ((! $method) || (! isset($methods[$method]))){
			api_output_error(404, "Method '{$enc_method}' not found");
		}

		$method_row = $methods[$method];

		if (! $method_row['enabled']){
			api_output_error(404, "Method '{$enc_method}' not found");
		}

		$method_row['name'] = $method;

		# TO DO: check API keys here

		# TO DO: actually check auth here (whatever that means...)

		if ($method_row['requires_auth']){
			api_auth_ensure_auth($method_row);
		}

		if ($method_row['requires_crumb']){
			api_auth_ensure_crumb($method_row);
		}

		loadlib($method_row['library']);

		$parts = explode(".", $method);
		$method = array_pop($parts);

		$func = "{$method_row['library']}_{$method}";
		call_user_func($func);

		exit();
	}

	#################################################################

?>
