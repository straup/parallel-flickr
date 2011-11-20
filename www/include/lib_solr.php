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

		$rsp = _solr_select($params);

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

	# https://wiki.apache.org/solr/SimpleFacetParameters
	# https://wiki.apache.org/solr/SolrFacetingOverview

	function solr_facet($params, $more=array()){

		$params['rows'] = 0;
		$params['facet'] = "on";

		$params['facet.mincount'] = (isset($more['mincount'])) ? $more['mincount'] : 1;

		# TO DO: pagination...
		$params['facet.limit'] = -1;

		$rsp = _solr_select($params);

		if (! $rsp['ok']){
			return $rsp;
		}

		$fields = $rsp['data']['facet_counts']['facet_fields'];
		$facets = array();

	 	# I suppose at some point we'll need to deal with multiple
		# facets but for now we don't  (20111120/straup)

		$facet = $params['facet.field'];
		$count_facet = count($fields[$facet]) - 1;

		foreach (range(0, $count_facet, 2) as $i){

			$woeid = $fields[$facet][$i];
			$count = $fields[$facet][$i + 1];

			$facets[$woeid] = $count;
		}

		arsort($facets);

		return array(
			'ok' => 1,
			'facets' => $facets,
		);
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

	# this is called by both solr_select and solr_facet

	function _solr_select($params){

		$params['wt'] = 'json';

		$url = $GLOBALS['cfg']['solr_endpoint'] . "select/";
		$str_params = _solr_build_query($params, "stringify");

		$http_rsp = http_post($url, $str_params);
		return _solr_parse_response($http_rsp);
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

	function _solr_build_query(&$params, $stringify=0){

		$query = array();

		foreach ($params as $k => $v){

			$v = (is_array($v)) ? $v : array($v);

			foreach ($v as $_v){
				$query[] = "$k=" . urlencode($_v);
			}
		}

		if ($stringify){
			$query = implode("&", $query);
		}

		return $query;
	}

	#################################################################
?>
