<?php

	loadlib("random");
	loadlib("flickr_push");

	#################################################################

	function flickr_push_subscriptions_topic_map(){

		# these keys should match those defined in
		# flickr_push_topic_map()

		# the labels and the URLs get superseded by stuff
		# in lib_subscription_urls

		$map = array(
			1 => array('label' => 'your contacts photos', 'enabled' => 1, 'has_args' => 0),
			2 => array('label' => 'your contacts faves', 'enabled' => 1, 'has_args' => 0),
			3 => array('label' => 'your photos', 'enabled' => 1, 'has_args' => 0),
			4 => array('label' => 'your faves', 'enabled' => 1, 'has_args' => 0),
			5 => array('label' => 'photos of you', 'enabled' => 0, 'has_args' => 0),
			6 => array('label' => 'photos of your contacts', 'enabled' => 0, 'has_args' => 0),
			7 => array('label' => 'geotagged photos', 'enabled' => 0, 'has_args' => 1, 'requires_args' => 1),
			8 => array('label' => 'photos from the Commons', 'enabled' => 1, 'has_args' => 0),
			9 => array('label' => 'photos with tags', 'enabled' => 1, 'has_args' => 1, 'requires_args' => 1),
		);

		return $map;
	}

	#################################################################

	function flickr_push_subscriptions_is_valid_topic_id($id){

		$map = flickr_push_subscriptions_topic_map();

		if (! isset($map[$id])){
			return 0;
		}

		if (! $map[$id]['enabled']){
			return 0;
		}

		return 1;
	}

	#################################################################

	function flickr_push_subscriptions_is_push_backup(&$subscription){

		loadlib("flickr_backups");

		$owner = users_get_by_id($subscription['user_id']);

		if (! flickr_backups_is_registered_user($owner)){
			return 0;
		}

		if (! flickr_backups_is_registered_subscription($subscription)){
			return 0;
		}

		return 1;
	}

	#################################################################

	function flickr_push_subscriptions_generate_secret_url(){

		$tries = 0;
		$max_tries = 50;

		while (1){

			$tries += 1;

			$url = random_string(64);

			if (! flickr_push_subscriptions_get_by_secret_url($url)){
				return $url;
			}

			if ($tries >= $max_tries){
				return null;
			}
		}
	}

	#################################################################

	function flickr_push_subscriptions_get_by_id($id){

		$cache_key = "flickr_push_subscriptions_{$id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			$row = $cache['data'];
		}

		else {

			$enc_id = AddSlashes($id);
			$sql = "SELECT * FROM FlickrPushSubscriptions WHERE id='{$enc_id}'";

			$rsp = db_fetch($sql);
			$row = db_single($rsp);

			if ($row){
				cache_set($cache_key, $row, "cache locally");
			}
		}

		return $row;
	}

	#################################################################

	function flickr_push_subscriptions_get_by_secret_url($url){

		$cache_key = "flickr_push_subscriptions_secret_{$url}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			$row = $cache['data'];
		}

		else {

			$enc_url = AddSlashes($url);
			$sql = "SELECT * FROM FlickrPushSubscriptions WHERE secret_url='{$enc_url}'";

			$rsp = db_fetch($sql);
			$row = db_single($rsp);

			if ($row){
				cache_set($cache_key, $row, "cache locally");
			}
		}

		return $row;
	}

	#################################################################

	function flickr_push_subscriptions_get_subscriptions($more=array()){

		$sql = "SELECT * FROM FlickrPushSubscriptions ORDER BY id";
		$rsp = db_fetch_paginated($sql, $more);

		return $rsp;
	}

	#################################################################

	function flickr_push_subscriptions_get_subscriptions_for_user(&$user, $more=array()){

		$enc_user = AddSlashes($user['id']);
		$sql = "SELECT * FROM FlickrPushSubscriptions WHERE user_id='{$enc_user}' ORDER BY id";
		$rsp = db_fetch_paginated($sql, $args);

		return $rsp;
	}

	#################################################################

	function flickr_push_subscriptions_get_by_user_and_topic(&$user, $topic_id, $topic_args=null){

		if ($topic_args){
			$topic_args = json_encode($topic_args);
		}

		$cache_key = "flickr_push_subscriptions_user_{$user['id']}_{$topic_id}";

		if ($topic_args){
			$cache_key .= "#" . md5($topic_args);
		}

		$cache = cache_get($cache_key);

		if ($cache['ok']){
			$row = $cache['data'];
		}

		else {

			$enc_id = AddSlashes($user['id']);
			$enc_topic = AddSlashes($topic_id);

			$enc_args = ($topic_args) ? AddSlashes($topic_args) : "";

			$sql = "SELECT * FROM FlickrPushSubscriptions WHERE user_id='{$enc_id}' AND topic_id='{$enc_topic}'";

			if ($enc_args){
				$sql .= " AND topic_args='{$enc_args}'";
			}

			$rsp = db_fetch($sql);
			$row = db_single($rsp);

			if ($row){
				cache_set($cache_key, $row, "cache locally");
			}
		}

		return $row;
	}

	#################################################################

	function flickr_push_subscriptions_for_user_as_hash(&$user){

		$rsp = flickr_push_subscriptions_for_user($user);

		$subscriptions = array();

		foreach ($rsp['rows'] as $row){

			$topic_id = $row['topic_id'];

			if (! isset($subscriptions[$topic_id])){
				$subscriptions[$topic_id] = array();
			}

			$subscriptions[$topic_id][] = $row;
		}

		return $subscriptions;
	}

	#################################################################

	function flickr_push_subscriptions_for_user(&$user){

		$cache_key = "flickr_push_subscriptions_user_{$user['id']}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$cluster_id = $user['cluster_id'];
		$enc_user = AddSlashes($user['id']);

		$sql = "SELECT * FROM FlickrPushSubscriptions WHERE user_id='{$enc_user}'";
		$rsp = db_fetch_users($cluster_id, $sql);

		if ($rsp['ok']){
			cache_set($cache_key, $rsp, "cache locally");
		}

		return $rsp;
	}

	#################################################################

	function flickr_push_subscriptions_create_subscription($subscription){

		$user = users_get_by_id($subscription['user_id']);
		$cluster_id = $user['cluster_id'];

		$secret_url = flickr_push_subscriptions_generate_secret_url();

		if (! $secret_url){

			return array(
				'ok' => 0,
				'error' => 'Failed to generate secret URL',
			);
		}

		$token = random_string(32);

		$subscription['id'] = dbtickets_create();

		$subscription['secret_url'] = $secret_url;
		$subscription['verify_token'] = $token;
		$subscription['created'] = time();

		$insert = array();

		foreach ($subscription as $k => $v){

			# quick and dirty hack... unsure

			if ($k == 'topic_args'){
				$v = json_encode($v);
			}

			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert_users($cluster_id, 'FlickrPushSubscriptions', $insert);

		if ($rsp['ok']){
			$rsp['subscription'] = $subscription;

			$cache_key = "flickr_push_subscriptions_for_user_{$user['id']}";
			cache_unset($cache_key);
		}

		return $rsp;
	}

	#################################################################

	# this both adds the subscription to the database and registers
	# it with the flickr.push API

	function flickr_push_subscriptions_register_subscription($subscription){

		$rsp = flickr_push_subscriptions_create_subscription($subscription);

		if (! $rsp['ok']){
			return $rsp;
		}

		$subscription = $rsp['subscription'];

		$flickr_rsp = flickr_push_subscribe($subscription);

		if ($flickr_rsp['ok']){
			$flickr_rsp['subscription'] = $subscription;
		}

		return $flickr_rsp;
	}

	#################################################################

	# this both removes the subscription from the database and unsubscribes
	# it with the flickr.push API

	function flickr_push_subscriptions_remove_subscription($subscription, $force=0){

		$flickr_rsp = flickr_push_unsubscribe($subscription);

		if ((! $flickr_rsp['ok']) && (! $force)){
			return $flickr_rsp;
		}

		$rsp = flickr_push_subscriptions_delete($subscription);

		if ($rsp['ok']){
			_flickr_push_subscriptions_purge_cache_keys($subscription);
		}

		return $rsp;
	}

	#################################################################

	function flickr_push_subscriptions_delete(&$subscription){

		$user = users_get_by_id($subscription['user_id']);
		$cluster_id = $user['cluster_id'];

		$enc_id = AddSlashes($subscription['id']);

		$sql = "DELETE FROM FlickrPushSubscriptions WHERE id='{$enc_id}'";

		$rsp = db_write_users($cluster_id, $sql);

		if ($rsp['ok']){
			_flickr_push_subscriptions_purge_cache_keys($subscription);
		}

		return $rsp;
	}

	#################################################################

	function flickr_push_subscriptions_update(&$subscription, $update){

		$user = users_get_by_id($subscription['user_id']);
		$cluster_id = $user['cluster_id'];

		$hash = array();

		foreach ($update as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$enc_id = AddSlashes($subscription['id']);
		$where = "id='{$enc_id}'";

		$rsp = db_update_users($cluster_id, 'FlickrPushSubscriptions', $hash, $where);

		if ($rsp['ok']){
			_flickr_push_subscriptions_purge_cache_keys($subscription);
		}

		return $rsp;
	}

	#################################################################

	function _flickr_push_subscriptions_purge_cache_keys(&$subscription){

		$secret_key = "flickr_push_subscriptions_secret_{$subscription['secret_url']}";
		$topic_key = "flickr_push_subscriptions_user_{$subscription['user_id']}_{$subscription['topic_id']}";

		if ($topic_args = $subscription['topic_args']){
			$topic_key .= "#" . md5($topic_args);
		}

		$user_key = "flickr_push_subscriptions_for_user_{$subscription['user_id']}";

		$cache_keys = array(
			$secret_key,
			$topic_key,
			$user_key,
		);

		foreach ($cache_keys as $k){
			cache_unset($k);
		}
	}

	#################################################################
?>
