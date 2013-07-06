<?php

	loadlib("storage_utils");
	loadlib("storage_fs");
	loadlib("storage_s3");
	loadlib("storage_storagemaster");

	#################################################################

	$GLOBALS['_storage_hooks'] = array(
		'file_exists'	=> null,
		'get_file'	=> null,
		'put_file'	=> null,
		'delete_file'	=> null,
	);

	#################################################################

	# pass in provider here ?

	function storagemaster_init(){

		$storage_provider_lib = "storage_{$GLOBALS['cfg']['storage_provider']}";
		$storage_provider_init = "{$storage_provider_lib}_init";

		if (! function_exists($storage_provider_init)){
			$storage_provider_lib = "storage_fs";
			$storage_provider_init = "{$storage_provider_lib}_init";
		}

		call_user_func($storage_provider_init);
	}

	#################################################################

	function storage_file_exists($path, $more=array()){

		if ($func = $GLOBALS['_storage_hooks']['file_exists']){

			$rsp = call_user_func_array($func, array($path, $more));
			return (isset($more['boolean'])) ? $rsp['ok'] : $rsp;
		}

		return array('ok' => 0);
	}

	#################################################################

	# $fh = fopen("...", "w");
	# fwrite($fh, stream_get_contents($rsp['fh']));
	# fclose($fh);

	function storage_get_file($path, $more=array()){

		if ($func = $GLOBALS['_storage_hooks']['get_file']){

			return call_user_func_array($func, array($path, $more));
		}

		return array('ok' => 0);
	}

	#################################################################

	function storage_put_file($path, $bytes, $more=array()){

		if ($func = $GLOBALS['_storage_hooks']['put_file']){

			return call_user_func_array($func, array($path, $bytes, $more));
		}

		return array('ok' => 0);
	}

	#################################################################

	function storage_delete_file($path, $more=array()){

		if ($func = $GLOBALS['_storage_hooks']['delete_file']){

			return call_user_func_array($func, array($path, $more));
		}

		return array('ok' => 0);
	}

	#################################################################

	# the end
