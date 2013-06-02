<?php

	include("include/init.php");

	flickr_backups_ensure_registered_user($GLOBALS['cfg']['user']);

	$GLOBALS['smarty']->display("page_api_oauth2.txt");
	exit();

?>
