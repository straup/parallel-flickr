<?php

	loadlib("api_oauth2_access_tokens");

	#################################################################

	function api_auth_oauth2_get_access_token(&$method){

		# https://tools.ietf.org/html/draft-ietf-oauth-v2-bearer-20#section-2.1

		if ($GLOBALS['cfg']['api_oauth2_require_authentication_header']){

			$headers = apache_request_headers();

			if (! isset($headers['Authorization'])){
				return null;
			}

			if (! preg_match("/Bearer\s+([a-zA-Z0-9\+\/]+)$/", $headers['Authorization'], $m)){
				return null;
			}

			$token = $m[1];
			$token = base64_decode($token);

			return $token;
		}

		if ($GLOBALS['cfg']['api_oauth2_allow_get_parameters']){
			return request_str('access_token');
		}

		return post_str('access_token');
	}

	#################################################################

	function api_auth_oauth2_has_auth(&$method, $key_row=null){

		$access_token = api_auth_oauth2_get_access_token($method);

		if (! $access_token){
			return array('ok' => 0, 'error' => 'Required access token missing', 'error_code' => 400);
		}

		$token_row = api_oauth2_access_tokens_get_by_token($access_token);

		if (! $token_row){
			return array('ok' => 0, 'error' => 'Invalid access token', 'error_code' => 400);
		}

		if (($token_row['expires']) && ($token_row['expires'] < time())){
			return array('ok' => 0, 'error' => 'Access token has expired', 'error_code' => 400);
		}

		# I find it singularly annoying that we have to do this here
		# but OAuth gets what [redacted] wants. See also: notes in
		# lib_api.php around ln 65 (20121026/straup)

		$key_row = api_keys_get_by_id($token_row['api_key_id']);
		$rsp = api_keys_utils_is_valid_key($key_row);

		if (! $rsp['ok']){
			return $rsp;
		}

		if (isset($method['requires_perms'])){

			if ($token_row['perms'] < $method['requires_perms']){
				return array('ok' => 0, 'error' => 'Insufficient permissions', 'error_code' => 403);
			}
		}

		# Ensure user-iness - this may seem like a no-brainer until you think
		# about how the site itself uses the API in the absence of a logged-in
		# user (20130508/straup)

		$ensure_user = 1;
		$user = null;

		if (features_is_enabled("api_site_keys", "api_site_tokens")){

			# check that API key is a site key
			$ensure_user = ($token_row['user_id']) ? 1 : 0;
		}

		if ($ensure_user){

			$user = users_get_by_id($token_row['user_id']);

			if ((! $user) || ($user['deleted'])){
				return array('ok' => 0, 'error' => 'Not a valid user', 'error_code' => 400);
			}
		}

		#

		return array(
			'ok' => 1,
			'access_token' => $token_row,
			'api_key' => $key_row,
			'user' => $user,
		);
	}

	#################################################################

	# the end
