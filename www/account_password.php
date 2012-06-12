<?
	#
	# $Id$
	#

	include("include/init.php");

	login_ensure_loggedin();


	#
	# crumb key
	#

	$crumb_key = 'account_password';
	$smarty->assign("crumb_key", $crumb_key);



	#
	# update?
	#

	if (post_str('change') && crumb_check($crumb_key)){

		$old_pass	= trim(post_str('old_password'));
		$new_pass1	= trim(post_str('new_password1'));
		$new_pass2	= trim(post_str('new_password2'));

		$ok = 1;

		if (! passwords_validate_password_for_user($old_pass, $GLOBALS['cfg']['user'])){
			$smarty->assign('error_oldpass_mismatch', 1);
			$ok = 0;
		}

		if ($ok && $new_pass1 !== $new_pass2){

			$smarty->assign('error_newpass_mismatch', 1);
			$ok = 0;
		}

		if ($ok && !strlen($new_pass2)){

			$smarty->assign('error_newpass_empty', 1);
			$ok = 0;
		}

		if ($ok){
			if (! users_update_password($GLOBALS['cfg']['user'], $new_pass1)){

				$smarty->assign('error_fail', 1);
				$ok = 0;
			}
		}

		if ($ok){

			#
			# Refresh the user so that we pick up the newer password when
			# we set new cookies. Should this be a function in lib_users?
			# (20101012/asc)
			#

			$GLOBALS['cfg']['user'] = users_get_by_id($GLOBALS['cfg']['user']['id']);

			login_do_login($GLOBALS['cfg']['user'], "/account/?password=1");
			exit;
		}
	}


	#
	# output
	#

	$smarty->display("page_account_password.txt");
?>
