<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	#

	include("include/init.php");
	loadlib("flickr_backups");

	foreach (flickr_backups_users() as $user){
		$rsp = flickr_backups_get_faves($user);
	}

?>
