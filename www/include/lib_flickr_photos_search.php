<?php

	#################################################################

	loadlib("solr");
	loadlib("solr_utils");
	loadlib("solr_dates");
	loadlib("solr_machinetags");

	loadlib("flickr_photos_permissions");
	loadlib("flickr_geo_permissions");

	loadlib("flickr_photos_metadata");
	loadlib("flickr_places");

	loadlib("flickr_photos_exif");
	loadlib("exif_tools");

	#################################################################

	function flickr_photos_search(&$query, $more=array()){

		if (! $GLOBALS['cfg']['enable_feature_solr']){
			return not_okay('search indexing is disabled');
		}

		# OMGWTF: When sorting by date_taken|posted the results
		# are basically anything but sorted. It's unclear to me
		# whether this is a known Lucene thing or ... what? I
		# suppose it might make sense to store dates as INTs but
		# then we lose the ability to do date facteing, for calendar
		# pages sometime in the future. So for now we'll just sort
		# by photo ID since it accomplishes the same thing...
		# (20111121/straup)
		#
		# see also: http://phatness.com/2009/11/sorting-by-date-with-solr/

		$defaults = array(
			'viewer_id' => 0,
			'sort' => 'id desc',
		);

		$more = array_merge($defaults, $more);

		$q = solr_utils_hash2query($query, " AND ");

		$params = array(
			'q' => $q,
			'sort' => $more['sort'],
		);

		$owner_id = (isset($query['user_id'])) ? $query['user_id'] : 0;

		if ($fq = _flickr_photos_search_perms_fq($owner_id, $more['viewer_id'], $more)){
			$params['fq'] = $fq;
		}

		$rsp = solr_select($params, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$photos = array();

		foreach ($rsp['rows'] as $row){

			$photo = flickr_photos_get_by_id($row['id']);

			$can_view_geo = ($photo['hasgeo'] && flickr_geo_permissions_can_view_photo($photo, $more['viewer_id'])) ? 1 : 0;

			$photo['can_view_geo'] = $can_view_geo;
			$photos[] = $photo;
		}

		$rsp['rows'] = $photos;
		return $rsp;
	}

	#################################################################

	function flickr_photos_search_facet(&$query, $facet, $more=array()){

		if (! $GLOBALS['cfg']['enable_feature_solr']){
			return not_okay('search indexing is disabled');
		}

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);

		$q = solr_utils_hash2query($query, " AND ");

		$params = array(
			'q' => $q,
			"facet" => "on",
			"facet.field" => $facet,
		);

		if (isset($more['facet.query'])){
			$params['facet.query'] = $more['facet.query'];
		}

		$owner_id = (isset($query['user_id'])) ? $query['user_id'] : 0;

		if ($fq = _flickr_photos_search_perms_fq($owner_id, $more['viewer_id'], $more)){
			$params['fq'] = $fq;
		}

		$rsp = solr_facet($params, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		return $rsp;
	}

	#################################################################

	# https://wiki.apache.org/solr/SimpleFacetParameters#Date_Faceting:_per_day_for_the_past_5_days
	# https://lucene.apache.org/solr/api/org/apache/solr/util/DateMathParser.html

	function flickr_photos_search_facet_dates(&$query, $facet, $start, $end, $gap, $more=array()){

		$start = (is_numeric($start)) ? solr_dates_prep_timestamp($start) : solr_dates_prep_mysql_datetime($start);
		$end = (is_numeric($end)) ? solr_dates_prep_timestamp($end) : solr_dates_prep_mysql_datetime($end);

		return flickr_photos_search_facet_range($query, $facet, $start, $end, $gap, $more);
	}

	#################################################################

	function flickr_photos_search_facet_range(&$query, $facet, $start, $end, $gap, $more=array()){

		if (! $GLOBALS['cfg']['enable_feature_solr']){
			return not_ok('search indexing is disabled');
		}

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);

		$q = solr_utils_hash2query($query, " AND ");

		$params = array(
			'q' => $q,
			"facet.range" => $facet,
			"facet.range.gap" => $gap,
			"facet.range.start" => $start,
			"facet.range.end" => $end,
		);

		$owner_id = (isset($query['user_id'])) ? $query['user_id'] : 0;

		if ($fq = _flickr_photos_search_perms_fq($owner_id, $more['viewer_id'], $more)){
			$params['fq'] = $fq;
		}

		return solr_facet_range($params, $more);
	}

	#################################################################

	# TO DO: add the ability to batch index photos (for backfill, etc.)

	function flickr_photos_search_index_photo(&$photo, $meta=array()){

		if (! $GLOBALS['cfg']['enable_feature_solr']){
			return not_okay('search indexing is disabled');
		}

		if (! $meta){
			$meta = flickr_photos_metadata_load($photo);
		}

		# really exit or just ignore all the $meta stuff below?

		if (! $meta['ok']){

			return not_okay('failed to load photo metadata');
		}

		$meta = $meta['data']['photo'];

		$doc = array(
			'id' => $photo['id'],
			'user_id' => $photo['user_id'],
			'title' => $photo['title'],
			'perms' => $photo['perms'],
			'datetaken' => solr_dates_prep_mysql_datetime($photo['datetaken']),
			'dateupload' => solr_dates_prep_mysql_datetime($photo['dateupload']),
		);

		$tags = array();
		$machinetags = array();

		if (isset($meta['tags']['tag'])){
			foreach ($meta['tags']['tag'] as $tag){

				$tags[] = $tag['raw'];

				if ($tag['machinetag']){
					$machinetags = array_merge($machinetags, solr_machinetags_explode($tag['raw']));
				}
			}
		}

		if (count($tags)){
			$doc['tags'] = $tags;
		}

		if (count($machinetags)){
			$doc['machinetags'] = $machinetags;
		}

		if ($photo['hasgeo']){

			$doc['location'] = "{$photo['latitude']},{$photo['longitude']}";
			$doc['accuracy'] = $photo['accuracy'];

			$doc['geoperms'] = $photo['geoperms'];
			$doc['geocontext'] = $photo['geocontext'];

			foreach (array('neighbourhood', 'locality', 'county', 'region', 'country', 'continent') as $place){

				if (isset($meta['location'][$place])){
					$doc[$place] = $meta['location'][$place]['woeid'];
				}
			}

			if ($place = flickr_places_get_by_woeid($photo['woeid'])){
				$doc['timezone'] = $place['timezone'];
				$doc['place'] = $place['place_url'];
			}
		}

		# pull in some EXIF data (if present)

		$rsp = flickr_photos_exif_read($photo);

		if ($rsp['ok']){

			$exif = $rsp['rows'];

			if (isset($exif['Make'])){

				if ($make = exif_tools_scrub_string($exif['Make'])){

					$doc['camera_make'] = ucwords($make);
				}
			}

			if (isset($exif['Model'])){

				if ($model = exif_tools_scrub_string($exif['Model'])){

					$doc['camera_model'] = $model;
				}
			}

			# EXIF: what else?

			if (isset($exif['FocalLength'])){
				$doc['focal_length'] = exif_tools_rational2float($exif['FocalLength']);
			}

			if (isset($exif['ApetureValue'])){
				$doc['apeture'] = exif_tools_rational2float($exif['ApetureValue']);
			}

			if (isset($exif['ShutterSpeedValue'])){
				$doc['shutter_speed'] = exif_tools_rational2float($exif['ShutterSpeedValue']);
			}

			if (isset($exif['ISOSpeedRatings'])){
				$doc['iso_speed'] = intval($exif['ISOSpeedRatings']);
			}

			# http://www.sno.phy.queensu.ca/~phil/exiftool/TagNames/GPS.html

			if (isset($exif['GPSAltitude'])){
				$altitude = exif_tools_explode_gps_altitude($exif['GPSAltitude'], $exif['GPSAltitudeRef']);
				$doc['altitude'] = $altitude;
			}

			if (isset($exif['GPSImgDirection'])){

				$direction = exif_tools_explode_gps_img_direction($exif['GPSImgDirection'], $exif['GPSImgDirectionRef']);
				$doc['direction'] = $direction;
			}
		}

		# go!

		$docs = array(
			$doc,
		);

		$rsp = solr_add($docs);
		return $rsp;
	}

	#################################################################

	function _flickr_photos_search_perms_fq($owner_id=0, $viewer_id=0, $more=array()){

		if (($owner_id) && ($owner_id == $viewer_id)){
			return;
		}

		# THIS IS NOT AWESOME. PERMISSIONS IN SOLR SHOULD
		# PROBABLY JUST ALL BE PRE-COMPUTED AND STORED THE
		# SAME WAY MACHINETAGS ARE.... (20111119/straup)

		$fq = array();

		if ($perms = flickr_photos_permissions_photos_where($owner_id, $viewer_id)){

			$count = count($perms);

			for ($i=0; $i < $count; $i++){
				$perms[$i] = "perms:" . urlencode($perms[$i]);
			}

			$fq[] = implode(" OR ", $perms);
		}

		if (isset($more['enforce_geoperms'])){

			if ($perms = flickr_geo_permissions_photos_where($owner_id, $viewer_id)){

				$count = count($perms);

				for ($i=0; $i < $count; $i++){
					$perms[$i] = "geoperms:" . urlencode($perms[$i]);
				}

				$fq[] = implode(" OR ", $perms);
			}
		}

		return $fq;
	}

	#################################################################
?>
