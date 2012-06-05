<?php

	include("../include/init.php");
	loadlib("god");

	features_ensure_enabled("flickr_push");

	loadlib("flickr_push");
	loadlib("flickr_backups");
	loadlib("flickr_push_photos");
	loadlib("flickr_push_subscriptions");

	$id = get_int32("id");
	$sub = flickr_push_subscriptions_get_by_id($id);

	if (! $sub){
		error_404();
	}

	$crumb_key = "delete_feed";
	$GLOBALS['smarty']->assign("crumb_key", $crumb_key);

	if ((post_str("delete") && (crumb_check($crumb_key)))){

		$feed_rsp = flickr_push_unsubscribe($sub);
		$GLOBALS['smarty']->assign("delete_feed", $feed_rsp);

		if ($feed_rsp['ok']){
			$sub_rsp = flickr_push_subscriptions_delete($sub);
			$GLOBALS['smarty']->assign("delete_sub", $sub_rsp);

			if ($sub_rsp['ok']){

				$redir = "{$GLOBALS['cfg']['abs_root_url']}god/push/subscriptions/{$sub['user_id']}/";
				header("location: {$redir}");
				exit();
			}
		}
	}

	$topic_map = flickr_push_topic_map();
	$sub['str_topic'] = $topic_map[$sub['topic_id']];

	if ($sub['last_update_details']){
		$sub['last_update_details'] = json_decode($sub['last_update_details'], "as hash");
	}

	$owner = users_get_by_id($sub['user_id']);
	$sub['owner'] = $owner;

	$photos = flickr_push_photos_for_subscription($sub);

	$is_push_backup = flickr_push_subscriptions_is_push_backup($sub);
	$GLOBALS['smarty']->assign("is_push_backup", $is_push_backup);

	$GLOBALS['smarty']->assign_by_ref("subscription", $sub);
	$GLOBALS['smarty']->assign_by_ref("photos", $photos['rows']);

	$GLOBALS['smarty']->display("page_god_push_subscription.txt");
	exit();
?>
