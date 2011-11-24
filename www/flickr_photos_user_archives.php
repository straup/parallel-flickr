<?php

	include("include/init.php");

	loadlib("flickr_photos_archives");

	# TO DO: some basic functionality even if solr is not enabled

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

	$facet = "date_posted";
	$start = "2012-01-01 00:00:00";
	$end = "2012-12-31 23:59:59";
	$gap = "+7DAYS";

	$more = array(
		'viewer_id' => $GLOBALS['cfg']['user']['id'],
	);

	$rsp = flickr_photos_archives_photos_for_user($owner, $facet, $start, $end, $gap, $more);

	dumper($rsp);
	exit();

?>
