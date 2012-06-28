<?php

	# As in ../../../filtr
	#
	# Obviously, if lib_filtr ever leaves as a thing not specific to
	# parallel-flickr we'll need to revisit how this path is set but
	# since we're not right now who cares, really? (20120628/straup)
 
	$GLOBALS['filtr_root'] = rtrim(dirname(dirname(dirname(__FILE__))), "/") . "/filtr/";

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

		preg_match("/\.([a-z]+)$/i", $src, $m);
		$ext = strtolower($m[1]);

		$tmp = sys_get_temp_dir();
		$dest = tempnam($tmp, "filtr") . ".{$ext}";

		$filtr_bin = $GLOBALS['filtr_root'] . "filtr";

		$cmd = "{$filtr_bin} {$src} {$dest} {$filtr}";
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
