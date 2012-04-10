<?php

	# Hey look. Running code.

	if (preg_match("!/god/$!", $GLOBALS['cfg']['abs_root_url'])){
		$GLOBALS['cfg']['abs_root_url'] = dirname($GLOBALS['cfg']['abs_root_url']) . "/";
	}

	login_ensure_loggedin($_SERVER['REQUEST_URI']);

	if (! auth_has_role('admin')){
		error_403();
	}

?>
