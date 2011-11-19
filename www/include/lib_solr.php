<?php

	#
	# $Id$
	#

	# This is *not* a general purpose wrapper library for talking to Solr.

	#################################################################

	loadlib("http");

	#################################################################

	# Note: this doesn't do any magic with 'q' query parameters yet
	# so you'll need to do that before you get here

	function solr_select($params, $more=array()){

		# defaults (to do: spill)

		$page = isset($more['page']) ? max(1, $more['page']) : 1;
		$per_page = isset($more['per_page']) ? max(1, $more['per_page']) : $GLOBALS['cfg']['pagination_per_page'];

		$start = ($page - 1) * $per_page;
		$rows = $per_page;

		$params['rows'] = $rows;
		$params['start'] = $start;
		$params['wt'] = 'json';

		# build query

		$_params = array();

		foreach ($params as $k => $v){

			$v = (is_array($v)) ? $v : array($v);

			foreach ($v as $_v){
				$_params[] = "$k=" . urlencode($_v);
			}
		}

		$str_params = implode('&', $_params);

		# go!

		$url = $GLOBALS['cfg']['solr_endpoint'] . "select/";

		$http_rsp = http_post($url, $str_params);
		$rsp = _solr_parse_response($http_rsp);

		if (! $rsp['ok']){
			return $rsp;
		}

		# pagination

		$total_count = $rsp['data']['response']['numFound'];
		$page_count = ceil($total_count / $per_page);
		$last_page_count = $total_count - (($page_count - 1) * $per_page);

		$rsp = array(
			'ok' => 1,
			'rows' => $rsp['data']['response']['docs'],
			'pagination' => array(
				'total_count' => $total_count,
				'page' => $page,
				'per_page' => $per_page,
				'page_count' => $page_count,
			)
		);

		# TO DO: put this someplace common (like not here or lib_db)

		if ($GLOBALS['cfg']['pagination_assign_smarty_variable']) {
			$GLOBALS['smarty']->assign('pagination', $rsp['pagination']);
			$GLOBALS['smarty']->register_function('pagination', 'smarty_function_pagination');
		}
		
		return $rsp;
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

		$url = $GLOBALS['cfg']['solr_endpoint'] . "update/json";

		$params = array(
			'commit' => 'true',
			'wt' => 'json',
		);

		$str_params = http_build_query($params);
		$url = implode("?", array($url, $str_params));

		$body = json_encode($docs);

		$headers = array(
			'Content-type' => 'application/json',
		);

		$http_rsp = http_post($url, $body, $headers);

		$rsp = _solr_parse_response($http_rsp);
		return $rsp;
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
