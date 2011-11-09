<?php

	include("include/init.php");

	loadlib("flickr_users");
	loadlib("flickr_photos");
	loadlib("flickr_faves");
	loadlib("flickr_urls");
	loadlib("flickr_dates");

	#

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

	#

	$more = array(
		'page' => get_int32("page"),
	);

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$GLOBALS['smarty']->assign("is_own", $is_own);

	$rsp = flickr_contacts_for_user($owner, $more);
	$contacts = array();

	foreach ($rsp['rows'] as $c){

		$contact = users_get_by_id($c['contact_id']);
		$contact['count_photos'] = flickr_photos_count_for_user($contact, $GLOBALS['cfg']['user']['id']);
		# $contact['count_photos'] = contacts_get_faves_count($contacts, $GLOBALS['cfg']['user']['id']);

		$contacts[] = $contact;
	}

	$GLOBALS['smarty']->assign_by_ref("owner", $owner);
	$GLOBALS['smarty']->assign_by_ref("contacts", $contacts);

	$pagination_url = flickr_urls_contacts_user($owner);
	$GLOBALS['smarty']->assign("pagination_url", $pagination_url);

	$GLOBALS['smarty']->display("page_flickr_contacts_user.txt");
	exit();
?>
