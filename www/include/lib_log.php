<?
	#
	# $Id$
	#


	#
	# the log module is designed to be as flexible as possible. the application can log message at
	# multiple levels: fatal, error & notice. these messages can have an attached type (e.g. 'db'),
	# but this is only used for notices right now. for each level, we can define zero or more
	# handlers, including html/display and writing to the error log.
	#
	# right now the html display only shows notice messages when 'debug=1' is specified as a URL
	# flag and always shows errors/fatals. it should ideally also check for admin auth/SSO, so that
	# regular users don't see notices at all. another approach is to remove the html handlers and
	# then add them back in during init for admin authed users. for fatal errors, you will probably
	# want to add a handler which displays an error page to your users.
	#
	# you will also want to disable html handlers when outputing things that aren't webpages, like
	# api results.
	#

	$GLOBALS['log_handlers'] = array(
		'notice'	=> array('html'),
		'error'		=> array('html', 'error_log'),
		'fatal'		=> array('html', 'error_log'),
		'rawr'		=> array('error_log'),	# this one calls exit
		'info'		=> array('error_log'),
		'debug'		=> array('plain'),
	);

	$GLOBALS['log_html_colors'] = array(
		'db'		=> '#eef,#000',
		'cache'		=> '#fdd,#000',
		'smarty'	=> '#efe,#000',
		'http'		=> '#ffe,#000',
		'_error'	=> '#fcc,#000',
		'_fatal'	=> '#800,#fff',
	);



	#
	# log a startup notice so we know what page this is and what env
	#

	log_notice('init', "this is $_SERVER[SCRIPT_NAME] on {$GLOBALS['cfg']['environment']}");

	###################################################################################################################

	#
	# public api
	#

	function log_fatal($msg){
		_log_dispatch('fatal', $msg);
		error_500();		
		exit;
	}

	function log_rawr($msg){
		_log_dispatch('rawr', $msg);
		exit;
	}

	function log_info($msg){
		_log_dispatch('info', $msg);
	}

	function log_error($msg){
		_log_dispatch('error', $msg);
	}


	function log_notice($type, $msg, $time=-1){
		_log_dispatch('notice', $msg, array('type' => $type, 'time' => $time));
	}
	
	function log_debug($type, $msg, $time=-1){
		_log_dispatch('debug', $msg, array('type' => $type, 'time' => $time));
	}
	
	function log_reset_handlers(){
		$GLOBALS['log_handlers'] = array();
	}
	
	function log_add_handler($level, $handler){
		if ($GLOBALS['log_handlers'][$level]){
			array_push($GLOBALS['log_handlers'][$level], $handler);
		}
		else{
			$GLOBALS['log_handlers'][$level] = array($handler);
		}
	}

	###################################################################################################################

	function _log_dispatch($level, $msg, $more = array()){

		if ($GLOBALS['log_handlers'][$level]){

			foreach ($GLOBALS['log_handlers'][$level] as $handler){

				call_user_func("_log_handler_$handler", $level, $msg, $more);
			}
		}
	}


	###################################################################################################################

	#
	# print messages to the error log
	#

	function _log_handler_error_log($level, $msg, $more = array()){
		$page = $GLOBALS['HTTP_SERVER_VARS']['REQUEST_URI'];

		if ($more['type']){
			$msg = "[$more[type]] $msg";
		}

		$msg = str_replace("\n", ' ', $msg);

		error_log("[$level] $msg");
	}


	#
	# display messages in the browser
	#

	function _log_handler_html($level, $msg, $more = array()){

		if (! auth_has_role('staff')){
			return;
		}

		# only shows notices if we asked to see them
		if ($level == 'notice' && !$GLOBALS['cfg']['admin_flags_show_notices']) return;

		$type = $more['type'] ? $more['type'] : '';

		$colors = $GLOBALS['log_html_colors']['_'.$level];
		if (!$colors) $colors = $GLOBALS['log_html_colors'][$type];
		if (!$colors) $colors = '#eee,#000';

		list($bgcolor, $color) = explode(',', $colors);

		echo "<div style=\"background-color: $bgcolor; color: $color; margin: 1px 1px 0 1px; border: 1px solid #000; padding: 4px; text-align: left; font-family: sans-serif;\">";

		if ($type) echo "[$type] ";

		echo HtmlSpecialChars($msg);

		if ($more['time'] > -1) echo " ($more[time] ms)";

		echo "</div>\n";
	}
	
	
	#
	# boring plaintext output (for scripts)
	#

	function _log_handler_plain($level, $msg, $more = array()){

		# only shows notices if we asked to see them
		if ($level == 'notice' && !$GLOBALS['cfg']['admin_flags_show_notices']) return;

		$type = $more['type'] ? $more['type'] : $level;

		if ($type) echo "[$type] ";

		echo $msg;

		if ($more['time'] > -1) echo " ($more[time] ms)";

		echo "\n";
	}

	###################################################################################################################
?>
