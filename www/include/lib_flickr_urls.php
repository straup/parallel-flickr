<?php

	loadlib("flickr_photos");
	loadlib("flickr_users");

	#################################################################

	function flickr_urls_photo_thumb_flickr(&$photo){
		return "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}_t.jpg";
	}

	#################################################################

	function flickr_urls_photo_static(&$photo){

		$secret = $photo['secret'];
		$sz = "z";
		$ext = "jpg";

		$root = $GLOBALS['cfg']['flickr_static_url'];
		$path = flickr_photos_id_to_path($photo['id']);
		$fname = "{$photo['id']}_{$secret}_{$sz}.{$ext}";

		return $root . $path . "/" . $fname;
	}

	#################################################################

	function flickr_urls_photo_original(&$photo){

		$secret = $photo['originalsecret'];
		$sz = "o";
		$ext = $photo['originalformat'];

		$root = $GLOBALS['cfg']['flickr_static_url'];
		$path = flickr_photos_id_to_path($photo['id']);
		$fname = "{$photo['id']}_{$secret}_{$sz}.{$ext}";

		return $root . $path . "/" . $fname;
	}

	#################################################################

	function flickr_urls_photo_page(&$photo){

		$user = users_get_by_id($photo['user_id']);
		$root = flickr_urls_photos_user($user);

		return $root . $photo['id'] . "/";
	}

	#################################################################

	function flickr_urls_photo_page_flickr(&$photo){

		$flickr_user = flickr_users_get_by_user_id($photo['user_id']);
		return "http://www.flickr.com/photos/{$flickr_user['nsid']}/{$photo['id']}/";
	}

	#################################################################

	function flickr_urls_photos_user(&$user){

		$flickr_user = flickr_users_get_by_user_id($user['id']);

		if ($GLOBALS['cfg']['enable_feature_path_alias_redirects']){
			loadlib("flickr_users_path_aliases");
			$alias = flickr_users_path_aliases_current_for_user($user);
		}

		else {
			$alias = $flickr_user['path_alias'];
		}

		if (! $alias){
			$alias = $flickr_user['nsid'];
		}

		$root = $GLOBALS['cfg']['abs_root_url'];

		return $root . "photos/" . $alias . "/";
	}

	#################################################################

	function flickr_urls_photos_user_places(&$user){

		$user_url = flickr_urls_photos_user($user);
		return "{$user_url}places/";
	}

	#################################################################

	function flickr_urls_photos_user_place(&$user, &$place){

		$places = flickr_urls_photos_user_places($user);
		return "{$places}{$place['woeid']}/";	
	}

	#################################################################

	function flickr_urls_photos_user_cameras(&$user){

		$user_url = flickr_urls_photos_user($user);
		return "{$user_url}cameras/";	
	}

	#################################################################

	function flickr_urls_photos_user_camera(&$user, $make, $model=null){

		$root = flickr_urls_photos_user_cameras($user);

		$enc_make = urlencode($make);
		$url = "{$root}{$enc_make}/";

		if ($model){
			$enc_model = urlencode($model);
			$url .= "{$enc_model}/";
		}

		return $url;
	}

	#################################################################

	function flickr_urls_contacts_user(&$user){

		return flickr_urls_photos_user($user) . "contacts/";
	}

	#################################################################

	function flickr_urls_faves_user(&$user){

		return flickr_urls_photos_user($user) . "faves/";
	}

	#################################################################

?>
