<?php

	#################################################################

	function exif_tools_rational2float($value){

		$parts = explode('/', $value);
		$count = count($parts);

		if (! $count){
			$ret = 0;
		}

		else if ($count == 1){
			$ret = $parts[0];
		}

		else {
			$ret = floatval($parts[0]) / floatval($parts[1]);	
		}

		return floatval($ret);
	}

	#################################################################
?>
