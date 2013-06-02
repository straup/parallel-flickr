<?php

	#################################################################

	function api_config_init(){

		$GLOBALS['cfg']['api_server_scheme'] = ($GLOBALS['cfg']['api_require_ssl']) ? 'https' : 'http';
		$GLOBALS['cfg']['api_server_name'] =  parse_url($GLOBALS['cfg']['abs_root_url'], 1);

		# If I have an API specific subdomain/prefix then check to see if I am already
		# running on that host; if not then update the 'api_server_name' config

		if (($GLOBALS['cfg']['api_subdomain']) && (! preg_match("/^{$GLOBALS['cfg']['api_subdomain']}\.(?:.*)/", $GLOBALS['cfg']['api_server_name']))){
			$GLOBALS['cfg']['api_server_name'] = $GLOBALS['cfg']['api_subdomain'] . "." . $GLOBALS['cfg']['api_server_name'];
		}

		# Build the 'api_abs_root_url' based on everything above

		$GLOBALS['cfg']['api_abs_root_url'] = "{$GLOBALS['cfg']['api_server_scheme']}://{$GLOBALS['cfg']['api_server_name']}" . "/";

		# If I have an API specific subdomain/prefix then check to see if I am already
		# running on that host; if I am then update the 'site_abs_root_url' config and
		# use it in your code accordingly.

		if (($GLOBALS['cfg']['api_subdomain']) && (preg_match("/{$GLOBALS['cfg']['api_subdomain']}\.(?:.*)/", $GLOBALS['cfg']['api_server_name']))){
			$GLOBALS['cfg']['site_abs_root_url'] = str_replace("{$GLOBALS['cfg']['api_subdomain']}.", "", $GLOBALS['cfg']['abs_root_url']);
		}

		else {
			$GLOBALS['cfg']['site_abs_root_url'] = $GLOBALS['cfg']['abs_root_url'];
		}

		# Load methods / blessings

		foreach ($GLOBALS['cfg']['api_method_definitions'] as $def){

			try {
				$path = FLAMEWORK_INCLUDE_DIR . "/config_api_{$def}.php";
				include_once($path);
			}

			catch (Exception $e){
				# $msg = $e->getMessage();
				_api_config_freakout_and_die();
			}
		}

		api_config_apply_blessings();
	}

	#################################################################

	function api_config_apply_blessings(){

		foreach ($GLOBALS['cfg']['api']['blessings'] as $api_key => $key_details){

			$blessing_defaults = array();

			foreach (array('hosts', 'tokens', 'environments') as $prop){
				if (isset($key_details[$prop])){
					$blessing_defaults[$prop] = $key_details[$prop];
				}
			}

			if (is_array($key_details['method_classes'])){

				foreach ($key_details['method_classes'] as $class_spec => $blessing_details){

					foreach ($GLOBALS['cfg']['api']['methods'] as $method_name => $method_details){

						if (! $method_details['requires_blessing']){
							continue;
						}

						if (! preg_match("/^{$class_spec}/", $method_name)){
							continue;
						}

						$blessing = array_merge($blessing_defaults, $blessing_details);
						_api_config_apply_blessing($method_name, $api_key, $blessing);
					}
				}
			}

			if (is_array($key_details['methods'])){

				foreach ($key_details['methods'] as $method_name => $blessing_details){

					$blessing = array_merge($blessing_defaults, $blessing_details);
					_api_config_apply_blessing($method_name, $api_key, $blessing);
				}
			}
		}
	}

 	#################################################################

	function _api_config_apply_blessing($method_name, $api_key, $blessing=array()){

		if (! is_array($GLOBALS['cfg']['api']['methods'][$method_name]['blessings'])){
			$GLOBALS['cfg']['api']['methods'][$method_name]['blessings'] = array();
		}

		$GLOBALS['cfg']['api']['methods'][$method_name]['blessings'][$api_key] = $blessing;
	}

	#################################################################

	function api_config_ensure_blessing($method_row, $key_row, $token_row=null){

		if (isset($method_row['requires_blessing'])){

			$blessings = $method_row['blessings'];
			$api_key = $key_row['api_key'];

			if (! isset($blessings[$api_key])){
				api_output_error(403, "Invalid API key");
			}

			$details = $blessings[$api_key];

			if (isset($details['environments'])){

				if (! in_array($GLOBALS['cfg']['environment'], $details['environments'])){
					api_output_error(403, 'Invalid host environment');
				}
			}

			if (isset($details['hosts'])){

				if (! in_array($_SERVER['REMOTE_ADDR'], $details['hosts'])){
					api_output_error(403, "Invalid host: '{$_SERVER['REMOTE_ADDR']}'");
				}
			}

			if (isset($details['tokens'])){

				if (! $token_row){
					api_output_error(403, 'Invalid token');
				}

				if (! in_array($token_row['access_tokens'], $details['tokens'])){
					api_output_error(403, 'Invalid token');
				}
			}
		}

		return 1;
	}

	#################################################################

	function api_config_ensure_role(&$method, &$key, &$token){

		$roles_map = api_keys_roles_map('string keys');
		$roles = array_keys($roles_map);

		if (! is_array($method['requires_role'])){
			return 1;
		}

		foreach ($method['requires_role'] as $r){

			if (in_array($r, $roles)){
				return 1;
			}
		}

		api_output_error(403, "Insufficient permissions for API key");
	}

	#################################################################

	function _api_config_freakout_and_die($reason=null){

		$msg = "The API is currently throwing a temper tantrum. That's not good.";

		if ($reason){
			$msg .= " This is what we know so far: {$reason}.";
		}

		# Because if we're here it's probably because the actual config
		# file is busted (20121026/straup)

		if (! isset($GLOBALS['cfg']['api']['default_format'])){
			$GLOBALS['cfg']['api']['default_format'] = 'json';
		}

		loadlib("api_output");
		loadlib("api_log");

		api_output_error(500, $msg);
		exit();
	}

	#################################################################

	# the end
