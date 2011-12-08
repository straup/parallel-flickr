<?php

	loadlib("flickr_users");
	loadlib("flickr_contacts_import");
	loadlib("flickr_photos_import");
	loadlib("flickr_faves_import");

	#################################################################

	function flickr_backups_type_map($string_keys=0){

		$map = array(
			0 => 'photos',
			1 => 'faves',
			2 => 'contacts',
		);

		if ($string_keys){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	function flickr_backups_create(&$user, $type_id){

		$now = time();

		$backup = array(
			'user_id' => $user['id'],
			'type_id' => $type_id,
			'date_created' => $now,
		);

		$hash = array();

		foreach ($backup as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$rsp = db_insert('FlickrBackups', $hash);

		if ($rsp['ok']){
			$rsp['backup'] = $backup;
		}

		else if ($rsp['error_code'] == 1062){

			$map = flickr_backups_type_map();
			$backups = flickr_backups_for_user($user, $type_id);
				
			$rsp = array(
				'ok' => 1,
				'backup' => $backups[$map[$type_id]],
			);	
		}

		else {}

		return $rsp;
	}

	#################################################################

	function flickr_backups_update(&$backup, $update){

		$hash = array();

		foreach ($update as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$enc_user = AddSlashes($backup['user_id']);
		$enc_type = AddSlashes($backup['type_id']);

		$where = "user_id='{$enc_user}' AND type_id='{$enc_type}'";

		return db_update('FlickrBackups', $hash, $where);
	}

	#################################################################

	function flickr_backups_users(){

		$sql = "SELECT DISTINCT(user_id) FROM FlickrBackups";
		$rsp = db_fetch($sql);

		$users = array();

		foreach ($rsp['rows'] as $row){
			$users[] = users_get_by_id($row['user_id']);
		}

		return $users;
	}

	#################################################################

	function flickr_backups_for_user(&$user, $type_id=null){

		$enc_user = AddSlashes($user['id']);
		$sql = "SELECT * FROM FlickrBackups WHERE user_id='{$enc_user}'";

		if (isset($type_id)){

			$enc_type = AddSlashes($type_id);
			$sql .= " AND type_id='{$enc_type}'";
		}

		$rsp = db_fetch($sql);
		$backups = array();

		$map = flickr_backups_type_map();

		foreach($rsp['rows'] as $row){
			$type = $map[$row['type_id']];
			$backups[$type] = $row;
		}

		return $backups;
	}

	#################################################################

	function flickr_backups_get_photos(&$user){

		$backups = flickr_backups_for_user($user);

		if (! isset($backups['photos'])){

			return array(
				'ok' => 0,
				'error' => 'backups not registered',
			);
		}

		#

		$flickr_user = flickr_users_get_by_user_id($user['id']);

		$backup = $backups['photos'];
		$update = array();

		$start_time = time();

		if (! $backup['date_firstupdate']){

			$rsp = flickr_photos_import_for_nsid($flickr_user['nsid']);
		}

		else {

			$more = array(
				'min_date' => $backup['date_lastupdate'],
			);

			$rsp = flickr_photos_import_get_recent($flickr_user['nsid'], $more);
		}

		#

		if ($rsp['ok']){
			$update['date_lastupdate'] = $start_time;
			$update['details'] = "count: {$rsp['count_imported']}";

			if (! $backup['date_firstupdate']){
				$update['date_firstupdate'] = $update['date_lastupdate'];
			}

		}

		else {
			$update['details'] = "update failed ($start_time) : {$rsp['error']}";
		}

		flickr_backups_update($backup, $update);

		return $rsp;
	}

	#################################################################

	function flickr_backups_get_faves(&$user){

		$backups = flickr_backups_for_user($user);

		if (! isset($backups['faves'])){

			return array(
				'ok' => 0,
				'error' => 'backups for faves not registered',
			);
		}

		#

		$flickr_user = flickr_users_get_by_user_id($user['id']);

		$backup = $backups['faves'];
		$update = array();

		$start_time = time();
		$more = array();

		if ($backup['date_firstupdate']){
			$more['min_fave_date'] = $backup['date_lastupdate'];
		}

		$rsp = flickr_faves_import_for_nsid($flickr_user['nsid'], $more);

		if ($rsp['ok']){
			$update['date_lastupdate'] = $start_time;
			$update['details'] = "count: {$rsp['count_imported']}";

			if (! $backup['date_firstupdate']){
				$update['date_firstupdate'] = $update['date_lastupdate'];
			}
		}

		else {
			$update['details'] = "update failed ($start_time) : {$rsp['error']}";
		}

		flickr_backups_update($backup, $update);

		return $rsp;
	}

	#################################################################

	function flickr_backups_get_contacts(&$user){

		$backups = flickr_backups_for_user($user);

		if (! isset($backups['contacts'])){

			return array(
				'ok' => 0,
				'error' => 'backups not registered',
			);
		}

		$backup = $backups['contacts'];
		$update = array();

		$start_time = time();

		$rsp = flickr_contacts_purge_contacts($user);

		if ($rsp['ok']){

			$flickr_user = flickr_users_get_by_user_id($user['id']);
			$rsp = flickr_contacts_import_for_nsid($flickr_user['nsid']);
		}

		if ($rsp['ok']){
			$update['date_lastupdate'] = $start_time;
			$update['details'] = "count: {$rsp['count_imported']}";

			if (! $backup['date_firstupdate']){
				$update['date_firstupdate'] = $update['date_lastupdate'];
			}
		}

		else {
			$update['details'] = "update failed ($start_time) : {$rsp['error']}";
		}

		flickr_backups_update($backup, $update);

		return $rsp;
	}

	#################################################################
?>
