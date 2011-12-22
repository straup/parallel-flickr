<?php

	include("include/init.php");

	loadlib("flickr_users");
	loadlib("flickr_push");
	loadlib("flickr_push_subscriptions");
	loadlib("flickr_push_photos");

	login_ensure_loggedin($_SERVER['REQUEST_URI']);

	# this one is still full of weirdness (20111220/straup)
	error_disabled();

	if (! $GLOBALS['cfg']['enable_feature_flickr_push']){
		error_disabled();
	}

	if (! $GLOBALS['cfg']['flickr_push_enable_recent_activity']){
		error_disabled();
	}

	$topic_map = flickr_push_topic_map("string keys");
	$topic_id = $topic_map["my_photos"];

	$topic_args = array(
		'update_type' => 'comments,faves,tags,notes',
	);

	$sub = flickr_push_subscriptions_get_by_user_and_topic($GLOBALS['cfg']['user'], $topic_id, $topic_args);
	$GLOBALS['smarty']->assign_by_ref("subscription", $sub);

	dumper($sub);

	if (! $sub){

		if (! $GLOBALS['cfg']['flickr_push_enable_registrations']){
			error_disabled();
		}

		$sub = array(
			'user_id' => $GLOBALS['cfg']['user']['id'],
			'topic_id' => $topic_id,
			'topic_args' => $topic_args,
		);

		$rsp = flickr_push_subscriptions_register_subscription($sub);
		dumper($rsp);

		$GLOBALS['smarty']->assign("new_subscription", $rsp['ok']);
		$GLOBALS['smarty']->assign("subscription_ok", $rsp['ok']);
	}

	else {

		$now = time();

		$offset_hours = 24;
		$older_than = $now - ((60 * 60) * $offset_hours);

		$rsp = flickr_push_photos_for_subscription($sub, $older_than);
		dumper($rsp);
	}

	$GLOBALS['smarty']->display("page_flickr_photos_user_recent_activity.txt");
	exit();

?>
