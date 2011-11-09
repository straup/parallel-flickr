<?
	#
	# $Id$
	#

	include("include/init.php");

	login_ensure_loggedin();


	#
	# generate a crumb
	#

	$crumb_key = 'account_delete';
	$smarty->assign('crumb_key', $crumb_key);


	#
	# delete account?
	#

	if (post_str('delete') && crumb_check($crumb_key)){

		if (post_str('confirm')){

			$rsp = users_delete_user($GLOBALS['cfg']['user']);

			if ($rsp['ok']){
				login_do_logout();

				$smarty->display('page_account_delete_done.txt');
				exit;
			}

			$smarty->assign('error_deleting', 1);

			$smarty->display('page_account_delete.txt');
			exit;
		}

		$smarty->display('page_account_delete_confirm.txt');
		exit;
	}


	#
	# output
	#

	$smarty->display("page_account_delete.txt");
	exit;
?>