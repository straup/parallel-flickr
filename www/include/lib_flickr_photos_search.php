<?php

	#################################################################

	loadlib("solr");
	loadlib("solr_machinetags");
	loadlib("solr_dates");
	loadlib("flickr_photos_metadata");

	#################################################################

	function flickr_photos_search_index_photo(&$photo){

		if (! $GLOBALS['cfg']['enable_feature_solr']){

			return array(
				'ok' => 0,
				'error' => 'search indexing is disabled',
			);
		}

		$meta = flickr_photos_metadata_load($photo);

		if (! $meta['ok']){

			return array(
				'ok' => 0,
				'error' => 'failed to load photo metadata',
			);
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

			# TO DO: get timezone and places URL and continent
			# just call flickr.places.getInfo for $photo['woeid'] ?
		}

		$docs = array(
			$doc,
		);

		$rsp = solr_add($docs);
		return $rsp;
	}

	#################################################################
?>