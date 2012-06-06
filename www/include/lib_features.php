<?php

	#################################################################

	function features_is_enabled($flags){

		if (! is_array($flags)){
			$flags = array($flags);
		}

		foreach ($flags as $flag){

			if (! preg_match("/^enable_feature_/", $flag)){
				$flag = "enable_feature_{$flag}";
			}

			if (! isset($GLOBALS['cfg'][$flag])){
				return 0;
			}
		}

		return $GLOBALS['cfg'][$flag];
	}

	#################################################################

	function features_ensure_enabled($flags){

		if (! features_is_enabled($flags)){
			error_disabled();
		}
	}

	#################################################################
?>
