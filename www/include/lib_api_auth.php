<?php

	#################################################################

	function api_auth_ensure_auth(){

		if (! api_auth_has_auth()){
			api_output_error(403, 'Forbidden');
		}
	}

	#################################################################

	function api_auth_has_auth(){

		# hey look... it's cheap and dirty cookie auth

		if ($GLOBALS['cfg']['user']['id']){
			return 1;
		}

		return 0;
	}

	#################################################################
?>
