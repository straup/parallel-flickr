<?php

	loadlib("api_spec_utils");

	# See also:
	# http://blog.linode.com/2012/04/04/api_spec/

 	#################################################################

	function api_spec_formats(){

		api_output_ok(array(
			'formats' => $GLOBALS['cfg']['api']['formats'],
			'default_format' => $GLOBALS['cfg']['api']['default_format']
		));
	}

 	#################################################################

	function api_spec_methods(){

		$export_keys = array(
			'method',
			'description',
			'requires_auth',
			'parameters',
			'errors',
			'notes',
			'example',
		);

		$defaults = array(
			'method' => 'GET',
			'requires_auth' => 0,
			'description' => '',
			'parameters' => array(),
			'errors' => array(),		
		);

		$methods = array();

		foreach ($GLOBALS['cfg']['api']['methods'] as $name =>$details){

			if (! $details['enabled']){
				continue;
			}

			if (! $details['documented']){
				continue;
			}

			$details = array_merge($defaults, $details);

			$method = array(
				'name' => $name,
			);

			foreach ($export_keys as $k){

				if (! isset($details[$k])){
					continue;
				}

				$v = $details[$k];
				$method[$k] = $v;
			}

			/*
			$rsp = api_spec_utils_example_for_method($name);

			if ($rsp['ok']){
				$method['example'] = $rsp['example'];
			}
			*/

			$methods[] = $method;
		}

		api_output_ok(array(
			'methods' => $methods
		));

	}

 	#################################################################

	# the end
