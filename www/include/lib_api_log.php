<?php

	$GLOBALS['cfg']['api_log'] = array();

	########################################################################

	function api_log($data, $dispatch=0){

		if (! features_is_enabled("api_logging")){
			return;
		}

		# We could also use apache_note to store data (serialized to
		# JSON) as we go but since that's really just ... a global
		# variable it seems kind of pointless not to just do this
		# (20121026/straup)

		$GLOBALS['cfg']['api_log'] = array_merge($GLOBALS['cfg']['api_log'], $data);

		if ($dispatch){

			$pid = getmypid();
			$note = json_encode($GLOBALS['cfg']['api_log']);
			error_log("[API][{$pid}] $note");
		}
	}

	########################################################################

	# the end
