<?php

	#################################################################

	function solr_machinetags_add_lazy8s($str){
		$str = preg_replace("/8/", "88", $str);
		$str = preg_replace("/:/", "8c", $str);
		$str = preg_replace("/\//", "8s", $str);
		return $str;
	}

	#################################################################

	function solr_machinetags_remove_lazy8s($str){

		$str = preg_replace("/8s/", "/", $str);
		$str = preg_replace("/8c/", ":", $str);
		$str = preg_replace("/88/", "8", $str);

		return $str;
	}

	#################################################################

?>
