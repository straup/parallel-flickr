<?php
	#
	# lib_santize.php
	#
	# A PHP input filtering library
	#
	# $Id$
	#
	# *article urls here*
	#
	# By Cal Henderson <cal@iamcal.com>
	# This code is licensed under a Creative Commons Attribution-ShareAlike 2.5 License
	# http://creativecommons.org/licenses/by-sa/2.5/
	#

	##############################################################################

	#
	# what to do when invalid
	#

	define('SANITIZE_INVALID_STRIP',	1); # strip out the offending bytes
	define('SANITIZE_INVALID_THROW',	2); # throw an error
	define('SANITIZE_INVALID_CONVERT',	3); # convert from another encoding

	define('SANITIZE_EXTENSION_PHP',	1); # pure php conversion (slow!)
	define('SANITIZE_EXTENSION_MBSTRING',	2); # the default
	define('SANITIZE_EXTENSION_ICONV',	3); # an alternative


	$GLOBALS['sanitize_mode']		= SANITIZE_INVALID_STRIP;
	$GLOBALS['sanitize_extension']		= SANITIZE_EXTENSION_MBSTRING;
	$GLOBALS['sanitize_convert_from']	= 'ISO-8859-1'; # Latin-1
	$GLOBALS['sanitize_input_encoding']	= 'UTF-8';
	$GLOBALS['sanitize_strip_reserved']	= false;
	$GLOBALS['sanitize_pcre_has_props']	= sanitize_check_pcre_unicode_props();

	##############################################################################

	function get_isset(		$key, $default=null, $more=null){ return sanitize($_GET[$key], 'isset'		, $default, $more);  }
	function get_str(		$key, $default=null, $more=null){ return sanitize($_GET[$key], 'str'		, $default, $more);  }
	function get_str_multi(		$key, $default=null, $more=null){ return sanitize($_GET[$key], 'str_multi'	, $default, $more);  }
	function get_int32(		$key, $default=null, $more=null){ return sanitize($_GET[$key], 'int32'		, $default, $more);  }
	function get_int64(		$key, $default=null, $more=null){ return sanitize($_GET[$key], 'int64'		, $default, $more);  }
	function get_html(		$key, $default=null, $more=null){ return sanitize($_GET[$key], 'html'		, $default, $more);  }
	function get_bool(		$key, $default=null, $more=null){ return sanitize($_GET[$key], 'bool'		, $default, $more);  }
	function get_rx(		$key, $default=null, $more=null){ return sanitize($_GET[$key], 'rx'		, $default, $more);  }
	function get_in(		$key, $default=null, $more=null){ return sanitize($_GET[$key], 'in'		, $default, $more);  }

	##############################################################################

	function post_isset(		$key, $default=null, $more=null){ return sanitize($_POST[$key], 'isset'		, $default, $more);  }
	function post_str(		$key, $default=null, $more=null){ return sanitize($_POST[$key], 'str'		, $default, $more);  }
	function post_str_multi(	$key, $default=null, $more=null){ return sanitize($_POST[$key], 'str_multi'	, $default, $more);  }
	function post_int32(		$key, $default=null, $more=null){ return sanitize($_POST[$key], 'int32'		, $default, $more);  }
	function post_int64(		$key, $default=null, $more=null){ return sanitize($_POST[$key], 'int64'		, $default, $more);  }
	function post_html(		$key, $default=null, $more=null){ return sanitize($_POST[$key], 'html'		, $default, $more);  }
	function post_bool(		$key, $default=null, $more=null){ return sanitize($_POST[$key], 'bool'		, $default, $more);  }
	function post_rx(		$key, $default=null, $more=null){ return sanitize($_POST[$key], 'rx'		, $default, $more);  }
	function post_in(		$key, $default=null, $more=null){ return sanitize($_POST[$key], 'in'		, $default, $more);  }

	##############################################################################

	function request_isset(		$key, $default=null, $more=null){ return sanitize($_REQUEST[$key], 'isset'	, $default, $more);  }
	function request_str(		$key, $default=null, $more=null){ return sanitize($_REQUEST[$key], 'str'	, $default, $more);  }
	function request_str_multi(	$key, $default=null, $more=null){ return sanitize($_REQUEST[$key], 'str_multi'	, $default, $more);  }
	function request_int32(		$key, $default=null, $more=null){ return sanitize($_REQUEST[$key], 'int32'	, $default, $more);  }
	function request_int64(		$key, $default=null, $more=null){ return sanitize($_REQUEST[$key], 'int64'	, $default, $more);  }
	function request_html(		$key, $default=null, $more=null){ return sanitize($_REQUEST[$key], 'html'	, $default, $more);  }
	function request_bool(		$key, $default=null, $more=null){ return sanitize($_REQUEST[$key], 'bool'	, $default, $more);  }
	function request_rx(		$key, $default=null, $more=null){ return sanitize($_REQUEST[$key], 'rx'		, $default, $more);  }
	function request_in(		$key, $default=null, $more=null){ return sanitize($_REQUEST[$key], 'in'		, $default, $more);  }

	##############################################################################

	function sanitize($input, $type, $default=null, $more=null){

		#
		# if we get a null in, always return a null
		#

		if ($type == 'isset') return isset($input);

		if (!isset($input)) return $default;

		switch ($type){

			case 'str':
				return sanitize_string($input, false);

			case 'str_multi':
				return sanitize_string($input, true);

			case 'int32':
				return sanitize_int32($input);

			case 'int64':
				return sanitize_int64($input);

			case 'html':
				# this needs to do class_exists('lib_filter')
				die("not implemented");
			
			case 'bool':
				return $input ? true : false;

			case 'rx':
				if (preg_match($more, $input)) return $input;
				return $default;

			case 'in':
				foreach ($more as $match){
					if ($input === $match){
						return $input;
					}
				}
				return $default;
		}

		die("Unknown data conversion type: $type");
	}

	##############################################################################

	function sanitize_string($input, $allow_newlines){

		if (!is_string($input)) $input = "$input";

		#
		# first, do we need to convert from another character set or encoding?
		#

		if ($GLOBALS['sanitize_input_encoding'] != 'UTF-8'){

			$input = sanitize_convert_string($input, $GLOBALS['sanitize_input_encoding'], 'UTF-8');

		}else{

			#
			# if we didn't convert from a different character set, check that it's valid UTF-8
			#

			$test = sanitize_convert_string($input, 'UTF-8', 'UTF-8');

			if ($test != $input){

				switch ($GLOBALS['sanitize_mode']){

					case SANITIZE_INVALID_THROW:
						throw new Exception('Sanitize found invalid input');

					case SANITIZE_INVALID_CONVERT:
						$input = sanitize_convert_string($input, $GLOBALS['sanitize_convert_from']);
						break;

					case SANITIZE_INVALID_STRIP:
						$input = $test;
						break;

					default:
						throw new Exception('Unknown sanitize mode');
				}
			}
		}


		#
		# filter out evil codepoints
		#
		# U+0000..U+0008	00000000..00001000				\x00..\x08				[\x00-\x08]
		# U+000E..U+001F	00001110..00011111				\x0E..\x1F				[\x0E-\x1F]
		# U+007F..U+0084	01111111..10000100				\x7F,\xC2\x80..\xC2\x84			\x7F|\xC2[\x80-\x84\x86-\x9F]
		# U+0086..U+009F	10000110..10011111				\xC2\x86..\xC2\x9F			^see above^
		# U+FEFF		1111111011111111				\xEF\xBB\xBF				\xEF\xBB\xBF
		# U+206A..U+206F	10000001101010..10000001101111			\xE2\x81\xAA..\xE2\x81\xAF		\xE2\x81[\xAA-\xAF]
		# U+FFF9..U+FFFA	1111111111111001..1111111111111010		\xEF\xBF\xB9..\xEF\xBF\xBA		\xEF\xBF[\xB9-\xBA]
		# U+E0000..U+E007F	11100000000000000000..11100000000001111111	\xF3\xA0\x80\x80..\xF3\xA0\x81\xBF	\xF3\xA0[\x80-\x81][\x80-\xBF]
		# U+D800..U+DFFF	1101100000000000..1101111111111111		\xED\xA0\x80..\xED\xBF\xBF		\xED[\xA0-\xBF][\x80-\xBF]
		# U+110000..U+13FFFF	100010000000000000000..100111111111111111111	\xf4\x90\x80\x80..\xf4\xbf\xbf\xbf	\xf4[\x90-\xbf][\x80-\xbf][\x80-\xbf]
		#

		$rx = '[\x00-\x08]|[\x0E-\x1F]|\x7F|\xC2[\x80-\x84\x86-\x9F]|\xEF\xBB\xBF|\xE2\x81[\xAA-\xAF]|\xEF\xBF[\xB9-\xBA]|\xF3\xA0[\x80-\x81][\x80-\xBF]|\xED[\xA0-\xBF][\x80-\xBF]|\xf4[\x90-\xbf][\x80-\xbf][\x80-\xbf]'; # |\p{Cn}
		$input = preg_replace('!'.$rx.'!', '', $input);

		# can i convert the above RX into a utf-8 mode one?
		# if i can, i can merge it with the Cn RX and maybe make it fast...
		#$rx = '[\x00-\x08\x0E-\x1F\x7F\xC2\x80-\xC2\x84\xC2\x86-\xC2\x9F\xEF\xBB\xBF\xE2\x81\xAA-\xE2\x81\xAF\xEF\xBF\xB9-\xEF\xBF\xBA\xF3\xA0\x80\x80-\xF3\xA0\x81\xBF\xED\xA0\x80-\xED\xBF\xBF\xf4\x90\x80\x80-\xf4\xbf\xbf\xbf]';
		#$input = preg_replace('!'.$rx.'!u', '', $input);


		#
		# we need to do this in a separte regexp, which makes stuff slow. gah!
		# maybe we can roll it all into one UTF-8 rx?
		#

		if ($GLOBALS['sanitize_strip_reserved']){

			if ($GLOBALS['sanitize_pcre_has_props']){

				$input = preg_replace('!\p{Cn}!u', '', $input);
			}else{
				throw new Exception('PCRE has not been compiled with unicode property support. Try disabling sanitize_strip_reserved');
			}

		}else{
			$rx = '((\xF4\x8F|\xEF|\xF0\x9F|\xF0\xAF|\xF0\xBF|((\xF1|\xF2|\xF3)(\x8F|\x9F|\xAF|\xBF)))\xBF(\xBE|\xBF))|\xEF\xB7[\x90-\xAF]';
			$input = preg_replace('!'.$rx.'!', '', $input);
		}


		#
		# convert some others into new lines
		#

		$lf = $allow_newlines ? "\n" : " ";
		$ff = $allow_newlines ? "\n\n" : " ";

		$map = array(
			"\xE2\x80\xA8"	=> $lf, # U+2028
			"\xE2\x80\xA9"	=> $ff, # U+2029
			"\xC2\x85"	=> $lf, # EBCDIC Next Line / NEL
			"\x09"		=> " ",
			"\x0B"		=> $ff,
			"\x0C"		=> $ff,
			"\r\n"		=> $lf,
			"\r"		=> $lf,
			"\n"		=> $lf,
			"\xEF\xBF\xBC"	=> '?',	# U+FFFC
			"\xEF\xBF\xBD"	=> '?',	# U+FFFD
		);

		$input = str_replace(array_keys($map), $map, $input);


		#
		# returnify!
		#

		return $input;
	}

	##############################################################################

	function sanitize_convert_string($input, $from){


		switch ($GLOBALS['sanitize_extension']){

			case SANITIZE_EXTENSION_PHP:
				if ($from == 'ISO-8859-1'){

					return utf8_encode($input);
				}

				if ($from == 'UTF-8'){

					return sanitize_clean_utf8($input);
				}

				throw new Exception('Pure PHP sanitize can only convert from ISO-8859-1');
				return;

			case SANITIZE_EXTENSION_MBSTRING:

				if (!function_exists('mb_substitute_character')) return 'NO-MBSTRING-SUPPORT';
				if (!function_exists('mb_convert_encoding')) return 'NO-MBSTRING-SUPPORT';

				if ($from == 'UTF-8'){

					#
					# we strip out several things before feeding it into the convertor, since the convertor
					# tries to do some fixing, while we'd rather it just gave up on bad codes.
					#

					mb_substitute_character('long');
					return mb_convert_encoding(sanitize_strip_overlong($input), 'UTF-8', 'UTF-8');
				}
			
				mb_substitute_character(0xFFFD);
				return mb_convert_encoding($input, 'UTF-8', $from);

			case SANITIZE_EXTENSION_ICONV:

				if (!function_exists('iconv')) return 'NO-ICONV-SUPPORT';

				#
				# iconv is, alas, fucking retarded. it acts incorrectly, throwing a notice
				# *only* if there's an invalid (short) multibyte sequence at the end of
				# the input, even with //IGNORE. if the invalid sequence is in the middle of
				# the string, it's fine.
				#
				# to fix this, we append some characters and then remove them afterwards.
				#
				# we also need to silence the call, since it complains on my fedora box
				# even when it succeeds in //IGNORE'ing certain errors. go iconv!
				#

				return substr(@iconv($from, 'UTF-8//IGNORE', sanitize_strip_overlong($input).'XXXX'), 0, -4);
		}

		throw new Exception('Unknown sanitize extension');
	}

	##############################################################################

	function sanitize_strip_overlong($input){

		#
		# invalid bytes: C0-C1, F5-FF
		# overlong 3 bytes: E0[80-9F][80-BF]
		# overlong 4 bytes: F0[80-8F][80-BF][80-BF]
		#

		return preg_replace('![\xC0-\xC1\xF5-\xFF]|\xE0[\x80-\x9F][\x80-\xbf]|\xF0[\x80-\x8F][\x80-\xBF][\x80-\xBF]!', '', $input);
	}

	##############################################################################

	function sanitize_clean_utf8($data){

		$rx = '';
		$rx .= '([\xC0-\xC1\xF5-\xFF])';				# invalid bytes
		$rx .= '|([\xC0-\xDF](?=[^\x80-\xBF]|$))';			# 1-leader without a trailer
		$rx .= '|([\xE0-\xEF](?=[\x80-\xBF]{0,1}([^\x80-\xBF]|$)))';	# 2-leader without 2 trailers
		$rx .= '|([\xF0-\xF7](?=[\x80-\xBF]{0,2}([^\x80-\xBF]|$)))';	# 3-leader without 3 trailers
		$rx .= '|((?<=[\x00-\x7F]|^)[\x80-\xBF]+)';			# trailer following a non-leader
		$rx .= '|((?<=[\xC0-\xDF][\x80-\xBF]{1})[\x80-\xBF]+)';		# 1 leader with too many trailers
		$rx .= '|((?<=[\xE0-\xEF][\x80-\xBF]{2})[\x80-\xBF]+)';		# 2 leader with too many trailers
		$rx .= '|((?<=[\xF0-\xF7][\x80-\xBF]{3})[\x80-\xBF]+)';		# 3 leader with too many trailers
		$rx .= '|(\xE0[\x80-\x9F])';					# overlong 3-byte
		$rx .= '|(\xF0[\x80-\x8F])';					# overlong 4-byte


		#
		# one of the reasons this is even slower than it needs to be is that
		# we have to apply it twice. seems to be related to overlapping
		# assertions, but that shouldn't be the case. argh!
		#

		return preg_replace("!$rx!s", '', preg_replace("!$rx!s", '', $data));
	}

	##############################################################################

	function sanitize_int32($input, $complain=false){

		$r = intval($input);

		if ($r == 2147483647 && $complain){
			die("sanitize_int32($input) overflowed");
		}

		return $r;
	}

	##############################################################################

	function sanitize_int64($input){

		if (preg_match('!^(\d+)!', $input, $m)){
			return $m[1];
		}

		return 0;
	}

	##############################################################################

	#
	# sometimes PCRE is compiled without --enable-unicode-properties and properties
	# wont work. we detect that once per execution and store the result.
	#

	function sanitize_check_pcre_unicode_props(){

		if (@preg_match('!\p{Ll}!', 'hello')){
			return true;
		}

		return false;
	}

	##############################################################################
?>
