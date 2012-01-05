<?php

	include("include/init.php");

	loadlib("flickr_photos_archives");

	if ($path = get_str("path")){
		$flickr_user = flickr_users_get_by_path_alias($path);
	}

	else if ($nsid = get_str("nsid")){
		$flickr_user = flickr_users_get_by_nsid($nsid);
	}

	if (! $flickr_user){
		error_404();
	}

	$owner = users_get_by_id($flickr_user['user_id']);
	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;

	$GLOBALS['smarty']->assign_by_ref("owner", $owner);
	$GLOBALS['smarty']->assign("is_own", $is_own);

	$more = array(
		'viewer_id' => $GLOBALS['cfg']['user']['id'],
	);

	$context = get_str("context");
	$GLOBALS['smarty']->assign("context", $context);

	if ((! $context) || ($context == 'posted')){
		$years_posted = flickr_photos_archives_years_for_user($owner, array('context' => 'posted'));
		$GLOBALS['smarty']->assign_by_ref("posted", $years_posted);
	}

	if ((! $context) || ($context == 'taken')){
		$years_taken = flickr_photos_archives_years_for_user($owner, array('context' => 'taken'));
		$GLOBALS['smarty']->assign_by_ref("taken", $years_taken);
	}

	$GLOBALS['smarty']->display("page_flickr_photos_user_archives.txt");
	exit();

?>
