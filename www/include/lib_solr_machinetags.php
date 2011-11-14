<?php

	#################################################################

	# http://www.aaronland.info/talks/mw10_machinetags/#105

	function solr_machinetags_explode($mt, $add_lazy8s=1){

		list($ns, $rest) = explode(":", $mt, 2);
		list($pred, $value) = explode("=", $rest, 2);

		$parts = array(
			"{$ns}:",
			"{$ns}:{$pred}=",
			"{$ns}:{$pred}={$value}",
			"={$value}",
			":{$pred}=",
			":{$pred}={$value}",
		);

		if ($add_lazy8s){

			$count = count($parts);

			for ($i=0; $i < $count; $i++){
				$parts[$i] = solr_machinetags_add_lazy8s($parts[$i]);
			}
		}

		return $parts;
	}

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
