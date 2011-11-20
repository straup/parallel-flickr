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

	# TO DO: check to see if facet is apssed in as a q uery param

	$facet = get_str("facet");

	$placetypes = flickr_places_valid_placetypes();

	if ((! $facet) || (! flickr_places_is_valid_placetype($facet))){
		$rand = rand(1, count($placetypes));
		$facet = $placetypes[$rand - 1];
	}

	$mincount = 10;

	$GLOBALS['smarty']->assign_by_ref("placetypes", $placetypes);
	$GLOBALS['smarty']->assign("facet", $facet);
	$GLOBALS['smarty']->assign("mincount", $mincount);

	#

	$viewer_id = $GLOBALS['cfg']['user']['id'];

	$more = array(
		'mincount' => $mincount,
	);

	$rsp = flickr_photos_places_for_user_facet($owner, $facet, $viewer_id, $more);

	if ($rsp['ok']){

		# TO DO: fill me in...
		$locations = array();

		$GLOBALS['smarty']->assign_by_ref("facets", $rsp['facets']);
		$GLOBALS['smarty']->assign_by_ref("locations", $locations);
	}

	else {

		$GLOBALS['smarty']->assign("error", $rsp['error']);
	}

	# TO DO: pull in places without lots of photos

	$GLOBALS['smarty']->display("page_flickr_photos_user_places.txt");
	exit();

?>
