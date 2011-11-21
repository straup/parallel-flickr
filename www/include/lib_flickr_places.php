<?php

	loadlib("flickr_api");

	#################################################################

	$GLOBALS['flickr_places_cache'] = array();

	#################################################################

	function flickr_places_valid_placetypes(){

		return array(
			'neighbourhood',
			'locality',
			'county',
			'region',
			'country',
		);
	}

	#################################################################

	function flickr_places_is_valid_placetype($type){

		$valid = flickr_places_valid_placetypes();
		return (in_array($type, $valid)) ? 1 : 0;
	}

	#################################################################

	# Okay, stop for a second. Normally we would just cache this sort
	# of thing (fetching data from a third-party API) using memcache
	# or similar. Although it might be overkill there's no particular
	# reason no to add memcache hooks here too but (at least for now)
	# parallel-flickr is expected to be *able* to run without memcache.
	# On the other hand it can't really run very well if it needs to
	# call the Flickr API over and over on a single page (think the
	# /user/places splash page) so we just shove everything in to the
	# database. And if that looks strangely like a poorman's memcache
	# I suppose it is, modulo the "memory" part... (20111121/straup)

	function flickr_places_get_by_woeid($woeid, $more=array()){

		if (! isset($more['force'])){

			if (isset($GLOBALS['flickr_places_cache'][$woeid])){
				return $GLOBALS['flickr_places_cache'][$woeid];
			}

			# filter by date too?

			$enc_woeid = AddSlashes($woeid);
			$sql = "SELECT * FROM Places WHERE woeid='{$enc_woeid}'";

			if ($row = db_single(db_fetch($sql))){

				$place = json_decode($row['flickr_data'], "as hash");
				$GLOBALS['flickr_places_cache'][$woeid] = $place;
				return $place;
			}
		}

		$rsp = _flickr_places_getinfo($woeid);

		if (! $rsp['ok']){
			return null;
		}

		$place = $rsp['rsp']['place'];

		$insert = array(
			'woeid' => AddSlashes($woeid),
			'flickr_data' => AddSlashes(json_encode($place)),
			'date_created' => time(),
		);

		$rsp = db_insert('Places', $insert);

		$GLOBALS['flickr_places_cache'][$woeid] = $place;
		return $place;
	}

	#################################################################

	function _flickr_places_getinfo($woeid){

		$method = "flickr.places.getInfo";
		$args = array('woe_id' => $woeid);

		$rsp = flickr_api_call($method, $args);
		return $rsp;
	}

	#################################################################
?>
