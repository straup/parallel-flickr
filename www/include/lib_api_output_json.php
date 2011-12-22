<?php

	#################################################################

	function api_output_ok($rsp, $more=array()){
		api_output_send($rsp, $more);
	}

	#################################################################

	function api_output_error($code=999, $msg='', $more=array()){

		$out = array('error' => array(
			'code' => $code,
			'error' => $msg,
		));

		$more['is_error'] = 1;

		api_output_send($out, $more);
	}

	#################################################################

	function api_output_send($rsp, $more=array()){

		$json = json_encode($rsp);

		utf8_headers();

		# TO DO: these don't always appear to be being set correctly

		if (isset($more['is_error'])){
 			header("HTTP 500 Server Error");
 			header("Status: 500 Server Error");
		}

		if (isset($more['cors_allow'])){
			header("Access-Control-Allow-Origin: " . htmlspecialchars($more['cors_allow']));
		}

		if (! isset($more['inline'])){
			header("Content-Type: text/json");
		}

		header("Content-Length: " . strlen($json));

		echo $json;
		exit();
	}

	#################################################################

?>
