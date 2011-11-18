<?php

	#################################################################

	loadlib("solr");
	loadlib("solr_machinetags");
	loadlib("solr_dates");
	loadlib("flickr_photos_metadata");
	loadlib("flickr_photos_exif");
	loadlib("flickr_places");

	#################################################################

	function flickr_photos_search_index_photo(&$photo){

		if (! $GLOBALS['cfg']['enable_feature_solr']){

			return not_ok('search indexing is disabled');
		}

		$meta = flickr_photos_metadata_load($photo);

		# really exit or just ignore all the $meta stuff below?

		if (! $meta['ok']){

			return not_ok('failed to load photo metadata');
		}

		$meta = $meta['data']['photo'];

		$doc = array(
			'photo_id' => $photo['id'],
			'photo_owner' => $photo['user_id'],
			'title' => $photo['title'],
			'photo_perms' => $photo['perms'],
			'date_taken' => solr_dates_prep_mysql_datetime($photo['datetaken']),
			'date_posted' => solr_dates_prep_mysql_datetime($photo['dateupload']),
		);

		$tags = array();
		$machinetags = array();

		foreach ($meta['tags']['tag'] as $tag){

			if (! $tag['machinetag']){
				$tags[] = $tag['raw'];
				continue;
			}		

			$machinetags = array_merge($machinetags, solr_machinetags_explode($tag['raw']));
		}

		if (count($tags)){
			$doc['tags'] = $tags;
		}

		if (count($machinetags)){
			$doc['machinetags'] = $machinetags;
		}

		if ($photo['latitude'] && $photo['longitude']){

			$doc['location'] = "{$photo['latitude']},{$photo['longitude']}";
			$doc['accuracy'] = $photo['accuracy'];

			$doc['geo_perms'] = $photo['geoperms'];

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

			if (isset($exif['Model'])){
				$doc['camera_model'] = $exif['Model'];
			}

			if (isset($exif['Make'])){
				$doc['camera_make'] = $exif['Make'];
			}

			# TO DO: what else?
			# FocalLength
			# ShutterSpeedValue
			# ApertureValue

			if (isset($exif['GPSAltitude'])){
				# TO DO: massage?
				# $doc['altitude'] = $exif['GPSAltitude'];
			}

			if (isset($exif['GPSDirection'])){
				# TO DO: massage?
				# $doc['direction'] = $exif['GPSDirection'];
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
?>
