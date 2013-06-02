<?php

	include("include/init.php");
	loadlib("api");

	features_ensure_enabled("api");

	flickr_backups_ensure_registered_user($GLOBALS['cfg']['user']);

	$GLOBALS['smarty']->display("page_api.txt");
	exit();
?>
