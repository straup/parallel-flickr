<?
	#
	# $Id$
	#

	include("include/init.php");

	if (!$GLOBALS['cfg']['enable_feature_signup']){
		$smarty->display('page_signup_disabled.txt');
		exit;
	}

	login_ensure_loggedout();


	#
	# carry this argument through
	#

	$smarty->assign('redir', request_str('redir'));


	#
	# are we signing up?
	#

	if (post_str('signup')){

		$ok = 1;

		$email		= post_str('email');
		$password	= post_str('password');
		$username	= post_str('username');
		$redir		= post_str('redir');

		$smarty->assign('email', $email);
		$smarty->assign('password', $password);
		$smarty->assign('username', $username);
		$smarty->assign('redir', $redir);


		#
		# all fields are in order?
		#

		if ((!strlen($email)) || (!strlen($password)) || (!strlen($username))){

			$smarty->assign('error_missing', 1);
			$ok = 0;
		}


		#
		# email available?
		#

		if ($ok && users_is_email_taken($email)){

			$smarty->assign('email', '');
			$smarty->assign('error_email_taken', 1);
			$ok = 0;
		}


		#
		# username available?
		#

		if ($ok && users_is_username_taken($username)){

			$smarty->assign('username', '');
			$smarty->assign('error_username_taken', 1);
			$ok = 0;
		}


		#
		# create account
		#

		if ($ok){

			$user = users_create_user(array(
				'username'	=> $username,
				'email'		=> $email,
				'password'	=> $password,
			));

			if ($user['id']){

				$redir = strlen($redir) ? $redir : '/';

				login_do_login($user, $redir);
				exit;
			}

			$smarty->assign('error_failed', 1);
			$ok = 0;
		}
	}


	#
	# output
	#

	$smarty->display('page_signup.txt');
?>