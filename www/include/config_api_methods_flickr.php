<?php

	########################################################################

	$GLOBALS['cfg']['api']['methods'] = array_merge(array(

		"flickr.photos.upload" => array(
			"documented" => 0,
			"enabled" => 1,
			"library" => "api_flickr_photos",
			"requires_auth" => 1,
			"requires_crumb" => 1,
			"crumb_ttl" => 300
		),

		"flickr.photos.friends.faves" => array(
			"documented" => 0,
			"enabled" => 1,
			"library" => "api_flickr_photos_friends",
			"requires_auth" => 1
		),

		"flickr.photos.geo.setContext" => array(
			"documented" => 0,
			"enabled" => 1,
			"library" => "api_flickr_photos_geo",
			"requires_auth" => 1
		),

		"flickr.photos.geo.correctLocation" => array(
			"documented" => 0,
			"enabled" => 1,
			"library" => "api_flickr_photos_geo",
			"requires_auth" => 1
		),

		"flickr.photos.geo.possibleCorrections" => array(
			"documented" => 0,
			"enabled" => 1,
			"library" => "api_flickr_photos_geo",
			"requires_auth" => 1
		),

		"flickr.favorites.add" => array(
			"documented" => 0,
			"enabled" => 1,
			"library" => "api_flickr_favorites",
			"requires_auth" => 1
		),

		"flickr.favorites.remove" => array(
			"documented" => 0,
			"enabled" => 1,
			"library" => "api_flickr_favorites",
			"requires_auth" => 1
		),

	), $GLOBALS['cfg']['api']['methods']);

	########################################################################

	# the end
