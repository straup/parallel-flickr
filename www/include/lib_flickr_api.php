<?php

	#
	# $Id$
	#

	loadlib("http");

	#################################################################

	$GLOBALS['cfg']['flickr_api_endpoint'] = 'http://api.flickr.com/services/rest/';
	$GLOBALS['cfg']['flickr_auth_endpoint'] = 'http://api.flickr.com/services/auth/';

	#################################################################

	function flickr_api_auth_url($perms, $extra=null){

		$args = array(
			'api_key' => $GLOBALS['cfg']['flickr_api_key'],
			'perms' => $perms,
		);

		if ($extra){

			$extra = http_build_query($extra);
			$args['extra'] = $extra;
		}

		$api_sig = _flickr_api_sign_args($args);
		$args['api_sig'] = $api_sig;

		$url = $GLOBALS['cfg']['flickr_auth_endpoint'] . "?" . http_build_query($args);
		return $url;
	}

	#################################################################

	function flickr_api_call_build($method, $args=array(), $more=array()){

		$args['api_key'] = $GLOBALS['cfg']['flickr_api_key'];

		$args['method'] = $method;
		$args['format'] = 'json';
		$args['nojsoncallback'] = 1;

		if ((isset($args['auth_token'])) || (isset($more['sign']))){
			$api_sig = _flickr_api_sign_args($args);
			$args['api_sig'] = $api_sig;
		}

		$url = $GLOBALS['cfg']['flickr_api_endpoint'];

		return array($url, $args);
	}

	#################################################################

	function flickr_api_call($method, $args=array(), $more=array()){

		list($url, $args) = flickr_api_call_build($method, $args, $more);

		$more = array(
			'http_timeout' => 10,
		);

		$headers = array();

		$rsp = http_post($url, $args, $headers, $more);

		# $url = $url . "?" . http_build_query($args);
		# $rsp = http_get($url);

		if (! $rsp['ok']){
			return $rsp;
		}

		$json = json_decode($rsp['body'], 'as a hash');

		if (! $json){
			return array( 'ok' => 0, 'error' => 'failed to parse response' );
		}

		if ($json['stat'] != 'ok'){
			return array( 'ok' => 0, 'error' => $json['message']);
		}

		unset($json['stat']);
		return array( 'ok' => 1, 'rsp' => $json );
	}

	#################################################################

	function _flickr_api_sign_args($args){

		$parts = array(
			$GLOBALS['cfg']['flickr_api_secret']
		);

		$keys = array_keys($args);
		sort($keys);

		foreach ($keys as $k){
			$parts[] = $k . $args[$k];
		}

		$raw = implode("", $parts);
		return md5($raw);
	}

	#################################################################
?>
