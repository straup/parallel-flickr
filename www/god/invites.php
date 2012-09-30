<?php

	include("../include/init.php");
	loadlib("god");

	loadlib("invite_codes");

	$page = ($p = get_str("page")) ? $p : 1;

	$args = array(
		'page' => $page,
	);

	$rsp = invite_codes_get_all($args);

	$rows = $rsp['rows'] ? $rsp['rows'] : array();

	$invites = array();

	foreach ($rows as $row){

		if ($row['user_id']){
			$row['user'] = users_get_by_id($row['user_id']);
		}

		if ($row['invited_by']){
			$row['invited_by_user'] = users_get_by_id($row['invited_by']);
		}

		$invites[] = $row;
	}

	$GLOBALS['smarty']->assign_by_ref("invites", $invites);

	$GLOBALS['smarty']->assign("pagination_url", "god/invites.php");
	$GLOBALS['smarty']->assign("pagination_query_params", 1);

	$GLOBALS['smarty']->display("page_god_invites.txt");
	exit();
?>
