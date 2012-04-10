<?php

	include("../include/init.php");
	loadlib("god");

	loadlib("invite_codes");
	loadlib("rfc822");

	$crumb_key = 'god_generate_invite';
	$GLOBALS['smarty']->assign("crumb_key", $crumb_key);

	$crumb_ok = crumb_check($crumb_key);

	if ($crumb_ok){

		$email = post_str("email");
		$code = post_str("code");

		if ($code){

			$ensure_sent = 0;

			if ($invite = invite_codes_get_by_code($code, $ensure_sent)){

				$template = 'email_invite_code.txt';
				invite_codes_send_invite($invite, $template);

				$invite = invite_codes_get_by_code($code, $ensure_sent);

				$GLOBALS['smarty']->assign_by_ref("invite", $invite);
				$GLOBALS['smarty']->assign("invite_sent", 1);
			}

			else {
				$GLOBALS['error'] = "Invalid invite code";
			}		
		}

		else if (! $email){
			$GLOBALS['error'] = "Missing email";
		}

		else if (! rfc822_is_valid_email_address($email)){
			$GLOBALS['error'] = "Invalid email ({$email})";
		}

		else if ($invite = invite_codes_get_by_email($email)){
			$GLOBALS['smarty']->assign_by_ref("invite", $invite);
		}

		else {

			$more = array(
				'invited_by' => $GLOBALS['cfg']['user']['id'],
			);

			$rsp = invite_codes_invite_user($email, $more);

			if ($rsp['ok']){
				$GLOBALS['smarty']->assign_by_ref("invite", $rsp['invite']);
			}

			else {
				$GLOBALS['error'] = $rsp['error'];
			}
		}
	}

	$GLOBALS['smarty']->display("page_god_invites_generate.txt");
	exit();
?>
