<?php

	include("include/init.php");

	loadlib("flickr_places");
	loadlib("flickr_photos_cameras");

	if ((! $GLOBALS['cfg']['enable_feature_solr']) || (! $GLOBALS['cfg']['enable_feature_cameras'])){
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

	# TO DO: get camera stuff here...

	#

	$viewer_id = $GLOBALS['cfg']['user']['id'];

	$more = array(
		'mincount' => $mincount,
	);

	$rsp = flickr_photos_cameras_for_user($owner, $viewer_id, $more);

	if ($rsp['ok']){

		$GLOBALS['smarty']->assign_by_ref("cameras", $rsp['facets']);
	}

	else {

		$GLOBALS['smarty']->assign("error", $rsp['error']);
	}

	$GLOBALS['smarty']->display("page_flickr_photos_user_cameras.txt");
	exit();

?>
