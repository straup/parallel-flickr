<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	#

	include("include/init.php");

	loadlib("backfill");
	loadlib("flickr_users_path_aliases");

	function set_path_alias($flickr_user, $more=array()){

		if ($flickr_user['path_alias'] == ''){
			return;
		}

		$user = users_get_by_id($flickr_user['user_id']);

		flickr_users_path_aliases_create($user, $flickr_user['path_alias']);
	}

	$sql = "SELECT * FROM FlickrUsers";
	backfill_db_users($sql, 'set_path_alias');

	exit();
?>
