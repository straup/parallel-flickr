<?php

	loadlib("flickr_photos_search");

	#################################################################

	function flickr_photos_archives_photos_for_user(&$user, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);

		$query = array(
			'photo_owner' => $user['id'],
		);

		return flickr_photos_search_facet_dates($query, $more);
	}

	#################################################################
?>
