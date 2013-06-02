<?php

	include("include/init.php");
	loadlib("api");

	features_ensure_enabled("api");

	$GLOBALS['smarty']->display("page_api.txt");
	exit();
?>
