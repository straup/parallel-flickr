<?php

	include("../include/init.php");
	loadlib("god");

	$page = ($p = get_int32("page")) ? $p : 1;

	$args = array(
		'page' => $page,
	);

	$rsp = users_get_users($args);
	$users = array();

	if ($rsp['ok']){

		foreach ($rsp['rows'] as $user){

			# get invite stuff here
			# get subscription stuff here

			$users[] = $user;
		}

		$GLOBALS['smarty']->assign_by_ref("users", $users);
	}

	else {
		$GLOBALS['error']['db_error'] = 1;
		$GLOBALS['error']['details'] = $rsp['error'];
	}

	$GLOBALS['smarty']->assign("pagination_url", "/god/users/");

	$GLOBALS['smarty']->display("page_god_users.txt");
	exit();
?>
