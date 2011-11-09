<?
	function useragent_decode($ua){

		#
		# a list of user agents, in order we'll match them.
		# e.g. we put chrome before safari because chrome also
		# claims it is safari (but the reverse is not true)
		#

		$agents = array(
			'chrome',
			'safari',
			'konqueror',
			'firefox',
			'netscape',
			'opera',
			'msie',
		);

		$engines = array(
			'webkit',
			'gecko',
			'trident',
		);

		$ua = StrToLower($ua);
		$out = array();

		$temp = useragent_match($ua, $agents);
		$out['agent']		= $temp['token'];
		$out['agent_version']	= $temp['version'];

		$temp = useragent_match($ua, $engines);
		$out['engine']		= $temp['token'];
		$out['engine_version']	= $temp['version'];


		#
		# safari does something super annoying, putting the version in the
		# wrong place like: "Version/5.0.1 Safari/533.17.8"
		#

		if ($out['agent'] == 'safari'){
			$temp = useragent_match($ua, array('version'));
			if ($temp['token']) $out['agent_version'] = $temp['version'];
		}


		return $out;
	}

	function useragent_match($ua, $tokens){

		foreach ($tokens as $token){

			if (preg_match("!{$token}[/ ]([0-9.]+)!", $ua, $m)){
				return array(
					'token'		=> $token,
					'version'	=> $m[1],
				);
			}

			if (preg_match("!$token!", $ua)){
				return array(
					'token'		=> $token,
					'version'	=> $null,
				);
			}
		}

		return array(
			'token'		=> null,
			'version'	=> null,
		);
	}
?>
