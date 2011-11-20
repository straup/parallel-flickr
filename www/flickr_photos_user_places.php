<?php

	include("include/init.php");

	loadlib("flickr_places");
	loadlib("flickr_photos_places");

error_disabled();

	if (! $GLOBALS['cfg']['enable_feature_solr']){
		error_disabled();
	}

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

	#

	#

	$GLOBALS['smarty']->display("page_flickr_photos_user_places.txt");
	exit();

?>
