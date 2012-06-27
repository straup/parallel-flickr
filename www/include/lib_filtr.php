<?php

	#################################################################

	function filtr_valid_filtrs(){

		if (! is_array($GLOBALS['cfg']['filtr_valid_filtrs'])){
			return array();
		}

		return $GLOBALS['cfg']['filtr_valid_filtrs'];
	}

	#################################################################

	function filtr_is_valid_filtr($filtr){

		$valid = filtr_valid_filtrs();
		return (in_array($filtr, $valid)) ? 1 : 0;
	}

	#################################################################

	function filtr($filtr, $files, $more=array()){

		# See this? Not a feature..
		$src = $files[0];

		$tmp = sys_get_temp_dir();
		$dest = tempnam($tmp, "filtr");

		# FIX ME: use local (to parallel-flickr) copy of filtr

		$cmd = "{$GLOBALS['cfg']['filtr_path']} {$src} {$dest} {$filtr}";
		$enc_cmd = escapeshellcmd($cmd);	

		$out = array();
		$val = null;

		exec($enc_cmd, $out, $val);

		if ($val){
			return not_okay(implode(";", $out));
		}

		if (! file_exists($dest)){
			return not_okay("failed to filtr photo '{$cmd}'");
		}

		return okay(array(
			'path' => $dest
		));
	}

	#################################################################
?>
