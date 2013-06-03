<?php

	#################################################################

	function api_parallel_flickr_utils_photo2spr(&$photo, $more=array()){

		# just model that after flickr (+ extras) or...?
		# needs datetaken, geo and owner at least
		# (20130603/straup)

		# Seriously these names are just placeholders...
		# (20130603/straup)

		$user = users_get_by_id($photo['user_id']);

		$spr = array(
			'id' => $photo['id'],
			'owner_id' => $photo['user_id'],
			'owner_name' => $user['username'],
			'title' => $photo['title'],
			'date_taken' => $photo['datetaken'],
			#'photo_url' => flickr_urls_photo_original($photo),
			'photo_url' => flickr_urls_photo_static($photo),
			'photo_page' => flickr_urls_photo_page($photo),
			'photo_page_flickr' => flickr_urls_photo_page_flickr($photo),
			# 'photo_page_flickr_short' => flickr_urls_photo_page_flickr_short($photo),
		);

		return $spr;
	}

	#################################################################

	# the end
