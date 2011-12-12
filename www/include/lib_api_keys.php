<?php

	# this is nowhere close to being done (20111114/straup)

	#################################################################

	function api_keys_get_by_key($key){

	}

	#################################################################

	function api_keys_get_for_user(&$user){

	}

	#################################################################

	function api_keys_create(&$user){

		$id = dbtickets_create(64);

		$key = api_keys_generate_key();
		$secret = random_string(64);

		$now = time();

		$key_row = array(
			'id' => $id,
			'user_id' => $user['id'],
			'app_key' => $key,
			'app_secret' => $secret,
			'created' => $now,
		);

		# TO DO: callbacks and other stuff (what?)

		$insert = array();

		foreach ($key_row as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert('ApiKeys', $insert);

		if ($rsp['ok']){
			$rsp['key'] = $key_row;
		}

		return $rsp;
	}

	#################################################################

	function api_keys_generate_key(){

		# TO DO: ensure unique-iness
		$key = md5(random_string(100));

		return $key;
	}

	#################################################################
?>
