<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	#

	echo "this is not the droid you are looking for";
	exit();

	include("include/init.php");

	loadlib("backfill");
	loadlib("flickr_photos");
	loadlib("flickr_photos_import");

	function check_photo($row, $more=array()){

	}

	$sql = "SELECT * FROM FlickrPhotos";
	backfill_db_users($sql, 'check_photo');

	exit();
?>
