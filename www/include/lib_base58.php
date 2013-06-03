<?php

	# http://darklaunch.com/2009/08/07/base58-encode-and-decode-using-php-with-example-base58-encode-base58-decode

	#################################################################

	function base58_encode($num) {
		$alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
		$base_count = strlen($alphabet);
		$encoded = '';
 
		while ($num >= $base_count) {
			$div = $num / $base_count;
			$mod = ($num - ($base_count * intval($div)));
			$encoded = $alphabet[$mod] . $encoded;
			$num = intval($div);
		}
 
		if ($num) {
			$encoded = $alphabet[$num] . $encoded;
		}
 
		return $encoded;
	}

	#################################################################
 
	function base58_decode($num) {
		$alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
		$len = strlen($num);
		$decoded = 0;
		$multi = 1;
 
		for ($i = $len - 1; $i >= 0; $i--) {
			$decoded += $multi * strpos($alphabet, $num[$i]);
			$multi = $multi * strlen($alphabet);
		}
 
		return $decoded;
	}

	#################################################################
