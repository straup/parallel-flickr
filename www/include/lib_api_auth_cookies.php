<?php

	#################################################################

	function api_auth_cookies_has_auth(&$method, $key_row=null){

		$ok = ($GLOBALS['cfg']['user']['id']) ? 1 : 0;

		if (! $ok){
			return array('ok' => 0, 'error' => 'Invalid user', 'error_code' => 400);
		}

		if (isset($method['requires_perms'])){

			if ($method['requires_perms'] != 0){
				return array('ok' => 0, 'error' => 'Insufficient permissions', 'error_code' => 403);
			}
		}

		return array(
			'ok' => 1,
			'user' => $GLOBALS['cfg']['user'],
		);
	}

	#################################################################

	# the end
