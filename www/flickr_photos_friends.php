<?php

	include("include/init.php");

	loadlib("flickr_users");
	loadlib("flickr_push");
	loadlib("flickr_push_subscriptions");
	loadlib("flickr_push_subscriptions_urls");
	# loadlib("flickr_push_photos");

	login_ensure_loggedin($_SERVER['REQUEST_URI']);

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

		# $rsp = flickr_push_subscriptions_urls_create($topic_url);
		# dumper($rsp);

		# if ($rsp['ok']){

			$sub = array(
				'user_id' => $GLOBALS['cfg']['user']['id'],
				'topic_id' => $topic_id,
				# 'url_id' => $rsp['url']['id'],
			);

			$rsp = flickr_push_subscriptions_register_subscription($sub);

			if ($rsp['ok']){
				$sub = $rsp['subscription'];
			}

			dumper($rsp);
			exit();
		# }

	}

dumper($sub);
exit;
	$GLOBALS['smarty']->display("page_flickr_photos_friends.txt");
	exit();

?>
