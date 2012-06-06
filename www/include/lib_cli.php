<?php

	#################################################################

	# $spec = array(
	# 	"input" => array("flag" => "i", "required" => 1, "help" => "yer input"),
	# 	"output" => array("flag" => "o", "required" => 1, "help" => "yer output"),
	# 	"username" => array("flag" => "u", "required" => 1, "help" => "a username"),
	# 	"year" => array("flag" => "y", "required" => 1, "help" => "what time is it?", "sanitize" => "int32"),
	# );

	function cli_getopts($spec, $more=array()){

		$defaults = array(
			'include_help' => 1
		);

		$more = array_merge($defaults, $more);

		if ($more['include_help']){

			$spec['help'] = array(
				"flag" => "h",
				"help" => "print this message",
				"boolean" => 1,
			);
		}

		$short_opts = array();
		$long_opts = array();
		$flags = array();

		foreach ($spec as $name => $details){

			if (! isset($details['flag'])){
				continue;
			}

			$flag = $details['flag'];

			if (isset($spec[$flag])){
				continue;
			}

			$flags[$flag] = $name;

			$extras = ":";

			if (isset($details['boolean'])){
				$extras = "";
			}

			$short_opts[] = "{$flag}{$extras}";
			$long_opts[] = "{$name}{$extras}";
		}

		$short_opts = implode("", $short_opts);

		# See this: we're going to return a cleaned up version
		# of the input parameters using longnames as keys

		$_opts = getopt($short_opts, $long_opts);
		$opts = array();

		$help = ((isset($_opts['h'])) || (isset($_opts['help']))) ? 1 : 0;

		if (($help) && ($more['include_help'])){
			cli_help($spec);
			return;
		}

		foreach ($_opts as $key => $stuff){

			if (isset($spec[$key])){
				$opts[$key] = $stuff;
			}

			else if (isset($flags[$key])){
				$name = $flags[$key];
				$opts[$name] = $stuff;
			}
		}

		foreach ($spec as $key => $details){

			if ((! isset($opts[$key])) && (! $details['required'])){
				continue;
			}

			if (! isset($opts[$key])){
				cli_help($spec, "Required parameter '{$key}' missing");
			}		

			if (isset($details['sanitize'])){
				loadlib("sanitize");
				$opts[$key] = sanitize($opts[$key], $details['sanitize']);
			}

			# note: we are only sanitizing input and not are not testing the
			# actual values, just their presense (20120516/straup)
		}

		return $opts;
	}

	#################################################################

	function cli_help($spec, $msg=''){

		if ($msg){
			echo "{$msg}\n\n";
		}

		echo "Usage:\n\n";
		echo "   $>php -q {$GLOBALS['argv'][0]}";

		if (count($spec)){
			echo " --options\n\n";
			echo "Valid options are:\n";
		}

		echo "\n";

		foreach ($spec as $name => $details){

			echo "--{$name} ";

			if (isset($details['flag'])){
				echo "-{$details['flag']} ";
			}

			if ((isset($details['required'])) && ($details['required'])){

				echo "(required";

				if (isset($details['sanitize'])){
					echo ", {$details['sanitize']}";
				}

				echo ")";
			}

			if (isset($details['help']) && ($details['help'])){
				echo "\n";

				# chunk_split is the quick and dirty way of doing
				# this; it does not account for splitting on words
				# or multibyte strings (20120514/straup)

				$chunks = chunk_split($details['help'], 80);
				$chunks = rtrim($chunks);

				foreach (explode("\n", $chunks) as $chunk){
					$chunk = trim($chunk);
					echo "   {$chunk}\n";
				}
			}

			echo "\n";
		}

		echo "\n";
		exit();
	}

	#################################################################
?>
