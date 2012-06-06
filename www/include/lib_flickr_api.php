<?php

	#
	# $Id$
	#

	loadlib("http");

	#################################################################

	$GLOBALS['cfg']['flickr_api_endpoint'] = 'http://api.flickr.com/services/rest/';
	$GLOBALS['cfg']['flickr_auth_endpoint'] = 'http://api.flickr.com/services/auth/';

	#################################################################

	function flickr_api_authtoken_perms_map($string_keys=0){

		$map = array(
			'0' => 'read',
			'1' => 'write',
			'2' => 'delete',
		);

		if ($string_keys){
			$map = array_flip($map);
		}

		return $map;
	}

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

		$defaults = array(
			'http_timeout' => 10,
		);

		$more = array_merge($defaults, $more);

		$headers = array();

		$rsp = http_post($url, $args, $headers, $more);

		# $url = $url . "?" . http_build_query($args);
		# $rsp = http_get($url);

		return flickr_api_parse_response($rsp);
	}

	#################################################################

	function flickr_api_parse_response($rsp){

		if (! $rsp['ok']){
			return $rsp;
		}

		if (isset($more['raw'])){
			return $rsp;
		}

		$json = json_decode($rsp['body'], 'as a hash');

		if (! $json){
			return array( 'ok' => 0, 'error' => 'failed to parse response' );
		}

		if ($json['stat'] != 'ok'){
			return array( 'ok' => 0, 'error' => $json['message'], 'error_code' => $json['code']);
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
