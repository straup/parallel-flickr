<?php

	#
	# $Id$
	#

	# This is *not* a general purpose wrapper library for talking to Solr.

	#################################################################

	loadlib("http");

	#################################################################

	function solr_select($params, $more=array()){

		$url = $GLOBALS['cfg']['solr_endpoint'] . "select/";

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

		$http_rsp = http_post($url, $str_params);

		return _solr_parse_response($http_rsp);
	}

	#################################################################

	function solr_facet($params, $more=array()){

		$rsp = solr_select($params, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		# please write me...
	}

	#################################################################

	# https://wiki.apache.org/solr/UpdateJSON

	function solr_add($docs, $more=array()){

		$url = $GLOBALS['cfg']['solr_endpoint'] . "update/json?commit=true";

		$body = json_encode($docs);

		$http_rsp = http_post($url, $body);

		return _solr_parse_response($http_rsp);
	}

	#################################################################

	function solr_delete(){

		# please write me
	}

	#################################################################

	function _solr_parse_response($http_rsp){

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

		return $rsp;
	}

	#################################################################
?>
