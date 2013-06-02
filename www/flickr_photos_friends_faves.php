<?php

	# Basically this just stopped working one day. Which sucks because it
	# was the best thing ever and now I am going to have to fuck around and
	# recreate it as on TV. (20130602/straup)

	include("include/init.php");

	loadlib("flickr_users");
	loadlib("flickr_push");
	loadlib("flickr_faves");
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

		$rsp = flickr_push_photos_for_subscription($sub, $limit);
		$photos = $rsp['rows'];

		$count = count($photos);

		for ($i=0; $i < $count; $i++){
			$photo_id = $photos[$i]['photo_id'];
			$faved = flickr_faves_is_faved_by_user($GLOBALS['cfg']['user'], $photo_id);
			$photos[$i]['is_faved'] = $faved;
		}

		$GLOBALS['smarty']->assign_by_ref("photos", $photos);
	}

	$flickr_user = flickr_users_get_by_user_id($GLOBALS['cfg']['user']['id']);
	$can_fave = flickr_users_has_token_perms($flickr_user, "write");
	$GLOBALS['smarty']->assign("can_fave", $can_fave);

	$GLOBALS['smarty']->display("page_flickr_photos_friends_faves.txt");
	exit();

?>
