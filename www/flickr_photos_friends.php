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
		$older_than = $now - ((60 * 60) * $offset_hours);

		$rsp = flickr_push_photos_for_subscription($sub, $older_than);

		$one_minute = 60;
		$one_hour = $one_minute * 60;

		$users = array(
			'30 minutes' => array(),
			'two hours' => array(),
			'four hours' => array(),
			'eight hours' => array(),
		);

		$meta = array();

		$seen = array();

		foreach ($rsp['rows'] as $row){

			$diff = $now - $row['created'];
			$nsid = $row['owner'];

			if ($diff <= ($one_minute * 30)){
				$timepie = '30 minutes';
			}

			else if ($diff <= ($one_hour * 2)){
				$timepie = 'two hours';
			}

			else if ($diff <= ($one_hour * 4)){
				$timepie = 'four hours';
			}

			else {
				$timepie = 'eight hours';
			}

			if (($last_timepie) && ($last_timepie != $timepie)){

				foreach ($users[$last_timepie] as $nsid => $ignore){
					$seen[$nsid] = 1;
				}
			}

			$last_timepie = $timepie;

			if (isset($seen[$nsid])){
				continue;
			}

			$users[$timepie][$nsid] ++;

			if (! isset($meta[$nsid])){

				$meta[$nsid] = array(
					'username' => $row['ownername'],
					'hex' => substr(md5($nsid), 0, 6),
					'images' => array(),
				);
			}

			$meta[$nsid]['images'][] = $row;
		}

		$GLOBALS['smarty']->assign_by_ref("users", $users);
		$GLOBALS['smarty']->assign_by_ref("meta", $meta);
	}

	$GLOBALS['smarty']->display("page_flickr_photos_friends.txt");
	exit();

?>
