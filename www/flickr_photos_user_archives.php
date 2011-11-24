<?php

	include("include/init.php");

	loadlib("flickr_photos_archives");

	# TO DO: some functionality if solr is not enabled

	if ((! $GLOBALS['cfg']['enable_feature_solr']) || (! $GLOBALS['cfg']['enable_feature_archives'])){
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

	$more = array(
		'viewer_id' => $GLOBALS['cfg']['user']['id'],
	);

	flickr_photos_archives_photos_for_user($owner, $more);

	exit();

?>
