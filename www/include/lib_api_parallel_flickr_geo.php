<?php

	loadlib("youarehere_api");
	loadlib("geo_utils");

	#################################################################

	function api_parallel_flickr_geo_reverseGeocode(){

		# feature flags...

		# patch lib_sanitize to have a _float function...

		$lat = request_str("latitude");
		$lon = request_str("longitude");

		if ((! $lat) || (! $lon)){
			api_output_error(999, "Missing latitude or longitude");
		}

		if (! geo_utils_is_valid_latitude($lat)){
			api_output_error(999, "Invalid latitude");
		}

		if (! geo_utils_is_valid_longitude($lon)){
			api_output_error(999, "Invalid longitude");
		}

		# sort out 'filter' type based on accuracy

		$args = array(
			'lat' => $lat,
			'lon' => $lon,
		);

		# cache me

		$rsp = youarehere_api_call('youarehere.geo.reverseGeocode', $args);

		if (! $rsp['ok']){
			api_output_error(999, $rsp['error']);
		}

		api_output_ok($rsp['data']);
	}

	#################################################################

	# the end
