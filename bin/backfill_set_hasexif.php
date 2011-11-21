<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	#

	include("include/init.php");

	loadlib("backfill");
	loadlib("flickr_photos");
	loadlib("flickr_photos_exif");

	function check_photo($row, $more=array()){

		$photo = flickr_photos_get_by_id($row['id']);
		$more = array('force' => 1);

		$hasexif = flickr_photos_exif_has_exif($photo, $more);
		# echo "photo {$photo['id']} has exif: {$hasexif}\n";

		if ($hasexif){

			$update = array('hasexif' => 1);
			$rsp = flickr_photos_update_photo($photo, $update);

			if (! $rsp['ok']){
				echo "ack! failed to update {$photo['id']}: {$rsp['error']}\n";
			}
		}
	}

	$sql = "SELECT * FROM FlickrPhotos";
	backfill_db_users($sql, 'check_photo');

	exit();
?>
