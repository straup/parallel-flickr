<?php

	include("../include/init.php");
	loadlib("god");

	loadlib("flickr_backups");

	# TO DO: pagination

	$users = flickr_backups_users($args);
	$GLOBALS['smarty']->assign_by_ref("users", $users);

	$GLOBALS['smarty']->display("page_god_users_backup.txt");
	exit();
?>
