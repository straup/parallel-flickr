<?php

	#
	# $Id$
	#

	#################################################################

	#
	# Because life is too short to write your own browser detection
	# code. This is a modified version of a function taken from the
	# PHP site and doesn't try to be more than a 80/20 solution.
	# (20101028/straup)
	#

	function user_agent_info($agent='', $add_extras=0){

		# see also: http://www.php.net/manual/en/function.get-browser.php#92310

		# Declare known browsers to look for

		$known = array(

			'chrome',
			'firefox',
			'gecko',
			'konqueror',
			'msie',
			'netscape',
			'opera',
			'safari',
			'webkit',

			# see this - it's a trick so that we don't have to write
			# code to distinguish between browser engine and browser...

			'version',
		);

		# Clean up agent and build regex that matches phrases for known browsers
		# (e.g. "Firefox/2.0" or "MSIE 6.0" (This only matches the major and minor
		# version numbers.  E.g. "2.0.0.6" is parsed as simply "2.0"

		$agent = strtolower($agent ? ($agent) : $_SERVER['HTTP_USER_AGENT']);
		$pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9]+(?:\.[0-9]+)?)#';

		# Find all phrases (or return empty array if none found)

		if (! preg_match_all($pattern, $agent, $matches)){
			return array();
		}

		$info = array();

		for ($i = 0; $i < count($matches['browser']); $i++){
			$k = $matches['browser'][$i];
			$v = floatval($matches['version'][$i]);
			$info[$k] = $v;
		}

		if ($add_extras){
			user_agent_info_add_extras($info, $agent);
		}

		return $info;
	}

	#################################################################

	# Note the pass-by-ref

	function user_agent_info_add_extras(&$info, $agent){

		if (preg_match("/(android)\s+(\d+\.\d+)/i", $agent, $m)){

			$info[$m[1]] = floatval($m[2]);
		}

		else if (preg_match("#(symbian)os/(\d+\.\d+);\s+(series(?:\d+))/(\d+\.\d+)\s+(nokia)([0-9a-z\-]+)#i", $agent, $m)){

			$info[$m[1]] = floatval($m[2]);	# symbian => 9.3
			$info[$m[3]] = floatval($m[4]);	# series60 => 3.2
			$info[$m[5]] = $m[6];			# nokia => 'e72-1'
		}

		else if (preg_match("/(i(?:pad|phone))/i", $agent, $m)){

			$ima = $m[1];
			$version = null;

			if (preg_match("/OS\s+(?:(\d+)_(\d+))/i", $agent, $m)){
				$info[$ima] = 1;
				$info['ios'] = floatval("{$m[1]}.{$m[2]}");
			}
		}

		# chromeframe

		# blackberry
	}

	#################################################################
?>
