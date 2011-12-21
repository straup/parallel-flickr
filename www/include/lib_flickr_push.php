<?php

	loadlib("flickr_api");
	loadlib("flickr_users");

	#################################################################

	function flickr_push_topic_map($str_keys=0){

		# note: changes here should also be reflected
		# in flickr_push_subscriptions_topic_map()

		$map = array(
			1 => 'contacts_photos',
			2 => 'contacts_faves',
			3 => 'my_photos',
			4 => 'my_faves',
			5 => 'photos_of_me',
			6 => 'photos_of_contacts',
			7 => 'geo',
			8 => 'commons',
			9 => 'tags',
		);

		if ($str_keys){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	function flickr_push_update_types_map($rollup_by_type=0){

		$map = array(
			'name' => 'title',
			'description' => 'description',
			'date_create' => 'date_create',
			'date_taken' => 'date_taken',
			'camera' => 'camera',
			'license' => 'license',
			'rotation' => 'photo_url',
			'count_comments' => 'comments',
			'count_notes' => 'notes',
			'count_tags' => 'tags',
			'count_faves' => 'faves',
			'secret' => 'photo_url',
			'secret_o' => 'photo_url',
			'server' => 'photo_url',
			'has_geo' => 'geo',
			'created' => 'created',
		);

		if ($rollup_by_type){

			$tmp = array();

			foreach ($map as $event => $type){

				if (! is_array($tmp[$type])){
					$tmp[$type] = array();
				}

				$tmp[$type][] = $event;
			}

			$map = $tmp;
		}

		return $map;
	}

	#################################################################

	function flickr_push_subscribe($subscription){

		$flickr_user = flickr_users_get_by_user_id($subscription['user_id']);

		$callback = "{$GLOBALS['cfg']['abs_root_url']}push/{$subscription['secret_url']}/";

		$method = 'flickr.push.subscribe';

		$map = flickr_push_topic_map();
		$topic = $map[$subscription['topic_id']];

		$args = array(
			'auth_token' => $flickr_user['auth_token'],
			'topic' => $topic,
			'verify' => 'sync',
			'verify_token' => $subscription['verify_token'],
			'callback' => $callback,
		);

		if ($topic_args = $subscription['topic_args']){

			if (! is_array($topic_args)){
				$topic_args = json_decode($topic_args, "as hash");
			}

			$args = array_merge($args, $topic_args);
		}

		$rsp = flickr_api_call($method, $args);
		return $rsp;
	}

	#################################################################

	function flickr_push_unsubscribe($subscription){

		$flickr_user = flickr_users_get_by_user_id($subscription['user_id']);

		$callback = "{$GLOBALS['cfg']['abs_root_url']}push/{$subscription['secret_url']}/";

		$method = 'flickr.push.unsubscribe';

		$map = flickr_push_topic_map();
		$topic = $map[$subscription['topic_id']];

		$args = array(
			'auth_token' => $flickr_user['auth_token'],
			'topic' => $topic,
			'verify' => 'sync',
			'verify_token' => $subscription['verify_token'],
			'callback' => $callback,
		);

		if ($topic_args = $subscription['topic_args']){

			if (! is_array($topic_args)){
				$topic_args = json_decode($topic_args, "as hash");
			}

			$args = array_merge($args, $topic_args);
		}

		$rsp = flickr_api_call($method, $args);
		return $rsp;
	}

	#################################################################

?>
