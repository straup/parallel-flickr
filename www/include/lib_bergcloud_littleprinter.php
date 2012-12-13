<?php

	loadlib("http");

	$GLOBALS['bergcloud_littleprinter_direct_print_endpoint'] = "http://remote.bergcloud.com/playground/direct_print/";

	########################################################################

	function bergcloud_littleprinter_direct_print($msg, $code){

		$enc_code = urlencode($code);
		$url .= $GLOBALS['bergcloud_littleprinter_direct_print_endpoint'] . $enc_code;

		$params = array(
			'html' => $msg,
		);

		$rsp = http_post($url, $params);
		return $rsp;
	}

	########################################################################
