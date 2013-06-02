<?php

	#################################################################

	function api_test_echo(){

		$out = array();

		foreach ($_GET as $k => $ignore){

			if ($k = filter_strict($k)){
				$v = filter_strict(get_str($k));
				$out[$k] = $v;
			}
		}

		api_output_ok($out);
	}

	#################################################################

	function api_test_error(){
		api_output_error(500, 'This is the network of our disconnect');
	}

	#################################################################

	# the end
