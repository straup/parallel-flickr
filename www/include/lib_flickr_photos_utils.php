<?php

	loadlib("flickr_geo_permissions");

	#################################################################

	function flickr_photos_utils_assign_can_view_geo(&$photos, $viewer_id=0){

		$count = count($photos);

		for ($i=0; $i < $count; $i++){
			$ph = $photos[$i];
			$ph['can_view_geo'] = ($ph['hasgeo'] && flickr_geo_permissions_can_view_photo($ph, $viewer_id)) ? 1 : 0;
			$photos[$i] = $ph;
		}

		# Note the pass-by-ref
	}

	#################################################################

	function flickr_photos_utils_inflate_photo_rows(&$rows){

		foreach ($rows as &$row){
			flickr_photos_utils_inflate_photo_row($row);
		}

		# pass-by-ref
	}

	#################################################################

	function flickr_photos_utils_inflate_photo_row(&$row){

		if (! isset($row['owner'])){
			$row['owner'] = users_get_by_id($row['user_id']);
		}

		if (! isset($row['str_perms'])){
			$map = flickr_photos_permissions_map();
			$row['str_perms'] = $map[$row['perms']];
		}

		# pass-by-ref
	}

	#################################################################

	# the end
