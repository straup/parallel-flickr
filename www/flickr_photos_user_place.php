<?php

	include("include/init.php");

	loadlib("flickr_places");
	loadlib("flickr_photos_places");

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

	$woeid = get_int32("woeid");

	if (! $woeid){
		error_404();
	}

	$place = flickr_places_get_by_woeid($woeid);

	if (! $place){
		error_404();
	}

	$GLOBALS['smarty']->assign_by_ref("place", $place);

	$more = array();

	if ($page = get_int32("page")){
		$more['page'] = $page;
	}

	$viewer_id = $GLOBALS['cfg']['user']['id'];

	# TO DO: check for errors

	$rsp = flickr_photos_places_for_user($owner, $place, $viewer_id, $more);
	$GLOBALS['smarty']->assign_by_ref("photos", $rsp['rows']);

	# pagination stuff

	$pagination_url = flickr_urls_photos_user_place($owner, $place);
	$GLOBALS['smarty']->assign("pagination_url", $pagination_url);

	# go!

	$GLOBALS['smarty']->display("page_flickr_photos_user_place.txt");
	exit();

?>
