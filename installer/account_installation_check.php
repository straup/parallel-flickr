<?php

	include("include/init.php");

	# In theory, this should just be checked right after installation but before users start signing up
	# as it is purely an administration issue.
	login_ensure_loggedin(); 
	
	# Per the README, if you are using Flickr PuSH backups (for photo uploads and faves),
	# your static directory will need to be writable by the web server.
	# Instead of doing tricky-dicky stuff to figure out the user of the web server process
	# and various Unix privileges, we'll simply try to write to the directory, if it works,
	# thumbs up and congratulations. Not sure what the best way to do this for S3, without poking
	# at it (and that case should be a lot more obvious if it doesn't work.


	$is_s3 = false;
	if ($GLOBALS['enable_feature_storage_s3']) {
		$is_s3 = true;
	} else {

		$path = $GLOBALS['cfg']['flickr_static_path'] . "/test";

		$can_write = @mkdir($path, 0755, true);

		if ($can_write) {
			$cleaned_up = rmdir($path);
		}
	}

	$GLOBALS['smarty']->assign('is_s3', $is_s3);
	$GLOBALS['smarty']->assign('can_write', $can_write);
	$GLOBALS['smarty']->assign('cleaned_up', $cleaned_up);

	$GLOBALS['smarty']->display("page_account_installation_check.txt");
