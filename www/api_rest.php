<?php

	# Note the order here – it's important
	# (20121024/straup)

	$GLOBALS['this_is_api'] = 1;

	include("include/init.php");
	loadlib("api");

	$method = request_str("method");

	api_dispatch($method);
	exit();

?>
