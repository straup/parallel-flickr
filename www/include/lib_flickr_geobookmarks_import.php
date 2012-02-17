<?php

	loadlib("flickr_api");
	loadlib("flickr_users");
	loadlib("flickr_geobookmarks");
	loadlib("flickr_geo_permissions");

	#################################################################

	function flickr_geobookmarks_import_for_nsid($nsid, $more=array()){

		$flickr_user = flickr_users_get_by_nsid($nsid);
		$user = users_get_by_id($flickr_user['user_id']);

		if (! $user){
			return not_okay("Not a valid user");
		}

		$flickr_user = flickr_users_get_by_user_id($user['id']);

		$method = 'flickr.people.geoBookmarks.getList';

		$args = array(
			'auth_token' => $flickr_user['auth_token'],
		);

		$rsp = flickr_api_call($method, $args);

		if (! $rsp['ok']){
			return $rsp;
		}

		if (! $rsp['rsp']['bookmarks']['count']){
			return okay();
		}

		$bookmarks = array();

		# mark everything as private for now since none of that stuff
		# got turned on before I left, sad face... (20120217/straup)

		$geo_perms = flickr_geo_permissions_map("string keys");
		$geo_private = $geo_perms['private'];

		foreach ($rsp['rsp']['bookmarks']['bookmark'] as $bm){

			$bm['user_id'] = $user['id'];
			$bm['name'] = $bm['label'];
			$bm['geocontext'] = $bm['context'];
			$bm['geoperms'] = $geo_private;
			$bm['woeid'] = 0;

			unset($bm['label']);
			unset($bm['pretty_name']);
			unset($bm['context']);

			$geo_method = 'flickr.places.findByLatLon';

			$geo_args = array(
				'lat' => $bm['latitude'],
				'lon' => $bm['longitude'],
				'accuracy' => $bm['accuracy'],
			);

			$geo_rsp = flickr_api_call($geo_method, $geo_args);

			if ($geo_rsp['ok']){
				# I still miss xpath...
				$bm['woeid'] = $geo_rsp['rsp']['places']['place'][0]['woeid'];
			}

			$bookmarks[] = $bm;
		}

		$rsp = flickr_geobookmarks_purge_for_user($user);

		if (! $rsp['ok']){
			return $rsp;
		}

		$count = 0;

		foreach ($bookmarks as $bm){
			$rsp = flickr_geobookmarks_add($bm);
			$count += $rsp['ok'];
		}

		return okay(array('count_imported' => $count));
	}

	#################################################################

?>
