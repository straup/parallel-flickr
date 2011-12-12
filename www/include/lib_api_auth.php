<?php

	#################################################################

	function api_auth_ensure_auth(){

		if (! api_auth_has_auth()){
			api_output_error(403, 'Forbidden');
		}
	}

	#################################################################

	function api_auth_has_auth(){

		# please write me...

		return 0;
	}

	#################################################################
?>
