<?
	#
	# $Id$
	#

	include("include/init.php");
	loadlib("flickr_backups");

	login_ensure_loggedin();

	$is_backup_user = flickr_backups_is_registered_user($GLOBALS['cfg']['user'], "ensure enabled");
	$GLOBALS['smarty']->assign("is_backup_user", $is_backup_user);

	$can_backup = $is_backup_user;

	# See this? It's a bit confusing. The first thing to understand is that
	# we're using invites codes as a general purpose shoehorn/check for
	# whether or not a user can register to back up their photos. This has
	# the potential for becoming a confusing mess but since the list of
	# things which may need to blocked by invites are few and only really
	# exist in the some abstract future possibility soup factory future so
	# we're just not going to worry about until it actually happens. Still
	# with me? Okay, so the point here is that we're trying to figure out
	# whether or not to show the link to the flickr backups page. If the
	# user has already registered then it's obvious. If not then check to
	# see if we're even using feature flags. If not, then show the link. If
	# they are (enabled) then we need to see whether or not the user has a
	# valid invite code set. Not awesome and suggests that maybe we need to
	# set another/different feature flag but, for now, it works.
	# (20120613/straup)
	
	if (! $can_backup){

		loadlib("invite_codes");

		if (! $GLOBALS['cfg']['enable_feature_invite_codes']){
			$can_backup = 1;
		}

		elseif (invite_codes_get_by_cookie()){
			$can_backup = 1;
		}

		else {
			$can_backup = 0;
		}
	}

	$GLOBALS['smarty']->assign("can_backup", $can_backup);

	$GLOBALS['smarty']->display("page_account.txt");
	exit();
?>
