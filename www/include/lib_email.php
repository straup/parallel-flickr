<?
	#
	# $Id$
	#

	#########################################################################################

	function email_send($args){

		$headers = array();

		if (is_array($args['headers'])){

			$headers = $args['headers'];
		}


		#
		# set up the from address
		#

		if ($args['from_name'] && $args['from_email']){

			$from_email = $args['from_email'];
			$from_name = $args['from_name'];

		}else if ($args['from_email']){

			$from_email = $args[from_email];
			$from_name = $args[from_email];

		}else if ($args['from_name']){

			$from_email = $GLOBALS['cfg']['email_from_email'];
			$from_name = $args['from_name'];
		}else{

			$from_email = $GLOBALS['cfg']['email_from_email'];
			$from_name = $GLOBALS['cfg']['email_from_name'];
		}

		$headers['From'] = "\"".email_quoted_printable($from_name)."\" <$from_email>";


		#
		# other headers
		#

		if (!$headers['To']){
			$headers['To'] = $args['to_email'];
		}

		if (!$headers['Reply-To']){
			$headers['Reply-To'] = $from_email;
		}

		if (!$headers['Content-Type']){

			$headers['Content-Type'] = 'text/plain; charset=utf-8';
		}


		#
		# subject and message come from a smarty template
		#

		$message = trim($GLOBALS['smarty']->fetch($args['template']));
		$subject = trim($GLOBALS['smarty']->get_template_vars('email_subject'));

		$message = email_format_body($message);
		$subject = email_quoted_printable($subject);


		#
		# send via local MTA
		#

		unset($headers['To']);

		mail($args['to_email'], $subject, $message, email_format_headers($headers), $GLOBALS['cfg']['auto_email_args']);
	}

	#########################################################################################

	function email_format_body($message){

		$message = str_replace("\r", "", $message);
		$message = wordwrap($message, 72);

		return $message;
	}

	#########################################################################################

	function email_quoted_printable($subject){

		if (preg_match('/[^a-z: ]/i', $subject)){
			$subject = preg_replace_callback('/([^a-z ])/i', 'email_quoted_printable_encode', $subject);
			$subject = str_replace(' ', '_', $subject);
			return "=?utf-8?Q?$subject?=";
		}

		return $subject;
	}

	function email_quoted_printable_encode($m){

		return sprintf('=%02x', StripSlashes(ord($m[1])));
	}

	#########################################################################################

	function email_format_headers(&$headers){

		$h2 = array();

		foreach ($headers as $h => $v){
			$h2[] = "$h: $v";
		}

		return implode("\r\n", $h2);
	}

	#########################################################################################

?>