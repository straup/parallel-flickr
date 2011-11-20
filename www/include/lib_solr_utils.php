<?php

	#################################################################

	function solr_utils_hash2query(&$hash, $join=null){

		$q = array();

		foreach ($hash as $k => $v){

			$k = urlencode($k);

			# allow foo:[* TO *] queries

			$v = (preg_match("/^raw:(.*)$/", $v, $m)) ? $m[1] : urlencode($v);

			$q[] = "{$k}:{$v}";
		}

		if ($join){
			$q = implode($join, $q);
		}

		return $q;
	}

	#################################################################
?>
