<?php

	loadlib("flickr_push");
	loadlib("flickr_push_subscriptions");
	loadlib("flickr_push_photos");

	#################################################################

	# an api method for photos with recent activity?

	#################################################################

	function api_flickr_photos_friends_faves(){

		if (! $GLOBALS['cfg']['enable_feature_flickr_push']){
			api_output_error(999, "disabled");
		}

		if (! $GLOBALS['cfg']['flickr_push_enable_photos_friends_faves']){
			api_output_error(999, "disabled");
		}

		$topic_map = flickr_push_topic_map("string keys");
		$topic_id = $topic_map["contacts_faves"];

		$sub = flickr_push_subscriptions_get_by_user_and_topic($GLOBALS['cfg']['user'], $topic_id);

		if (! $sub){
			api_output_error(999, "no subscription");
		}

		$older_than = get_int32("older_than");

		$rsp = flickr_push_photos_for_subscription($sub, $older_than);

		if (! $rsp['ok']){
			api_output_error(999, $rsp['error']);
		}

		$out = array(
			'photos' => $rsp['rows'],
		);

		$more = array(
			'inline' => 1
		);

		api_output_ok($out, $more);
	}

	#################################################################
?>
