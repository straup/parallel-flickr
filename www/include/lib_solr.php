<?php

	#
	# $Id$
	#

	# This is *not* a general purpose wrapper library for talking to Solr.
	# This is the just the stuff to wrap arguments in to a POST string and
	# to json_decodify the results when they come back. It assumes you've
	# already loaded Flamework's lib_http by the time you get here.

	#################################################################

	function solr_select($url, $params=array(), $more=array()){

		$params['wt'] = 'json';

		$_params = array();

		foreach ($params as $k => $v){

			$v = (is_array($v)) ? $v : array($v);

			foreach ($v as $_v){
				$_params[] = "$k=" . urlencode($_v);
			}
		}

		$str_params = implode('&', $_params);

		#

		if (function_exists('cache_get')){

			$cache_key = "solr_select_" . md5($str_params);
			$cache = cache_get($cache_key);

			if ($cache['ok']){
				return $cache['data'];
			}
		}

		#

		$http_rsp = http_post($url, $str_params);

		if (! $http_rsp['ok']){
			return $http_rsp;
		}

		$json = json_decode($http_rsp['body'], "as a hash");

		if (! $json){
			return array(
				'ok' => 0,
				'error' => 'Failed to parse response',
			);
		}

		$rsp = array(
			'ok' => 1,
			'data' => $json,
		);

		if (function_exists('cache_set')){
			cache_set($cache_key, $rsp);
		}

		return $rsp;
	}

	#################################################################
?>
