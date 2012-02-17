<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	#

	include("include/init.php");
	loadlib("flickr_backups");

	if (! $GLOBALS['cfg']['enable_feature_backups']){
		echo "backups are currently disabled\n";
		exit();
	}

	$map = flickr_backups_type_map("string keys");

	foreach (flickr_backups_users() as $user){

		$backups = flickr_backups_for_user($user);

		foreach ($map as $label => $type_id){

			if (isset($backups[$label])){
				continue;
			}

			echo "backup", "register '{$user['username']}' for {$label} backups\n";

			$rsp = flickr_backups_create($user, $type_id);
		}
	}

?>
