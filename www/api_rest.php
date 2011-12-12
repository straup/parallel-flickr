<?php

	include("include/init.php");
	loadlib("api");

	$method = request_str("method");

	api_dispatch($method);
	exit();

?>
