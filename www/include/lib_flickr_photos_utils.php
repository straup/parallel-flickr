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

	# the end
