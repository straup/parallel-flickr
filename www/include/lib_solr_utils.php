<?php

	#################################################################

	function solr_utils_hash2query(&$hash, $join=null){

		$q = array();

		foreach ($hash as $k => $v){

			$k = urlencode($k);

			$v = (preg_match("/^raw:(.*)$/", $v, $m)) ? $m[1] : solr_utils_escape($v);

			$q[] = "{$k}:{$v}";
		}

		if ($join){
			$q = implode($join, $q);
		}

		return $q;
	}

	#################################################################

	# http://e-mats.org/2010/01/escaping-characters-in-a-solr-query-solr-url/

        function solr_utils_escape($string){

		$match = array('\\', '+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '"', ';', ' ');
		$replace = array('\\\\', '\\+', '\\-', '\\&', '\\|', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\~', '\\*', '\\?', '\\:', '\\"', '\\;', '\\ ');
		$string = str_replace($match, $replace, $string);
		return $string;
	}

	#################################################################
?>
