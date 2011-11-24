<?php

	loadlib("flickr_contacts");

	#################################################################

	function flickr_geo_permissions_map($string_keys=0){

		$map = array(
			0 => 'public',
			1 => 'contacts',
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

	function flickr_geo_permissions_can_view_photo(&$photo, $viewer_id=0){

		if (($viewer_id) && ($photo['user_id'] == $viewer_id)){
			return 1;
		}

		$perms_map = flickr_geo_permissions_map();
		$perms = $perms_map[$photo['geoperms']];

		if ((! $viewer_id) && ($perms == 'public')){
			return 1;
		}

		if ($perms == 'public'){
			return 1;
		}

		if ($contact = flickr_contacts_get_contact($photo['user_id'], $viewer_id)){

			$rel_map = flickr_contacts_relationship_map();
			$str_rel = $rel_map[$contact['rel']];

			if (($perms == 'friends') || ($perms == 'family')){
				return ($str_rel == $perms) ? 1 : 0;
			}

			if ($perms == 'friends and family'){
				return (in_array($str_rel, array('friends', 'family'))) ? 1 : 0;
			}

			return ($perms == 'contacts') ? 1 : 0;
		}

		return 0;
	}

	#################################################################

	function flickr_geo_permissions_photos_where($owner_id, $viewer_id=0){

		if ($owner_id == $viewer_id){
			return '';
		}

		$perms_map = flickr_geo_permissions_map('string keys');

		if ($viewer_id == 0){
			$perms = array($perms_map['public']);
		}

		else if ($contact = flickr_contacts_get_contact($owner_id, $viewer_id)){

			$rel_map = flickr_contacts_relationship_map();
			$str_rel = $rel_map[$contact['rel']];

			$perms = array(
				$perms_map['public'],
				$perms_map['contacts'],
			);

			if ($str_rel == 'friends'){
				$perms[] = $perms_map['friends'];
				$perms[] = $perms_map['friends and family'];
			}

			else if ($str_rel == 'family'){
				$perms[] = $perms_map['family'];
				$perms[] = $perms_map['friends and family'];
			}

			else { }

		}

		else {
			$perms = array($perms_map['public']);
		}

		return $perms;
	}

	#################################################################

?>
