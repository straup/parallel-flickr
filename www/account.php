<?
	#
	# $Id$
	#

	include("include/init.php");

	login_ensure_loggedin("/account");


	#
	# output
	#

	$smarty->display("page_account.txt");
?>