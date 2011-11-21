<?php

	include("include/init.php");

	loadlib("flickr_photos");
	loadlib("flickr_photos_metadata");
	loadlib("flickr_photos_permissions");
	loadlib("flickr_geo_permissions");

	loadlib("flickr_users");
	loadlib("flickr_urls");
	loadlib("flickr_places");

	$photo_id = get_int64("id");

	if (! $photo_id){
		error_404();
	}

	$photo = flickr_photos_get_by_id($photo_id);

	if (! $photo['id']){
		error_404();
	}

	if ($photo['deleted']){
		$GLOBALS['smarty']->display("page_photo_deleted.txt");
		exit();
	}

	if (! flickr_photos_permissions_can_view_photo($photo, $GLOBALS['cfg']['user']['id'])){
		error_403();
	}

	$perms_map = flickr_photos_permissions_map();
	$photo['str_perms'] = $perms_map[$photo['perms']];

	$GLOBALS['smarty']->assign_by_ref("photo", $photo);

	$owner = users_get_by_id($photo['user_id']);
	$GLOBALS['smarty']->assign_by_ref("owner", $owner);

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$GLOBALS['smarty']->assign("is_own", $is_own);

	# context (next and previous)

	$context = get_str("context");

	if ($context == 'faves'){
		# please write me
	}

	else if ($context == 'place'){
		# please write me
	}

	else {
		$bookends = flickr_photos_get_bookends($photo, $GLOBALS['cfg']['user']['id']);
	}

	$GLOBALS['smarty']->assign_by_ref("before", $bookends['before']);
	$GLOBALS['smarty']->assign_by_ref("after", $bookends['after']);

	# meta, geo, etc.

	# $meta = flickr_photos_metadata_load($photo);
	# $GLOBALS['smarty']->assign_by_ref("metadata", $meta['data']);

	$photo['can_view_geo'] = ($photo['hasgeo'] && flickr_geo_permissions_can_view_photo($photo, $GLOBALS['cfg']['user']['id'])) ? 1 : 0;

	if ($photo['can_view_geo']){

		$geo_perms_map = flickr_geo_permissions_map();
		$photo['str_geoperms'] = $geo_perms_map[$photo['geoperms']];

		# NOTE: this has the potential to slow things down if the
		# Flickr API is being wonky. On the other hand if you're
		# just running this for yourself (or maybe a handful of
		# friends) it shouldn't be a big deal. Also, caching.

		if ($place = flickr_places_get_by_woeid($photo['woeid'])){
			$GLOBALS['smarty']->assign_by_ref("place", $place);
		}
	}

	$GLOBALS['smarty']->display("page_flickr_photo.txt");
	exit();
?>
