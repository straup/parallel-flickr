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

	$more = array(
		'viewer__id' => $GLOBALS['cfg']['user']['id'],
	);

	# how are we bucketing the dates?

	$user_context = get_str("context");
	$db_context = ($user_context == 'posted') ? 'dateupload' : 'datetaken';

	$more['context'] = $db_context;
	$GLOBALS['smarty']->assign("context", $user_context);

	# how big of a time slice will we use?

	$gap = "+1YEAR";

	# now get the bookends for this user (and viewer)

	$rsp = flickr_photos_get_bookends_for_user($owner, $more);

	if ($rsp['ok']){

		$start = $rsp['start'];
		$end = $rsp['end'];

		$rsp = flickr_photos_archives_timepies_for_user($owner, $db_context, $start, $end, $gap, $more);

		$GLOBALS['smarty']->assign_by_ref("dates", $rsp[$db_context]);
		$GLOBALS['smarty']->assign_by_ref("details", $rsp['details']);
	}

	else {

		$GLOBALS['smarty']->assign("error", $rsp['error']);
	}

	$GLOBALS['smarty']->display("page_flickr_photos_user_archives.txt");
	exit();

?>
