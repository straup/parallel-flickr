<?php

	loadlib("flickr_photos");
	loadlib("flickr_users");

	#################################################################

	function flickr_urls_photo_static_root(){

		if ($GLOBALS['cfg']['storage_provider'] == 's3'){
			$bucket = storage_s3_bucket();
			$root = s3_get_bucket_url($bucket);
		}

		else {
			$root = $GLOBALS['cfg']['abs_root_url'] . $GLOBALS['cfg']['flickr_static_url'];
		}

		return $root;
	}

	#################################################################

	function flickr_urls_photo_thumb_flickr(&$photo){

		if (! $photo['farm']){
			return flickr_urls_photo_static($photo, array('size' => 't'));
		}

		return "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}_t.jpg";
	}

	#################################################################

	function flickr_urls_photo_original(&$photo){
		return flickr_urls_photo_static($photo, array('size' => 'o'));
	}

	#################################################################

	function flickr_urls_photo_static(&$photo, $more=array()){

		$defaults = array(
			'size' => 'z',
		);

		$more = array_merge($defaults, $more);

		$root = flickr_urls_photo_static_root();
		$path = flickr_photos_path($photo, $more);

		return $root . $path;
	}

	#################################################################

	function flickr_urls_photo_page(&$photo){

		$user = users_get_by_id($photo['user_id']);
		$root = flickr_urls_photos_user($user);

		return $root . $photo['id'] . "/";
	}

	#################################################################

	function flickr_urls_photos_user_flickr(&$user){

		# note: just always use the NSID

		$flickr_user = flickr_users_get_by_user_id($user['id']);
		return "http://www.flickr.com/photos/{$flickr_user['nsid']}/";
	}

	#################################################################

	function flickr_urls_photo_page_flickr(&$photo){

		if (! flickr_photos_is_on_flickr($photo)){
			return;
		}

		$user = users_get_by_id($photo['user_id']);
		$root = flickr_urls_photos_user_flickr($user);

		return $root . "{$photo['id']}/";
	}

	#################################################################

	# this appears to not be working... (20130603/straup)

	function flickr_urls_photo_page_flickr_short(&$photo){

		if (! flickr_photos_is_on_flickr($photo)){
			return;
		}

		loadlib("base58");

		$code = base58_encode($photo['id']);
		return "http://flic.kr/p/{$code}/";
	}

	#################################################################

	function flickr_urls_photos_user(&$user){

		$alias = flickr_urls_path_alias_for_user($user);

		$root = $GLOBALS['cfg']['abs_root_url'];
		return $root . "photos/" . $alias . "/";
	}

	#################################################################

	function flickr_urls_photos_user_places(&$user){

		$user_url = flickr_urls_photos_user($user);
		$url = "{$user_url}places/";

		return $url;
	}

	#################################################################

	function flickr_urls_photos_user_place(&$user, &$place, $geo_context=0){

		$places = flickr_urls_photos_user_places($user);
		$url = "{$places}{$place['woeid']}/";	

		if ($geo_context){
			loadlib("flickr_photos_geo");
			$map = flickr_photos_geo_context_map();
			$url .= "{$map[$geo_context]}/";
		}

		return $url;
	}

	#################################################################

	function flickr_urls_photos_user_archives(&$user, $context=null){

		$user_url = flickr_urls_photos_user($user);
		$url = "{$user_url}archives/";

		if ($context){
			$url .= "date-{$context}/";
		}

		return $url;
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

	function flickr_urls_faves_user($user, $by_user=null){

		$url = flickr_urls_photos_user($user) . "faves/";

		if ($by_user){
			$alias = flickr_urls_path_alias_for_user($by_user);
			$url .= "{$alias}/";
		}

		return $url;
	}

	#################################################################

	function flickr_urls_path_alias_for_user(&$user){

		$flickr_user = flickr_users_get_by_user_id($user['id']);
		$alias = null;

		if ($GLOBALS['cfg']['enable_feature_path_alias_redirects']){
			loadlib("flickr_users_path_aliases");
			$alias = flickr_users_path_aliases_current_for_user($user);
		}

		if (! $alias){

			# see notes in flickr_users_create_user

			if ((! $flickr_user['path_alias']) || ($flickr_user['path_alias_taken_by'])){
				$alias = $flickr_user['nsid'];
			}

			else {
				$alias = $flickr_user['path_alias'];
			}
		}

		return $alias;
	}

	#################################################################
?>
