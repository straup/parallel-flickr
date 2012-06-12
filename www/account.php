<?
	#
	# $Id$
	#

	include("include/init.php");
	loadlib("flickr_backups");

	login_ensure_loggedin();

	$is_backup_user = flickr_backups_is_registered_user($GLOBALS['cfg']['user'], "ensure enabled");
	$GLOBALS['smarty']->assign("is_backup_user", $is_backup_user);

	$GLOBALS['smarty']->display("page_account.txt");
?>
