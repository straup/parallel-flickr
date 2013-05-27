<?php

	$GLOBALS['_storage_hooks'] = array(
		'file_exists'	=> null,
		'get_file'	=> null,
		'put_file'	=> null,
		'delete_file'	=> null,
	);

	#################################################################

	function storage_file_exists($path, $more=array()){

		if ($func = $GLOBALS['_storage_hooks']['file_exists']){

			return call_user_func_array($func, array($path, $more));
		}

		return array('ok' => 0);
	}

	#################################################################

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
