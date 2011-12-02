<?php

	include("include/init.php");

	loadlib("flickr_places");
	loadlib("flickr_photos_places");

	if ((! $GLOBALS['cfg']['enable_feature_solr']) || (! $GLOBALS['cfg']['enable_feature_places'])){
		error_disabled();
	}

	$flickr_user = flickr_users_get_by_url();
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

	$placetypes = flickr_places_valid_placetypes();
	$hier = array();

	# put this in _get_by_woeid? probably...

	foreach ($placetypes as $type){

		if (isset($place[$type])){

			$woeid = $place[$type]['woeid'];

			$parts = explode(",", $place[$type]['_content']);
			$name = trim($parts[0]);

			$hier[] = array(
				'woeid' => $woeid,
				'placetype' => $type,
				'name' => $name,
			);
		}
	}

	$hier = array_reverse($hier);

	$GLOBALS['smarty']->assign_by_ref("place", $place);
	$GLOBALS['smarty']->assign_by_ref("hierarchy", $hier);

	# now get the photos

	$more = array(
		'viewer_id' => $GLOBALS['cfg']['user']['id'],
	);

	if ($page = get_int32("page")){
		$more['page'] = $page;
	}

	$rsp = flickr_photos_places_for_user($owner, $place, $more);

	if (! $rsp['ok']){
		$GLOBALS['cfg']['error'] = $rsp['error'];
	}

	else {
		$pagination_url = flickr_urls_photos_user_place($owner, $place);
		$GLOBALS['smarty']->assign_by_ref("photos", $rsp['rows']);
		$GLOBALS['smarty']->assign("pagination_url", $pagination_url);
	}

	# go!

	$GLOBALS['smarty']->display("page_flickr_photos_user_place.txt");
	exit();

?>
