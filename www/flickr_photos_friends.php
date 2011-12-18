<?php

	include("include/init.php");

	loadlib("flickr_users");
	loadlib("flickr_push");
	loadlib("flickr_push_subscriptions");
	loadlib("flickr_push_photos");

	login_ensure_loggedin("/photos/friends/");

	if (! $GLOBALS['cfg']['enable_feature_flickr_push']){
		error_disabled();
	}

	if (! $GLOBALS['cfg']['flickr_push_enable_photos_friends']){
		error_disabled();
	}

	$topic_map = flickr_push_topic_map("string keys");
	$topic_id = $topic_map["contacts_photos"];

	$topic_args = array(
		'update_type' => 'created',
	);

	$sub = flickr_push_subscriptions_get_by_user_and_topic($GLOBALS['cfg']['user'], $topic_id, $topic_args);

	if (! $sub){

		if (! $GLOBALS['cfg']['flickr_push_enable_photos_friends_registrations']){
			error_disabled();
		}

		$sub = array(
			'user_id' => $GLOBALS['cfg']['user']['id'],
			'topic_id' => $topic_id,
			'topic_args' => $topic_args,
		);

		$rsp = flickr_push_subscriptions_register_subscription($sub);

		$GLOBALS['smarty']->assign("new_subscription", $rsp['ok']);
		$GLOBALS['smarty']->assign("subscription_ok", $rsp['ok']);
	}

	else {

		$now = time();

		$offset_hours = 8;
		$GLOBALS['smarty']->assign("offset_hours", $offset_hours);

		$older_than = $now - ((60 * 60) * $offset_hours);
		$rsp = flickr_push_photos_for_subscription($sub, $older_than);

		$one_minute = 60;
		$one_hour = $one_minute * 60;

		$half_hour = array();
		$two_hours = array();
		$four_hours = array();
		$eight_hours = array();

		foreach ($rsp['rows'] as $row){

			$diff = $now - $row['created'];

			if ($diff <= ($one_minute * 30)){
				$half_hour[] = $row;
			}

			else if ($diff <= ($one_hour * 2)){
				$two_hours[] = $row;
			}

			else if ($diff <= ($one_hour * 4)){
				$four_hours[] = $row;
			}

			else {
				$eight_hours[] = $row;
			}

		}

		$GLOBALS['smarty']->assign_by_ref("half_hour", $half_hour);
		$GLOBALS['smarty']->assign_by_ref("two_hours", $two_hours);
		$GLOBALS['smarty']->assign_by_ref("four_hours", $four_hours);
		$GLOBALS['smarty']->assign_by_ref("eight_hours", $eight_hours);
	}

	$GLOBALS['smarty']->display("page_flickr_photos_friends.txt");
	exit();

?>
