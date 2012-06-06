<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	#

	include("include/init.php");

	loadlib("cli");
	loadlib("flickr_backups");
	loadlib("flickr_push");
	loadlib("flickr_push_subscriptions");
	
	$features = array(
		"backups",
		"flickr_push",
		"flickr_push_backups",
	);

	if (! features_is_enabled($features)){
		echo "backups are currently disabled\n";
		exit();
	}

	$spec = array(
		"url" => array("flag" => "u", "required" => 1, "help" => "the *root* URL of your copy of parallel-ogram (the need to specify this here is not a feature...)")
	);

	$opts = cli_getopts($spec);
	$topic = $opts['topic'];

	# This sucks to have to do but I am uncertain what the
	# better alternative is right now... (20120601/straup)

	$root = rtrim($opts['url'], '/') . "/";	
	$GLOBALS['cfg']['abs_root_url'] = $root;	

	log_info("set 'abs_root_url' to '{$GLOBALS['cfg']['abs_root_url']}'");

	$topic_map = flickr_push_topic_map("string keys");

	$topics = array(
		"my_photos",
		"my_faves"
	);

	foreach (flickr_backups_users() as $user){

		foreach ($topics as $topic){

			$sub = array(
				'user_id' => $user['id'],
				'topic_id' => $topic_map[$topic],
			);

			$rsp = flickr_push_subscriptions_register_subscription($sub);

			log_info("[{$user['username']}] {$topic}: {$rsp['ok']}");

			if (! $rsp['ok']){
				log_info("[{$user['username']}] {$topic}: {$rsp['error']}");
			}
		}
	}

	log_info("- done -");
	exit();
?>
