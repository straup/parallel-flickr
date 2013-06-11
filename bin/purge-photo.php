<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	include("include/init.php");
	loadlib("flickr_photos");

	loadlib("cli");

	$photo_id = 0;

	echo "THIS TOOL WILL PURGE A PHOTO FROM PARALLEL-FLICKR\n";
	echo "PAUSING FOR A MOMENT SO YOU CAN CONSIDER THAT IDEA\n";

	sleep(10);

	echo "I STILL DON'T WORK";
	exit();

	$photo = flickr_photos_get_by_id($photo_id);

	if (! $photo){
		exit();
	}

	# delete from FlickrPhotosLookup

	# delete from FlickrPhotos

	# delete from Solr

	# delete files:
	# original photo
	# resized photos
	# info
	# comments


?>
