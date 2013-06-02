<?php

	#################################################################

	loadlib("flickr_api");
	loadlib("flickr_photos");
	loadlib("flickr_photos_geo");
	loadlib("flickr_photos_geo_corrections");
	loadlib("api_utils_flickr");

	loadlib("flickr_places");
	loadlib("geo_utils");

	#################################################################

	function api_flickr_photos_geo_setContext(){

		$flickr_user = api_utils_flickr_ensure_token_perms($GLOBALS['cfg']['user'], 'write');

		$photo_id = post_int64("photo_id");
		$photo = _api_flickr_photos_geo_get_photo($photo_id);

		$context = post_int32("context");

		if (! isset($context)){
			api_output_error(999, "Missing context");
		}

		if (! flickr_photos_geo_is_valid_context($context)){
			api_output_error(999, "Invalid context");
		}

		if ($photo['geocontext'] == $context){
			api_output_ok();
		}

		# Sanity check photo and context here...

		$method = 'flickr.photos.geo.setContext';

		$args = array(
			'photo_id' => $photo_id,
			'context' => $context,
			'auth_token' => $flickr_user['auth_token'],
		);

		$rsp = flickr_api_call($method, $args);

		if (! $rsp['ok']){
			api_output_error(999, $rsp['error']);
		}

		$update = array(
			'geocontext' => $context,
		);

		$rsp = flickr_photos_update_photo($photo, $update);

		if (! $rsp['ok']){
			api_output_error(999, $rsp['error']);
		}

		$out = array(
			'photo_id' => $photo_id,
			'context' => $context,
		);

		api_output_ok($out);
	}

	#################################################################

	function api_flickr_photos_geo_correctLocation(){

		$flickr_user = api_utils_flickr_ensure_token_perms($GLOBALS['cfg']['user'], 'write');

		$photo_id = post_int64("photo_id");
		$photo = _api_flickr_photos_geo_get_photo($photo_id);

		$old_woeid = $photo['woeid'];
		$new_woeid = post_int32("woeid");

		if (! $new_woeid){
			api_output_error(999, "Missing WOE ID");
		}

		if ($old_woeid == $new_woeid){
			api_output_error(999, "Nothing to correct!");
		}

		# validate WOE ID preemptively?

		$method = "flickr.photos.geo.correctLocation";

		$args = array(
			'photo_id' => $photo['id'],
			'woe_id' => $new_woeid,
			'auth_token' => $flickr_user['auth_token'],
		);

		$rsp = flickr_api_call($method, $args);

		if (! $rsp['ok']){
			api_output_error(999, $rsp['error']);
		}

		$update = array(
			'woeid' => $new_woeid,
		);

		$rsp = flickr_photos_update_photo($photo, $update);

		if (! $rsp['ok']){
			api_output_error(999, $rsp['error']);
		}

		# throw an error if this fails? feels like overkill...

		$correction = array(
			'photo_id' => $photo['id'],
			'user_id' => $photo['user_id'],
			'old_woeid' => $old_woeid,
			'new_woeid' => $new_woeid,
		);

		flickr_photos_geo_corrections_create($correction);

		#

		$place = flickr_places_get_by_woeid($new_woeid);

		$out = array(
			'photo_id' => $photo_id,
			'woeid' => $new_woeid,
			'place' => $place,
		);

		api_output_ok($out);
	}

	#################################################################

	function api_flickr_photos_geo_possibleCorrections(){

		$photo_id = request_int64("photo_id");
		$photo = _api_flickr_photos_geo_get_photo($photo_id);

		$type = request_str("place_type");

		if (! $type){
			api_output_error(999, "Missing place type");
		}

		if (! flickr_places_is_valid_placetype($type)){
			api_output_error(999, "Invalid place type");
		}

		# TO DO: calculate based on $type
		$radius = 1.5;

		$bbox = geo_utils_bbox_from_point($photo['latitude'], $photo['longitude'], $radius, 'km');
		$bbox = implode(",", array($bbox[1], $bbox[0], $bbox[3], $bbox[2]));

		$method = 'flickr.places.placesForBoundingBox';

		$args = array(
			'bbox' => $bbox,
			'place_type' => $type,
		);

		$rsp = flickr_api_call($method, $args);

		if (! $rsp['ok']){
			api_output_error(999, "Flickr API error");
		}

		$possible = array();

		if ($rsp['rsp']['places']['total']){

			foreach ($rsp['rsp']['places']['place'] as $place){
				$possible[] = array(
					'woeid' => $place['woeid'],
					'placetype' => $place['place_type'],
					'name' => $place['_content'],
				);
			}
		}

		$parent = flickr_places_parent_placetype($type);

		$out = array(
			'place_type' => $type,
			'parent_place_type' => $parent,
			'places' => $possible
		);

		return api_output_ok($out);
	}

	#################################################################

	function _api_flickr_photos_geo_get_photo($photo_id, $ensure_is_own=1){

		if (! $photo_id){
			api_output_error(999, "Missing photo ID");
		}

		$photo = flickr_photos_get_by_id($photo_id);

		if (! $photo['id']){
			api_output_error(999, "Invalid photo ID");
		}

		if (($ensure_is_own) && ($photo['user_id'] != $GLOBALS['cfg']['user']['id'])){
			api_output_error(999, "Insufficient permissions");
		}

		if (! $photo['hasgeo']){
			api_output_error(999, "Photo is not geotagged");
		}

		return $photo;
	}

	#################################################################
?>
