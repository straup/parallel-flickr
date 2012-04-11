<?php

	loadlib("random");

	#################################################################

	function invite_codes_generate_cookie(&$invite){

		$raw = implode("-", array(
			$invite['code'],
			$invite['created']
		));

		return crypto_encrypt($raw, $GLOBALS['cfg']['crypt_invite_secret']);
	}

	#################################################################

	function invite_codes_get_by_cookie($cookie=''){

		$cookie = login_get_cookie('invite');

		if (! $cookie){
			return null;
		}

		$cookie = crypto_decrypt($cookie, $GLOBALS['cfg']['crypt_invite_secret']);

		if (! $cookie){
			return null;
		}

		$cookie = explode("-", $cookie, 2);

		if (count($cookie) != 2){
			return null;
		}

		return invite_codes_get_by_code($cookie[0], $cookie[1]);
	}

	function invite_codes_set_cookie(&$invite){

		$cookie = invite_codes_generate_cookie($invite);

		$expires = time() * 2;
		login_set_cookie('invite', $cookie, $expires);
	}

	#################################################################

	function invite_codes_get_by_email($email){

		$cache_key = "invite_codes_email_{$email}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$enc_email = AddSlashes($email);

		$sql = "SELECT * FROM InviteCodes WHERE email='{$enc_email}'";
		$rsp = db_fetch($sql);

		$row = db_single($rsp);

		if ($row){
			cache_set($cache_key, $row, "cache locally");
		}

		return $row;
	}

	#################################################################

	function invite_codes_get_by_code($code, $ensure_sent=1){

		$cache_key = "invite_codes_code_{$code}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$enc_code = AddSlashes($code);

		$sql = "SELECT * FROM InviteCodes WHERE code='{$code}'";

		$rsp = db_fetch($sql);
		$row = db_single($rsp);

		if (($ensure_sent) && (! $row['sent'])){
			$row = null;
		}

		if ($row){
			cache_set($cache_key, $row, "cache locally");
		}

		return $row;
	}

	#################################################################

	function invite_codes_create($email, $more=array()){

		if ($invite = invite_codes_get_by_email($email)){

			return array(
				'ok' => 1,
				'invite' => $invite
			);
		}

		$code = null;
		$tries = 0;

		while (! $code){

			$code = random_string(12);
			$tries += 1;

			if (invite_codes_get_by_code($code)){
				$code = null;
			}

			if ($tries == 50){
				break;
			}
		}

		if (! $code){

			return array(
				'ok' => 0,
				'error' => 'Failed to generate code',
			);
		}

		$invite = array(
			'email' => $email,
			'code' => $code,
			'created' => time(),
		);

		if (isset($more['invited_by'])){
			$invite['invited_by'] = intval($more['invited_by']);
		}

		$hash = array();

		foreach ($invite as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$rsp = db_insert('InviteCodes', $hash);

		if ($rsp['ok']){
			$rsp['invite'] = $invite;
		}

		return $rsp;
	}

	#################################################################

	function invite_codes_invite_user($email, $more=array()){

		$rsp = invite_codes_create($email, $more);

		if (($rsp['ok']) && (isset($more['send_email']))){

			invite_codes_send_invite($rsp['invite']);
		}

		return $rsp;
	}

	#################################################################

	function invite_codes_update(&$invite, &$update){

		$hash = array();
			
		foreach ($update as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$enc_code = AddSlashes($invite['code']);
		$where = "code='{$enc_code}'";

		$rsp = db_update('InviteCodes', $hash, $where);

		if ($rsp['ok']){

			$keys = array(
				"invite_codes_code_{$invite['code']}",
				"invite_codes_email_{$invite['email']}",
			);

			foreach ($keys as $k){
				cache_unset($k);
			}
		}

		return $rsp;
	}

	#################################################################

	function invite_codes_delete(&$invite){

		$enc_code = AddSlashes($invite['code']);
		$sql = "DELETE FROM InviteCodes WHERE code='{$enc_code}'";

		$rsp = db_write('InviteCodes', $sql);

		if ($rsp['ok']){

			$keys = array(
				"invite_codes_code_{$invite['code']}",
				"invite_codes_email_{$invite['email']}",
			);

			foreach ($keys as $k){
				cache_unset($k);
			}
		}

		return $rsp;
	}

	#################################################################

	function invite_codes_register_invite(&$invite){

		return array(
			'ok' => 1,
		);
	}

	#################################################################

	function invite_codes_send_invite(&$invite, $template='email_invite_code.txt'){

		$args = array(
			'to_email' => $invite['email'],
			'template' => $template,
			'from_name' => "{$GLOBALS['cfg']['site_name']} Email Robot",
			'from_email' => "do-not-reply@mail.{$_SERVER['SERVER_NAME']}",
		);

		$GLOBALS['smarty']->assign_by_ref("invite", $invite);

		# There's not really a good way to check the response
		# of this... which is frustrating.

		email_send($args);

		$update = array(
			'sent' => time(),
		);

		$rsp = invite_codes_update($invite, $update);

		return array(
			'ok' => 1,
		);
	}

	#################################################################

	function invite_codes_signin(&$invite, $redir=''){

		if (! $invite['redeemed']){

			$update = array(
				'redeemed' => time(),
			);

			invite_codes_update($invite, $update);
		}

		invite_codes_set_cookie($invite);

		$goto = "/signin/";

		if ($redir){
			$goto .= "?redir=" . urlencode($redir);
		}

		header("location: {$goto}");
		exit();
	}

	#################################################################

	function invite_codes_get_all(&$args){

		$sql = "SELECT * FROM InviteCodes";

		$sql .= " ORDER BY created DESC";

		return db_fetch_paginated($sql, $args);
	}

	#################################################################
?>
