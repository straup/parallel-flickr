<?php

	########################################################################

	function youarehere_api_call($method, $args, $more=array()){

		$defaults = array(
			'endpoint' => $GLOBALS['cfg']['youarehere_api_endpoint'],
			'access_token' => $GLOBALS['cfg']['youarehere_api_access_token'],
		);

		$more = array_merge($defaults, $more);

		$url = $more['endpoint'];

		$args['method'] = $method;
		$args['access_token'] = $more['access_token'];

		$rsp = http_post($url, $args);

		if (! $rsp['ok']){
			return $rsp;
		}

		$data = json_decode($rsp['body'], 'as hash');

		if (! $data){
			$rsp['ok'] = 0;
			$rsp['error'] = 'failed to parse JSON';
			return $rsp;
		}

		$rsp['data'] = $data;
		return $rsp;
	}

	########################################################################

	# the end

