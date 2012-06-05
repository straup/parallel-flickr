<?php

	#################################################################

	function features_is_enabled($flag){

		if (! preg_match("/^enable_feature_/", $flag)){
			$flag = "enable_feature_{$flag}";
		}

		if (! isset($GLOBALS['cfg'][$flag])){
			return 0;
		}

		return $GLOBALS['cfg'][$flag];
	}

	#################################################################

	function features_ensure_enabled($flag){

		if (! features_is_enabled($flag)){
			error_disabled();
		}
	}

	#################################################################
?>
