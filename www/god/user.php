<?php

	include("../include/init.php");
	loadlib("god");

	$id = request_str("user_id");

	if ($id){

		$user = users_get_by_id($id);

		if (! $user['id']){
			error_404();
		}

		$GLOBALS['smarty']->assign_by_ref("user", $user);
	}

	$GLOBALS['smarty']->display("page_god_user.txt");
	exit();
?>
