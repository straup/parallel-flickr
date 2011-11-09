<?
	#
	# $Id$
	#

	include("include/init.php");

	if (!$GLOBALS['cfg']['enable_feature_signin']){
		$smarty->display('page_signin_disabled.txt');
		exit;
	}

	login_ensure_loggedout();


	#
	# pass through
	#

	$redir = request_str('redir');
	$smarty->assign('redir', $redir);


	#
	# try and sign in?
	#

	if (post_str('signin')){

		$email		= post_str('email');
		$password	= post_str('password');

		$smarty->assign('email', $email);

		$ok = 1;


		#
		# required fields?
		#

		if ((!strlen($email)) || (!strlen($password))){

			$smarty->assign('error_missing', 1);
			$ok = 0;
		}


		#
		# user exists?
		#

		if ($ok){
			$user = users_get_by_email($email);

			if (!$user['id']){

				$smarty->assign('error_nouser', 1);
				$ok = 0;
			}
		}


		#
		# users deleted?
		#

		if ($ok && $user['deleted']){

			$smarty->assign('error_deleted', 1);
			$ok = 0;
		}


		#
		# password match
		#

		if ($ok){
			$enc_password = login_encrypt_password($password, $GLOBALS['cfg']['crypto_password_secret']);

			if ($enc_password != $user['password']){

				$smarty->assign('error_password', 1);
				$ok = 0;
			}
		}


		#
		# it's all good - sign in
		#

		if ($ok){
			$redir = ($redir) ? $redir : '/';

			login_do_login($user, $redir);
			exit;
		}
	}


	#
	# output
	#

	$smarty->display('page_signin.txt');
?>