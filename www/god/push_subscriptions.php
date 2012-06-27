<?php

	include("../include/init.php");
	loadlib("god");

	features_ensure_enabled("flickr_push");

	loadlib("flickr_backups");
	loadlib("flickr_push");
	loadlib("flickr_push_subscriptions");

	$topic_map = flickr_push_subscriptions_topic_map();
	$GLOBALS['smarty']->assign_by_ref("topic_map", $topic_map);

	if ($user_id = get_int32("user_id")){

		$owner = users_get_by_id($user_id);

		if (! $owner){
			error_404();
		}

		$GLOBALS['smarty']->assign_by_ref("owner", $owner);
	}

	$is_backup_user = (($owner) && (flickr_backups_is_registered_user($owner))) ? 1 : 0;
	$GLOBALS['smarty']->assign("is_backup_user", $is_backup_user);

	if ($is_backup_user){

		$crumb_key = "create_feed";
		$GLOBALS['smarty']->assign("crumb_key", $crumb_key);

		if ((post_str("create") && (crumb_check($crumb_key)))){

			$topic_id = post_int32("topic_id");

			if (flickr_push_subscriptions_is_valid_topic_id($topic_id)){

				# HEY LOOK! THIS STILL DOESN'T DEAL WITH FEEDS THAT
				# NEED OR HAVE TOPIC ARGS (20120605/straup)

				# As a practical matter that just means that the
				# API call to register a subscription with
				# Flickr will fail. Since we're already
				# disabling these topics at the template layer I
				# am less inclined to also check here. If
				# someone is passing args that means they're
				# just doofing around and well, you know,
				# whatever... (20120612/straup)
	
				$sub = array(
					'user_id' => $owner['id'],
					'topic_id' => $topic_id
				);

				$rsp = flickr_push_subscriptions_register_subscription($sub);
				$GLOBALS['smarty']->assign_by_ref("create_sub", $rsp);
			}
		}
	}

	$more = array();

	if ($page = get_int32("page")){
		$more['page'] = $page;
	}

	if ($owner){
		$rsp = flickr_push_subscriptions_get_subscriptions_for_user($owner, $more);
	}

	else {
		$rsp = flickr_push_subscriptions_get_subscriptions($more);
	}

	$subs = array();

	foreach ($rsp['rows'] as $row){

		$row['owner'] = users_get_by_id($row['user_id']);
		$row['str_topic'] = $topic_map[$row['topic_id']]['label'];

		$row['is_push_backup'] = flickr_push_subscriptions_is_push_backup($row);
		$subs[] = $row;
	}

	$GLOBALS['smarty']->assign_by_ref("subscriptions", $subs);

	$GLOBALS['smarty']->display("page_god_push_subscriptions.txt");
	exit();
?>
