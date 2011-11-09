<?php

	#
	# $Id$
	#

	# THIS WILL PROBABLY BE MOVED IN TO FLAMEWORK SOON.
	# (20101109/straup)

	# THIS IS ALSO NOT EVEN NEAR TO BEING FINISHED - SPECIFICALLY
	# THE AUTH-Y BITS AND COOKIE DANCING AND OTHER SECURITY FUN...
	# (20101109/straup)

	#################################################################

	function api_dispatch(){

		#
		# Output formats
		#

		$format = request_str('format');

		if ($format = request_str('format')){

			if (in_array($format, $GLOBALS['cfg']['api']['formats']['valid'])){
				$GLOBALS['cfg']['api']['formats']['current'] = $format;
			}

			else {
				$format = null;
			}
		}

		if (! $format){
			$GLOBALS['cfg']['api']['formats']['current'] = $GLOBALS['cfg']['api']['formats']['default'];
		}

		#
		# Can I get a witness?
		#

		if (! $GLOBALS['cfg']['enable_feature_api']){
			api_output_error(999, 'The API is currently disabled');
		}

		#
		# Is this a valid method?
		#

		$method = request_str('method');

		if (! $method){
			api_output_error(404, 'Method not found');
		}

		if (! isset($GLOBALS['cfg']['api']['methods'][$method])){
			api_output_error(404, 'Method not found');
		}

		$method_row = $GLOBALS['cfg']['api']['methods'][$method];

		if (! $method_row['enabled']){
			api_output_error(404, 'Method not found');
		}

		$lib = $method_row['library'];
		loadlib($lib);

		$method = explode(".", $method);
		$function = $lib . "_" . array_pop($method);

		if (! function_exists($function)){
			api_output_error(404, 'Method not found');
		}

		#
		# Auth-y bits
		#

		if ($method_row['required_login']){
			# Please, to write me...
		}

		#
		# Go!
		#

		call_user_func($function);
		exit();
	}

	#################################################################
?>
