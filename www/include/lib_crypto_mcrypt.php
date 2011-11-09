<?php

	#
	# $Id$
	#

	#################################################################

	$GLOBALS['crypto_td'] = MCRYPT_RIJNDAEL_256;

	function crypto_create_iv(){

		$iv_size = mcrypt_get_iv_size($GLOBALS['crypto_td'], MCRYPT_MODE_ECB);
		return mcrypt_create_iv($iv_size, MCRYPT_RAND);
	}

	#################################################################

	function crypto_encrypt($data, $key){

		$enc = mcrypt_encrypt($GLOBALS['crypto_td'], $key, $data, MCRYPT_MODE_ECB, crypto_create_iv());
		return base64_encode($enc);
	}

	#################################################################

	function crypto_decrypt($enc_b64, $key){

		$enc = base64_decode($enc_b64);
		$dec = mcrypt_decrypt($GLOBALS['crypto_td'], $key, $enc, MCRYPT_MODE_ECB, crypto_create_iv());

		return trim($dec);
	}

	#################################################################

?>
