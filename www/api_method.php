<?php

	include("include/init.php");

	loadlib("api");
	loadlib("api_spec");

	features_ensure_enabled(array("api", "api_documentation"));

	flickr_backups_ensure_registered_user($GLOBALS['cfg']['user']);

	$method = get_str("method");

	if (! $method){
		error_404();
	}

	if (! isset($GLOBALS['cfg']['api']['methods'][$method])){
		error_404();
	}

	$details = $GLOBALS['cfg']['api']['methods'][$method];

	if (! $details['documented']){
		error_404();
	}

	if (! $details['enabled']){
		error_404();
	}

	$rsp = api_spec_utils_example_for_method($method);

	if ($rsp['ok']){
		$details['example_response'] = $rsp['example'];
	}

	# TO DO: convert markdown in $details

	$GLOBALS['smarty']->assign("method", $method);
	$GLOBALS['smarty']->assign_by_ref("details", $details);

	$GLOBALS['smarty']->display("page_api_method.txt");
	exit();
?>
