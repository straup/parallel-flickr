<?php

	loadlib("flickr_photos_search");

	#################################################################

	# IT IS UNCLEAR whether this function signature should be the same
	# as that of the search function... (20111124/straup)

	function flickr_photos_archives_photos_for_user(&$user, $facet, $start, $end, $gap, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);

		$query = array(
			'photo_owner' => $user['id'],
		);

		return flickr_photos_search_facet_dates($query, $facet, $start, $end, $gap, $more);
	}

	#################################################################
?>
