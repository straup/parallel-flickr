<?php

	#################################################################

	# Hey look! Running code!!

	$format = $GLOBALS['cfg']['api']['default_format'];

	if ($_format = get_str('format')){

		if (in_array($_format, $GLOBALS['cfg']['api_valid_formats'])){
			$format = $_format;
		}
	}

	loadlib("api_output_{$format}");

	#################################################################
?>
