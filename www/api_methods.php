<?php

	include("include/init.php");
	loadlib("api");

	if (! $GLOBALS['cfg']['enable_feature_api']){
		error_disabled();
	}

	if (! $GLOBALS['cfg']['enable_feature_api_documentation']){
		error_disabled();
	}

	$method_classes = array();

	foreach ($GLOBALS['cfg']['api']['methods'] as $method_name => $details){

		# TO DO: god auth check...

		if (! $details['enabled']){
			continue;
		}

		if (! $details['documented']){
			continue;
		}

		$parts = explode(".", $method_name);
		array_pop($parts);

		$method_class = implode(".", $parts);

		if (! is_array($method_classes[$method_class])){
			$methods_classes[$method_class] = array();
		}

		$method_classes[$method_class][] = $method_name;
	}

	foreach ($method_classes as $class_name => $method_names){
		sort($method_classes[$class_name]);
	}

	$GLOBALS['smarty']->assign_by_ref("methods", $GLOBALS['cfg']['api']['methods']);
	$GLOBALS['smarty']->assign_by_ref("method_classes", $method_classes);

	$GLOBALS['smarty']->display("page_api_methods.txt");
	exit();
?>
