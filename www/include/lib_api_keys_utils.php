<?php

	loadlib("api_throttle");

	########################################################################

	# This is meant to be invoked in a browser context

	function api_keys_utils_get_from_url($more=array()){

		$defaults = array(
			'allow_disabled' => 0,
			'ensure_isown' => 1,
		);

		$more = array_merge($defaults, $more);

		$api_key = request_str("api_key");

		# OAuth2 section 2.2 ...

		if (! $api_key){
			$api_key = request_str("client_id");
		}

		if (! $api_key){
			error_404();
		}

		$key_row = api_keys_get_by_key($api_key);

		if (! $key_row){
			error_404();
		}

		if ($key_row['deleted']){
			error_410();
		}

		if ($more['ensure_isown']){

			if ($key_row['user_id'] != $GLOBALS['cfg']['user']['id']){
				error_403();
			}
		}

		if (! $more['allow_disabled']){

			if ($key_row['disabled']){
				error_403();
			}
		}

		return $key_row;
	}

	########################################################################

	function api_keys_utils_is_valid_callback($url){

		$parts = parse_url($url);

		if (! isset($parts['scheme'])){
			return 0;
		}

		if (! isset($parts['host'])){
			return 0;
		}

		return 1;
	}

	########################################################################

	function api_keys_utils_is_valid_key($key_row){

		if (! $key_row){
			return array('ok' => 0, 'error' => 'Unknown API key');
		}

		if ($key_row['deleted']){
			return array('ok' => 0, 'error' => 'Invalid API key');
		}

		if ($key_row['disabled']){
			return array('ok' => 0, 'error' => 'API key is disabled');
		}

		if ((features_is_enabled("api_throttling")) && (api_throttle_is_key_throttled($key_row))){
			return array('ok' => 0, 'error' => 'API key is throttled');
		}

		return array('ok' => 1);
	}

	########################################################################

	# This is meant to be invoked in an API context

	function api_keys_ensure_valid_key($key_row){

		$rsp = api_keys_utils_is_valid_key($key_row);

		if (! $rsp['ok']){
			api_output_error($rsp['error_code'], $rsp['error']);
		}

		return 1;
	}

	########################################################################

	# the end
