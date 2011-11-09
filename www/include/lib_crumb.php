<?php

	#
	# $Id$
	#

	#################################################################

	#
	# generate a crumb
	#

	function crumb_generate($key){

		$base = crumb_get_base($key);
		$time = time();
		$snowman = "\xE2\x98\x83";

		$hash = crumb_hash($base.$time, 10);

		return "{$time}-{$hash}-{$snowman}";
	}

	#################################################################

	#
	# check if a crumb is valid
	#

	function crumb_validate($crumb, $key, $ttl=0){

		$base = crumb_get_base($key);
		list($time, $hash) = explode('-', $crumb);

		$hash_test = crumb_hash($base.$time, 10);

		if ($hash_test != $hash){
			return 0;
		}

		if ($ttl && ($time + $ttl > time())){
			return 0;
		}

		return 1;
	}

	#################################################################

	#
	# returns a string which we'll use as a base to combine with
	# a timestamp to create the crumb. it should be a hex string.
	#

	function crumb_get_base($key){

		$data = array(
			$key,
			$GLOBALS['_SERVER']['HTTP_USER_AGENT'],
			$GLOBALS['_SERVER']['SCRIPT_NAME'],
			$GLOBALS['_SERVER']['REMOTE_ADDR'],	# check if mobile?
		);

		$base = implode(':', $data);


		#
		# if they're signed in, use their account
		#

		if ($GLOBALS['cfg']['user']['id']){

			$base .= $GLOBALS['cfg']['user']['id'];
		}

		return $base;
	}

	#################################################################

	function crumb_hash($str, $len=5){

		return substr(sha1($GLOBALS['cfg']['crypto_crumb_secret'] . $str), 0, $len);
	}

	#################################################################

	function crumb_check($key, $ttl=0){

		$test = request_str('crumb');

		return crumb_validate($test, $key, $ttl);
	}

	#################################################################

	function crumb_input($key=""){

		$crumb = crumb_generate($key);
		return '<input type="hidden" name="crumb" value="'.$crumb.'" />';
	}

	function crumb_qs($key=""){

		$crumb = crumb_generate($key);
		return 'crumb='.urlencode($crumb);
	}

	#################################################################
?>
