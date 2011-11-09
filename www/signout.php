<?
	#
	# $Id$
	#

	include("include/init.php");

	login_ensure_loggedin();


	#
	# crumb key
	#

	$crumb_key = 'logout';
	$smarty->assign("crumb_key", $crumb_key);


	#
	# sign out?
	#

	if (post_isset('done') && crumb_check($crumb_key)){

		login_do_logout();

		$smarty->display('page_signout_done.txt');
		exit;
	}


	#
	# output
	#

	$smarty->display("page_signout.txt");
?>
