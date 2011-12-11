<?php

	#################################################################

	loadlib("flickr_api");
	loadlib("flickr_users");
	loadlib("flickr_faves");
	loadlib("flickr_photos_import");

	#################################################################

	function flickr_faves_import_for_nsid($nsid, $more=array()){

		$flickr_user = flickr_users_get_by_nsid($nsid);
		$user = users_get_by_id($flickr_user['user_id']);

		if (! $user){
			return array(
				'ok' => 0,
				'error' => 'not a valid user',
			);
		}

		$method = 'flickr.favorites.getList';

		$args = array(
			'user_id' => $flickr_user['nsid'],
			'auth_token' => $flickr_user['auth_token'],
			'extras' => 'original_format,tags,media,date_upload,date_taken,geo,owner_name',
			'per_page' => 100,
			'page' => 1,
		);

		if (isset($more['min_fave_date'])){
			$args['min_fave_date'] = $more['min_fave_date'];
		}

		$pages = null;
		$count = 0;

		while ((! isset($pages)) || ($pages >= $args['page'])){

			$rsp = flickr_api_call($method, $args);

			if (! $rsp['ok']){
				return $rsp;
			}

			if (! isset($pages)){
				$pages = $rsp['rsp']['photos']['pages'];
			}

			$photos = $rsp['rsp']['photos']['photo'];

			if (! is_array($photos)){
				return array(
					'ok' => 0,
					'error' => 'no photos',
				);
			}

			foreach ($photos as $photo){

				$ph_rsp = flickr_photos_import_photo($photo);

				if (! $ph_rsp['ok']){
					return $ph_rsp;
				}

				$fave_rsp = flickr_faves_add_fave($user, $ph_rsp['photo'], $photo['date_faved']);

				if ($fave_rsp['ok']){
					$count++;
				}
			}

			$args['page'] += 1;
		}

		return array(
			'ok' => 1,
			'count_imported' => $count,
		);
	}

	#################################################################

?>
