<?php

	#
	# $Id$
	#

	#################################################################

	function crumb_generate($key, $target=''){

		$base = crumb_get_base($key, $target);

		$time = time();
		$snowman = "\xE2\x98\x83";

		$hash = crumb_hash($base.$time, 10);

		return "{$time}-{$hash}-{$snowman}";
	}

	#################################################################

	function crumb_check($key, $ttl=0, $target=''){
		$test = request_str('crumb');
		return crumb_validate($test, $key, $ttl, $target);
	}

	#################################################################

	function crumb_input($key, $target=''){

		$crumb = crumb_generate($key, $target);
		return '<input type="hidden" name="crumb" value="'.$crumb.'" />';
	}

	#################################################################

	function crumb_qs($key, $target=''){

		$q = array(
			'crumb' => crumb_generate($key, $target)
		);

		return http_build_query($q);
	}

	#################################################################

	function crumb_validate($crumb, $key, $ttl=0, $target=''){

		list($time, $hash) = explode('-', $crumb);

		if ($ttl){
			$then = $time + $ttl;
			$now = time();

			if ($now > $then){
				return 0;
			}
		}

		$base = crumb_get_base($key, $target);
		$hash_test = crumb_hash($base . $time, 10);

		$hash = str_split($hash);
		$hash_test = str_split($hash_test);

		$len_hash = count($hash);

		for ($i=0; $i < $len_hash; $i++){

			if ($hash[$i] != $hash_test[$i]){
				return 0;
			}
		}

		return 1;
	}

	#################################################################

	# returns a string which we'll use as a base to combine with
	# a timestamp to create the crumb. it should be a hex string.

	function crumb_get_base($key, $target=''){

		if (! $target){
			$target = $GLOBALS['_SERVER']['SCRIPT_NAME'];
		}

		# basic browser stuff

		$data = array(
			$key,
			$GLOBALS['_SERVER']['HTTP_USER_AGENT'],
			$target,
			$GLOBALS['_SERVER']['REMOTE_ADDR'],	# check if mobile?
		);

		# if they're signed in, use their account

		if ($GLOBALS['cfg']['user']['id']){

			$data[] = $GLOBALS['cfg']['user']['id'];
			$data[] = md5($GLOBALS['cfg']['user']['conf_code']);
		}

		# this is a nice idea but likely to cause more pain than it's
		# worth the moment you have more than one web server. I suppose
		# you could hash the output of php_info... (20120122/straup)
		# $data[] = php_uname();

		$base = implode(':', $data);
		return $base;
	}

	#################################################################

	function crumb_hash($str, $len=5){

		return substr(sha1($GLOBALS['cfg']['crypto_crumb_secret'] . $str), 0, $len);
	}

	#################################################################
?>
