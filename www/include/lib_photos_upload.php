<?php

	loadlib("flickr_photos_upload");
	loadlib("parallel_flickr_photos_upload");

	#################################################################

	function photos_upload_sendto_map($pretty_keys=0){

		$map = array(
			'fl' => 'flickr',
			'pf' => 'parallel-flickr',
		);

		if ($pretty_keys){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	function photos_upload_resolve_sendto($send_to=null){

		if ((! $send_to) && (isset($GLOBALS['cfg']['uploads_default_send_to']))){
			$send_to = $GLOBALS['cfg']['uploads_default_send_to'];
		}

		$map = photos_upload_sendto_map();

		if (isset($map[$send_to])){
			return $map[$send_to];
		}

		$map = array_flip($map);

		if (isset($map[$send_to])){
			return $send_to;
		}

		return null;
	}

	#################################################################

	function photos_upload_notifications_map($pretty_keys=0){

		$map = array(
			'fl' => 'flickr',
			'tw' => 'twitter',
		);

		if ($pretty_keys){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	function photos_upload_resolve_notifications($notify=null){

		$notifications = array();

		if ((! $notify) && (isset($GLOBALS['cfg']['uploads_default_send_to']))){
			$notify = $GLOBALS['cfg']['uploads_default_send_to'];
		}

		$short_map = photos_upload_notifications_map();
		$pretty_map = photos_upload_notifications_map("pretty keys");

		foreach (explode(",", $notify) as $n){

			if (isset($short_map[$n])){
				$notifications[] = $short_map[$n];
			}

			else if (isset($pretty_map[$n])){
				$notifications[] = $n;
			}

			else {}
		}

		return $notifications;
	}

	#################################################################

	function photos_upload(&$user, $file, $args){

		$defaults = array();
		$args = array_merge($defaults, $args);

		$send_to = $args['send_to'];

		$rsp = photos_upload_can_upload($user, $send_to);

		if (! $rsp['ok']){
			return $rsp;
		}

		if (isset($GLOBALS['cfg']['uploads_default_tags'])){
			$tags = ($args['tags']) ? $args['tags'] : '';
			$tags .= " {$GLOBALS['cfg']['uploads_default_tags']}";

			$args['tags'] = trim($tags);
		}

		if ($send_to == 'flickr'){

			$perms = $args['perms'];
			unset($args['perms']);

			$perms_hash = photos_utils_perms_strtohash($perms, "flickr api");
			$args = array_merge($args, $perms_hash);

			$rsp = flickr_photos_upload($user, $file, $args);
		}

		else {
			$rsp = parallel_flickr_photos_upload($user, $file, $args);
		}

		unlink($file);

		if (! $rsp['ok']){
			return $rsp;
		}

		$photo = array(
			'id' => $rsp['id'],
			'url' => $rsp['url'],
		);

		$rsp = array(
			'ok' => 1,
			'photo' => $photo
		);

		return $rsp;
	}

	#################################################################

	# Maybe. Something like this anyway. Not sure it should live here.
	# (20130701/straup)

	function photos_upload_can_upload(&$user, $send_to=''){

		if (! features_is_enabled("uploads")){
			return array('ok' => 0, 'error' => 'Uploads are disabled');
		}

		$is_registered = flickr_backups_is_registered_user($user);

		if (! $is_registered){
			return array('ok' => 0, 'error' => 'Not a registered user');
		}

		loadlib("flickr_api");
		loadlib("flickr_users");

		$perms_map = flickr_api_authtoken_perms_map();

		$flickr_user = flickr_users_get_by_user_id($user['id']);
		$flickr_perms = $flickr_user['token_perms'];

		$user_perms = $perms_map[$flickr_perms];

		if ($send_to == 'parallel-flickr'){

			if (! features_is_enabled("uploads_parallel_flickr")){
				return array('ok' => 0, 'error' => 'Uploads are not enabled');
			}

			# delete permissions

			if ($flickr_perms < 2){
				return array('ok' => 0, 'error' => 'Insufficient permissions');
			}

			if (! features_is_enabled("dbtickets_flickr")){
				return array('ok' => 0, 'error' => 'Invalid configuration');
			}

			return array('ok' => 1);
		}

		else if ($send_to == 'flickr'){

			if (! features_is_enabled("uploads_flickr")){
				return array('ok' => 0, 'error' => 'Uploads are not enabled');
			}

			# write permissions

			if ($flickr_perms < 1){
				return array('ok' => 0, 'error' => 'Insufficient permissions');
			}

			return array('ok' => 1);
		}

		else {}

		return array('ok' => 0, 'error' => "Invalid context ({$send_to})");
	}

	#################################################################

	# the end
