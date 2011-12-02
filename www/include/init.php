<?
	#
	# $Id$
	#

	#
	# some startup tasks which come before anything else:
	#  * set up the timezone
	#  * record the time
	#  * set the mbstring encoding
	#

	error_reporting((E_ALL | E_STRICT) ^ E_NOTICE);

	putenv('TZ=PST8PDT');
	date_default_timezone_set('America/Los_Angeles');

	$GLOBALS['timings'] = array();
	$GLOBALS['timings']['execution_start'] = microtime_ms();
	$GLOBALS['timing_keys'] = array();

	mb_internal_encoding('UTF-8');


	#
	# the module loading code.
	#
	# we track which modules we've loaded ourselves instead of
	# using include_once(). we do this so that we can avoid the
	# stat() overhead involved in figuring out the canonical path
	# to a file. so long as we always load modules via this
	# method, we save some filesystem overhead.
	#
	# we can also ensure that modules don't pollute the global
	# namespace accidentally, since they are always loaded in a
	# function's private scope.
	#

	define('FLAMEWORK_WWW_DIR', dirname(dirname(__FILE__)) );
	define('FLAMEWORK_INCLUDE_DIR', FLAMEWORK_WWW_DIR . '/include/');

	#

	$GLOBALS['loaded_libs'] = array();

	function loadlib($name){

		if ($GLOBALS['loaded_libs'][$name]){
			return;
		}

		$GLOBALS['loaded_libs'][$name] = 1;

		$fq_name = _loadlib_enpathify("lib_{$name}.php");
		include($fq_name);
	}

	function loadpear($name){

		if ($GLOBALS['loaded_libs']['PEAR:'.$name]){
			return;
		}

		$GLOBALS['loaded_libs']['PEAR:'.$name] = 1;

		$fq_name = _loadlib_enpathify("pear/{$name}.php");
		include($fq_name);
	}

	function _loadlib_enpathify($lib){

		# see also: http://www.php.net/manual/en/ini.core.php#ini.include-path

		$inc_path = ini_get('include_path');

		if (preg_match("/\/flamework\//", $inc_path)){
			return $lib;
		}

		return FLAMEWORK_INCLUDE_DIR . $lib;
	}


	#
	# load config
	#

	if (! $GLOBALS['cfg']['flamework_skip_init_config']){
		include(FLAMEWORK_INCLUDE_DIR."/config.php");
	}

	#
	# Poor man's database configs:
	# See notes in config.php
	#

	if ($GLOBALS['cfg']['db_enable_poormans_slaves']){

		$GLOBALS['cfg']['db_main_slaves'] = $GLOBALS['cfg']['db_main'];

		$GLOBALS['cfg']['db_main_slaves']['host'] = array(
			1 => $GLOBALS['cfg']['db_main']['host'],
		);

		$GLOBALS['cfg']['db_main_slaves']['name'] = array(
			1 => $GLOBALS['cfg']['db_main']['name'],
		);
	}

	if ($GLOBALS['cfg']['db_enable_poormans_ticketing']){

		$GLOBALS['cfg']['db_tickets'] = $GLOBALS['cfg']['db_main'];
	}

	if ($GLOBALS['cfg']['db_enable_poormans_federation']){

		$GLOBALS['cfg']['db_users'] = $GLOBALS['cfg']['db_main'];

		$GLOBALS['cfg']['db_users']['host'] = array(
			1 => $GLOBALS['cfg']['db_main']['host'],
		);

		$GLOBALS['cfg']['db_users']['name'] = array(
			1 => $GLOBALS['cfg']['db_main']['name'],
		);

	}

	#
	# install an error handler to check for dubious notices?
	# we do this because we only care about one of the notices
	# that gets generated. we only want to run this code in
	# devel environments. we also want to run it before any
	# libraries get loaded so that we get to check their syntax.
	#

	if ($cfg['check_notices']){
		set_error_handler('handle_error_notices', E_NOTICE);
		error_reporting(E_ALL | E_STRICT);
	}

	function handle_error_notices($errno, $errstr){
		if (preg_match('!^Use of undefined constant!', $errstr)) return false;
		return true;
	}


	#
	# figure out some global flags
	#

	$this_is_apache		= strlen($_SERVER['REQUEST_URI']) ? 1 : 0;
	$this_is_shell		= $_SERVER['SHELL'] ? 1 : 0;
	$this_is_webpage	= $this_is_apache && !$this_is_api ? 1 : 0;

	$cfg['admin_flags_no_db'] = ($_GET['no_db']) ? 1 : 0;
	$cfg['admin_flags_show_notices'] = ($_GET['debug']) ? 1 : 0;

	#
	# load some libraries which we will 'always' need
	#

	loadlib('auth');
	loadlib('log');		# logging comes first, so that other modules can log during startup
	loadlib('smarty');	# smarty comes next, since other libs register smarty modules
	loadlib('utf8');	# make sure utf8/header stuff is present in case we need to take the site down

	if (($GLOBALS['cfg']['site_disabled']) && (! $this_is_shell)){

		header("HTTP/1.1 503 Service Temporarily Unavailable");
		header("Status: 503 Service Temporarily Unavailable");

		if ($retry = intval($GLOBALS['cfg']['site_disabled_retry_after'])){
			header("Retry-After: {$retry}");
		}

		$smarty->display("page_site_disabled.txt");
		exit();
	}

	loadlib('error');
	loadlib('sanitize');
	loadlib('db');
	loadlib('dbtickets');
	loadlib('cache');
	loadlib('crypto');
	loadlib('crumb');
	loadlib('login');
	loadlib('email');
	#loadlib('args');
	#loadlib('calendar');
	loadlib('users');
	#loadlib('versions');
	loadlib('http');
	loadlib('sanitize');
	loadlib('filter');

	if ($this_is_webpage){
		login_check_login();
	}


	#
	# disable precaching
	#

	if (StrToLower($_SERVER['HTTP_X_MOZ']) == 'prefetch'){

		if (!$allow_precache){

			error_403();
		}
	}


	#
	# general utility functions
	#

	function dumper($foo){
		echo "<pre style=\"text-align: left;\">";
		echo HtmlSpecialChars(var_export($foo, 1));
		echo "</pre>\n";
	}

	function intval_range($in, $lo, $hi){
		return min(max(intval($in), $lo), $hi);
	}

	function microtime_ms(){
		list($usec, $sec) = explode(" ", microtime());
		return intval(1000 * ((float)$usec + (float)$sec));
	}

	function filter_strict($str){

		$filter = new lib_filter();
		$filter->allowed = array();
		return $filter->go($str);
	}

	function ok($more=null){

		$out = array('ok' => 1);

		if (is_array($more)){
			$out = array_merge($more, $out);
		}

		return $out;
	}

	function not_ok($msg='Your call could not be completed as dialed', $code=null){

		$out = array('ok' => 0,	'error' => $msg);

		if ($code){
			$out['error_code'] = $code;
		}

		return $out;
	}

	#
	# this timer stores the end of core library loading
	#

	$GLOBALS['timings']['init_end'] = microtime_ms();

	# Smarty stuff

	$GLOBALS['error'] = array();
	$GLOBALS['smarty']->assign_by_ref('error', $error);

	# load core parallel-flickr stuff

	loadlib("flickr_users");
	loadlib("flickr_photos");
	loadlib("flickr_photos_lookup");
	loadlib("flickr_photos_metadata");
	loadlib("flickr_photos_permissions");
	loadlib("flickr_urls");
	loadlib("flickr_dates");

?>
