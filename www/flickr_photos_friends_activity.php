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

	# not clear how/why this is necessary (20121202/straup)

	if (($sub) && ($last_update = $sub['last_update'])){

		if ((time() - $last_update) > 86400){

			$rsp = flickr_push_subscriptions_delete($sub);

			if ($rsp['ok']){
				$GLOBALS['smarty']->assign("last_update", $last_update);
				$GLOBALS['smarty']->assign("reset_subscription", 1);
				$sub = null;
			}
		}
	}

	$GLOBALS['smarty']->assign_by_ref("subscription", $sub);

	if (! $sub){

		if (! $GLOBALS['cfg']['flickr_push_enable_registrations']){
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

		$update_map = flickr_push_update_types_map("rollup by type");

		$pretty_map = array(
			'geotagged' => 'geo',
			'faved' => 'faved',
			'administrivia' => 'photo_url',
		);

		$update_type = get_str("update_type");

		if (isset($pretty_map[$update_type])){
			$update_type = $pretty_map[$update_type];
		}

		else if (! isset($update_map[$update_type])){
			$update_type = null;
		}

		else {}

		$GLOBALS['smarty']->assign_by_ref("update_map", $update_map);
		$GLOBALS['smarty']->assign_by_ref("update_type", $update_type);

		foreach ($rsp['rows'] as $row){

			if (($update_type) && ($row['update_type'] != $update_type)){
				continue;
			}

			$nsid = $row['owner'];
			$created = $row['created'];

			if (! isset($users_names[$nsid])){
				$users_names[$nsid] = $row['ownername'];
			}

			if (! is_array($users_photos[$nsid])){
				$users_photos[$nsid] = array();
			}

			if (isset($seen[$row['photo_id']])){
				continue;
			}

			$users_counts[$nsid] ++;
			$users_updated[$nsid] = max($users_updated[$nsid], $created);

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
