<?php

	#################################################################

	# Yes, this makes me cry too; see notes in flickr_push_receiver.php
	# for details. And yes, this feels specific to the push stuff. At
	# least until it's proven otherwise... (20120606/straup)

	function flickr_push_utils_info2spr(&$info){

		$spr = array(
			'id' => $info['id'],
			'owner' => $info['owner']['nsid'],
			'ownername' => $info['owner']['username'],
			'secret' => $info['secret'],
			'server' => $info['server'],
			'farm' => $info['farm'],
			'title' => $info['title']['_content'],
			'originalsecret' => $info['originalsecret'],
			'originalformat' => $info['originalformat'],
			'ispublic' => $info['visibility']['ispublic'],
			'isfriend' => $info['visibility']['isfriend'],
			'isfamily' => $info['visibility']['isfamily'],
			'tags' => '',
			'media' => $info['media'],
			'media_status' => '',
			'dateupload' => $info['dates']['posted'],
			'datetaken' => $info['dates']['taken'],
			'datetakengranularity' => $info['dates']['takengranularity'],
			'latitude' => 0,
			'longitude' => 0,
			'accuracy' => 0,

			# remaining geo stuff is added below...
		);

		$tags = array();

		foreach ($info['tags']['tag'] as $t){
			$tags[] = $t['_content'];
		}

		$spr['tags'] = implode(" ", $tags);

		if (isset($info['location'])){

			$spr['latitude'] = $info['location']['latitude'];
			$spr['longitude'] = $info['location']['longitude'];
			$spr['accuracy'] = $info['location']['accuracy'];
		}

		# As noted elsewhere this is ultimately my fault (for not just
		# adding a 'hasgeo' attribute in API responses when I still
		# worked at Flickr). I'm sorry. Every time I have to do this...
		# (20120606/straup)
	
		if ($spr['accuracy']){

			$spr['context'] = $info['location']['context'];

			$spr['geo_is_family'] = $info['location']['geoperms']['isfamily'];
			$spr['geo_is_friend'] = $info['location']['geoperms']['isfriend'];
			$spr['geo_is_contact'] = $info['location']['geoperms']['iscontact'];
			$spr['geo_is_public'] = $info['location']['geoperms']['ispublic'];

			foreach (array('neighbourhood', 'locality', 'county', 'region', 'country') as $type){

				if (isset($info['location'][$type])){
					$spr['place_id'] = $info['location'][$type]['place_id'];
					$spr['woeid'] = $info['location'][$type]['woeid'];
					break;
				}
			}
		}

		return $spr;
	}

	#################################################################
?>
