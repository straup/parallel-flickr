<?php

	#################################################################

	function features_is_enabled($flag){

		$flag = "enable_feature_{$flag}";

		if (! isset($GLOBALS['cfg'][$flag])){
			return 0;
		}

		return $GLOBALS['cfg'][$flag];
	}

	#################################################################
?>
