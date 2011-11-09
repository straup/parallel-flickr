<?php

	loadlib("flickr_contacts");

	#################################################################

	function flickr_photos_permissions_map($string_keys=0){

		$map = array(
			0 => 'public',
			2 => 'friends',
			3 => 'family',
			4 => 'friends and family',
			5 => 'private',
		);

		if ($string_keys){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	function flickr_photos_permissions_can_view_photo(&$photo, $viewer_id=0){

		if (($viewer_id) && ($photo['user_id'] == $viewer_id)){
			return 1;
		}

		$perms_map = flickr_photos_permissions_map();
		$perms = $perms_map[$photo['perms']];

		if ((! $viewer_id) || ($perms == 'public')){
			return 1;
		}

		if ($contact = flickr_contacts_get_contact($photo['user_id'], $viewer_id)){

			# TODO: check this is actually correct...

			if ($photo['perms'] <= $contact['rel']){
				return 1;
			}
		}

		return 0;
	}

	#################################################################

	function flickr_photos_permissions_photos_where($owner_id, $viewer_id=0){

		if ($owner_id == $viewer_id){
			return '';
		}

		$perms_map = flickr_photos_permissions_map('string keys');

		if ($viewer_id == 0){
			$perms = $perms_map['public'];
		}

		else if ($contact = flickr_contacts_get_contact($owner_id, $viewer_id)){

			$rel_map = flickr_contacts_relationship_map();
			$str_rel = $rel_map[$contact['rel']];

			$perms = $perms_map[$str_rel];
		}

		else {
			$perms = $perms_map['public'];
		}

		return "perms={$perms}";
	}

	#################################################################

?>
