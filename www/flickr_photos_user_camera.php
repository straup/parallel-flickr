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

	$make = get_str("make");
	$model = get_str("model");

	$GLOBALS['smarty']->assign("camera_make", $make);
	$GLOBALS['smarty']->assign("camera_model", $model);

	$more = array(
		'viewer_id' => $GLOBALS['cfg']['user']['id'],
	);

	if ($page = get_int32("page")){
		$more['page'] = $page;
	}

	$rsp = flickr_photos_cameras_photos_for_user($owner, $make, $model, $more);

	if ($rsp['ok']){

		$GLOBALS['smarty']->assign_by_ref("photos", $rsp['rows']);

		# Pull in any other models for this camera. It is interesting to
		# think about faceting on focal length, etc. here but we're going
		# to punt on this until the rest of the search stuff is done.
		# (20111122/straup)

		$models_more = array(
			'make' => $make,
		);

		$models_rsp = flickr_photos_cameras_models_for_user($owner, $models_more);

		if ($models_rsp['ok']){
			$GLOBALS['smarty']->assign_by_ref("models", $models_rsp['cameras'][$make]['models']);
		}


		$pagination_url = flickr_urls_photos_user_camera($owner, $make, $model);
		$GLOBALS['smarty']->assign("pagination_url", $pagination_url);

	}

	else {

		$GLOBALS['smarty']->assign("error", $rsp['error']);
	}

	$GLOBALS['smarty']->display("page_flickr_photos_user_camera.txt");
	exit();

?>
