<?php

	include("include/init.php");
	loadlib("api");

	if (! $GLOBALS['cfg']['enable_feature_api']){
		error_disabled();
	}

	if (! $GLOBALS['cfg']['enable_feature_api_documentation']){
		error_disabled();
	}

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

	# TO DO: convert markdown in $details

	$GLOBALS['smarty']->assign("method", $method);
	$GLOBALS['smarty']->assign_by_ref("details", $details);

	$GLOBALS['smarty']->display("page_api_method.txt");
	exit();
?>
