<?php

	#
	# $Id$
	#

	#################################################################

	$GLOBALS['users_local_cache'] = array();

	#################################################################

	#
	# create a user record. the fields pass in $user
	# ARE NOT ESCAPED.
	#

	function users_create_user($user){

		#
		# set up some extra fields first
		#

		loadlib('random');

		$user['password'] = login_encrypt_password($user['password']);
		$user['created'] = time();
		$user['conf_code'] = random_string(24);

		$user['cluster_id'] = users_assign_cluster_id();

		#
		# now create the escaped version
		#

		$hash = array();
		foreach ($user as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$rsp = db_insert('Users', $hash);

		if (!$rsp['ok']){
			return null;
		}


		#
		# cache the unescaped version
		#

		$user['id'] = $rsp['insert_id'];

		$cache_key = "user_{$user['id']}";
		cache_set($cache_key, $user, 'cache locally');

		return $user;
	}

	#################################################################

	#
	# update multiple fields on an user record. the hash passed
	# in $update IS NOT ESCAPED.
	#

	function users_update_user(&$user, $update){

		foreach ($update as $k => $v){
			$update[$k] = AddSlashes($v);
		}

		$rsp = db_update('Users', $update, "id=$user[id]");

		if (!$rsp['ok']){
			return $rsp;
		}

		$cache_key = "user_{$user['id']}";
		cache_unset($cache_key);

		return $rsp;
	}

	#################################################################

	function users_update_password(&$user, $new_password){

		$enc_password = login_encrypt_password($new_password);

		return users_update_user($user, array(
			'password' => AddSlashes($enc_password),
		));
	}

	#################################################################

	function users_delete_user(&$user){

		# rely on mysql to enforce a unique key
		# on (email, deleted)

		$new_email = "{$user['email']}.DELETED";

		$rsp = users_update_user($user, array(
			'deleted'	=> time(),
			'email'		=> AddSlashes($new_email),

			# reset the password here ?
		));

		if (! $rsp['ok']){
			return $rsp;
		}

		#
		# check to see if the application (outside of
		# flamework) has defined a callback function
		# to run once the user has been 'deleted' in
		# the database.
		#

		if (function_exists('users_delete_user_callback')){
			users_reload_user($user);
			$rsp['callback'] = users_delete_user_callback($user);
		}

		return $rsp;
	}

	#################################################################

	function users_reload_user(&$user){

		$user = users_get_by_id($user['id']);
	}

	#################################################################

	function users_get_by_id($id){

		$cache_key = "user_{$id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$user = db_single(db_fetch("SELECT * FROM Users WHERE id=".intval($id)));

		cache_set($cache_key, $user, 'cache locally');
		return $user;
	}

	#################################################################

	function users_get_by_email($email){

		$enc_email = AddSlashes($email);

		return db_single(db_fetch("SELECT * FROM users WHERE email='{$enc_email}'"));
	}

	#################################################################

	function users_get_by_login($email, $password){

		$user = users_get_by_email($email);

		if (!$user){
			return null;
		}

		if ($user['deleted']){
			return null;
		}

		if ($user['password'] != login_encrypt_password($password)){
			return null;
		}

		return $user;
	}

	#################################################################

	function users_is_email_taken($email){

		$enc_email = AddSlashes($email);

		$row = db_single(db_fetch("SELECT id FROM users WHERE email='{$enc_email}' AND deleted=0"));

		return $row['id'] ? 1 : 0;
	}

	#################################################################

	function users_is_username_taken($username){

		$enc_username = AddSlashes($username);

		$row = db_single(db_fetch("SELECT id FROM users WHERE username='{$enc_username}' AND deleted=0"));
		return $row['id'] ? 1 : 0;
	}

	#################################################################

	function users_get_by_password_reset_code($code){

		$enc_code = AddSlashes($code);

		$row = db_single(db_fetch("SELECT * FROM UsersPasswordReset WHERE reset_code='{$enc_code}'"));

		if (!$row){
			return null;
		}

		return users_get_by_id($row['user_id']);
	}

	#################################################################

	function users_purge_password_reset_codes(&$user){

		$rsp = db_write("DELETE FROM UsersPasswordReset WHERE user_id=$user[id]");

		return $rsp['ok'];
	}

	#################################################################

	function users_send_password_reset_code(&$user){

		$code = users_generate_password_reset_code($user);
		if (!$code) return 0;

		$GLOBALS['smarty']->assign('code', $code);

		email_send(array(
			'to_email'	=> $user['email'],
			'template'	=> 'email_password_reset.txt',
		));

		return 1;
	}

	#################################################################

	function users_generate_password_reset_code(&$user){

		loadlib('random');

		users_purge_password_reset_codes($user);

		$code = '';

		while (!$code){

			$code = random_string(32);
			$enc_code = AddSlashes($code);

			if (db_single(db_fetch("SELECT 1 FROM UsersPasswordReset WHERE reset_code='{$enc_code}'"))){
				$code = '';
			}

			break;
		}

		$rsp = db_insert('UsersPasswordReset', array(
			'user_id'	=> $user['id'],
			'reset_code'	=> $enc_code,
			'created'	=> time(),
		));

		if (!$rsp['ok']){
			return null;
		}

		return $code;
	}

	#################################################################

	function users_assign_cluster_id(){

		if ($GLOBALS['cfg']['db_enable_poormans_federation']){
			return 1;
		}

		# TO DO: an actual cluster ID if federated

		return 1;
	}

	#################################################################

	function users_ensure_valid_user_from_url($method=''){

		if (strtolower($method) == 'post'){
			$user_id = post_int64('user_id');
		}

		else {
			$user_id = get_int64('user_id');
		}

		if (! $user_id){
			error_404();
		}

		$user = users_get_by_id($user_id);

		if ((! $user) || ($user['deleted'])){
			error_404();
		}

		return $user;
	}

	#################################################################

	function users_get_users($args=array()){

		$sql = "SELECT * FROM Users ORDER BY created DESC";
		return db_fetch_paginated($sql, $args);
	}

	#################################################################
?>
