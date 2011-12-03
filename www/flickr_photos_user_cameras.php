<?php

	include("include/init.php");

	loadlib("flickr_places");
	loadlib("flickr_photos_cameras");

	if ((! $GLOBALS['cfg']['enable_feature_solr']) || (! $GLOBALS['cfg']['enable_feature_cameras'])){
		error_disabled();
	}

	$flickr_user = flickr_users_get_by_url();
	$owner = users_get_by_id($flickr_user['user_id']);

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;

	$GLOBALS['smarty']->assign_by_ref("owner", $owner);
	$GLOBALS['smarty']->assign("is_own", $is_own);

	#

	$more = array(
		'viewer_id' => $GLOBALS['cfg']['user']['id'],
	);

	$rsp = flickr_photos_cameras_models_for_user($owner, $more);

	if ($rsp['ok']){

		$GLOBALS['smarty']->assign_by_ref("cameras", $rsp['cameras']);
	}

	else {

		$GLOBALS['smarty']->assign("error", $rsp['error']);
	}

	$GLOBALS['smarty']->display("page_flickr_photos_user_cameras.txt");
	exit();

?>
