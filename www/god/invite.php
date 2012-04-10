<?php

	include("../include/init.php");
	loadlib("god");

	loadlib("invite_codes");

	$code = request_str("code");

	if (! $code){
		error_404();
	}

	$invite = invite_codes_get_by_code($code, 0);

	if (! $invite){
		error_404();
	}

	$crumb_key = 'god_invite';
	$GLOBALS['smarty']->assign("crumb_key", $crumb_key);

	$crumb_ok = crumb_check($crumb_key);

	if (($crumb_ok) && (post_str("delete"))){

		$rsp = invite_codes_delete($invite);

		if ($rsp['ok']){
			header("location: /god/invites.php");
			exit();
		}

		$GLOBALS['error']['delete_failed'] = 1;
		$GLOBALS['error']['details'] = $rsp['error'];
	}

	else if (($crumb_ok) && (post_str("send"))){

		$more = array(
			'send_email' => 1,
			'invited_by' => $GLOBALS['cfg']['user']['id'],
		);

		$rsp = invite_codes_invite_user($invite['email'], $more);

		if ($rsp['ok']){
			$GLOBALS['smarty']->assign_by_ref("invite", $rsp['invite']);
			# refresh so we get an updated 'sent' date
			$invite = invite_codes_get_by_code($invite['code'], 0);
		}

		else {
			$GLOBALS['error'] = $rsp['error'];
		}

		$GLOBALS['smarty']->assign_by_ref("sent", $rsp);
	}

	else {}

	$GLOBALS['smarty']->assign_by_ref("invite", $invite);

	$GLOBALS['smarty']->display("page_god_invite.txt");
	exit();
?>
