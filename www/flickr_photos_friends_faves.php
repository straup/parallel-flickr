<?php

	include("include/init.php");

	loadlib("flickr_users");
	loadlib("flickr_push");
	loadlib("flickr_push_subscriptions");
	loadlib("flickr_push_photos");

	login_ensure_loggedin("/photos/friends/faves/");

	if (! $GLOBALS['cfg']['enable_feature_flickr_push']){
		error_disabled();
	}

	if (! $GLOBALS['cfg']['flickr_push_enable_photos_friends_faves']){
		error_disabled();
	}

	$topic_map = flickr_push_topic_map("string keys");
	$topic_id = $topic_map["contacts_faves"];

	$sub = flickr_push_subscriptions_get_by_user_and_topic($GLOBALS['cfg']['user'], $topic_id);

	if (! $sub){

		if (! $GLOBALS['cfg']['flickr_push_enable_photos_friends_faves_registrations']){
			error_disabled();
		}

		$sub = array(
			'user_id' => $GLOBALS['cfg']['user']['id'],
			'topic_id' => $topic_id,
		);

		$rsp = flickr_push_subscriptions_register_subscription($sub);

		dumper($rsp);
		exit();
	}

dumper($sub);
exit;
	$GLOBALS['smarty']->display("page_flickr_photos_friends_faves.txt");
	exit();

?>
