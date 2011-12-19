<?php

	include("include/init.php");

	loadlib("flickr_users");
	loadlib("flickr_push");
	loadlib("flickr_push_subscriptions");
	loadlib("flickr_push_photos");

	login_ensure_loggedin("/photos/friends/activity/");

	if (! $GLOBALS['cfg']['enable_feature_flickr_push']){
		error_disabled();
	}

	if (! $GLOBALS['cfg']['flickr_push_enable_photos_friends']){
		error_disabled();
	}

	$topic_map = flickr_push_topic_map("string keys");
	$topic_id = $topic_map["contacts_photos"];

	$sub = flickr_push_subscriptions_get_by_user_and_topic($GLOBALS['cfg']['user'], $topic_id);

	if (! $sub){

		if (! $GLOBALS['cfg']['flickr_push_enable_photos_friends_registrations']){
			error_disabled();
		}

		$sub = array(
			'user_id' => $GLOBALS['cfg']['user']['id'],
			'topic_id' => $topic_id,
		);

		$rsp = flickr_push_subscriptions_register_subscription($sub);

		$GLOBALS['smarty']->assign("new_subscription", $rsp['ok']);
		$GLOBALS['smarty']->assign("subscription_ok", $rsp['ok']);
	}

	else {

		$offset_hours = 8;
		$GLOBALS['smarty']->assign("offset_hours", $offset_hours);

		$older_than = time() - ((60 * 60) * $offset_hours);
		$rsp = flickr_push_photos_for_subscription($sub, $older_than);

		$users_names = array();
		$users_updated = array();
		$users_counts = array();
		$users_photos = array();

		$seen = array();

		foreach ($rsp['rows'] as $row){

			$nsid = $row['owner'];
			$created = $row['created'];

			$users_counts[$nsid] ++;
			$users_updated[$nsid] = max($users_updated[$nsid], $created);

			if (! isset($users_names[$nsid])){
				$users_names[$nsid] = $row['ownername'];
			}

			if (! is_array($users_photos[$nsid])){
				$users_photos[$nsid] = array();
			}

			if (isset($seen[$row['photo_id']])){
				continue;
			}

			$users_photos[$nsid]["{$created}.{$row['photo_id']}"] = $row;
			$seen[$row['photo_id']] = 1;
		}

		arsort($users_updated);
		arsort($users_photos);

		$GLOBALS['smarty']->assign_by_ref("users_updated", $users_updated);
		$GLOBALS['smarty']->assign_by_ref("users_counts", $users_counts);
		$GLOBALS['smarty']->assign_by_ref("users_names", $users_names);
		$GLOBALS['smarty']->assign_by_ref("users_photos", $users_photos);
	}

	$GLOBALS['smarty']->display("page_flickr_photos_friends_activity.txt");
	exit();

?>
