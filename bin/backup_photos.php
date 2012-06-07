<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	#

	include("include/init.php");
	loadlib("flickr_backups");
	
	if (! features_is_enabled("backups")){
		echo "backups are currently disabled\n";
		exit();
	}

	foreach (flickr_backups_users() as $user){
		log_info("backup photos for {$user['username']}");
		$rsp = flickr_backups_get_photos($user);
		dumper($rsp);
	}

?>
