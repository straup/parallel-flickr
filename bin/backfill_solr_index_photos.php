<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	#

	include("include/init.php");

	loadlib("backfill");
	loadlib("flickr_photos");
	loadlib("flickr_photos_search");

	if (! $GLOBALS['cfg']['enable_feature_solr']){
		echo "search indexing is disabled, exiting";
		exit();
	}

	function index_photo($row, $more=array()){
		$photo = flickr_photos_get_by_id($row['id']);
		$rsp = flickr_photos_search_index_photo($photo);
	}

	$sql = "SELECT * FROM FlickrPhotos";
	backfill_db_users($sql, 'index_photo');

	exit();
?>
