<?php

	loadlib("flickr_users");
	loadlib("flickr_contacts_import");
	loadlib("flickr_geobookmarks_import");
	loadlib("flickr_photos_import");
	loadlib("flickr_faves_import");
	loadlib("flickr_push");
	loadlib("flickr_push_subscriptions");

	# TO DO: add an optional flag that lets you offset the last mindate
	# by (n) seconds in case you need to backfill but not all the way back
	# to the start of time (20120105/straup)

	#################################################################

	function flickr_backups_type_map($string_keys=0){

		$map = array(
			0 => 'photos',
			1 => 'faves',
			2 => 'contacts',
			3 => 'geobookmarks'
		);

		if ($string_keys){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	function flickr_backups_push_topics_map(){

		$backup_map = flickr_backups_type_map("string keys");
		$push_map = flickr_push_topic_map("string keys");

		$map = array(
			$backup_map['photos'] => $push_map['my_photos'],
			$backup_map['faves'] => $push_map['my_faves'],
		);

		if ($push_keys){
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
				
			$rsp = okay(array(
				'backup' => $backups[$map[$type_id]],
			));	
		}

		else {}

		if ($rsp['ok']){
			$enabled = ($rsp['backup']['disabled']) ? 0 : 1;
			$push_rsp = flickr_backups_toggle_push_subscription($rsp['backup'], $enabled);
			$rsp['push_backup'] = $push_rsp;
		}

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

		$rsp = db_update('FlickrBackups', $hash, $where);

		if (($rsp['ok']) && (isset($update['disabled']))){

			$backup = array_merge($backup, $update);

			$enabled = ($update['disabled']) ? 0 : 1;
			$push_rsp = flickr_backups_toggle_push_subscription($backup, $enabled);

			$rsp['push_backup'] = $push_rsp;
		}

		return $rsp;
	}

	#################################################################

	# Note: we return three possible values instead of the usual two:
	# okay, not_okay and null. This is mostly so that we can distinguish
	# between backup types that are relevant to push backups (okay and
	# not_okay) and those that aren't (null). It's not sexy but then
	# again... who cares? (20120608/straup)

	function flickr_backups_toggle_push_subscription(&$backup, $enable){

		$push_features = array("flickr_push", "flickr_push_backups");

		if (! features_is_enabled($push_features)){
			return null;
		}

		# First, figure out if this backup type is a valid push
		# backup topic - this duplicates most/all of the code in
		# flickr_backups_is_push_backups but since we'll need the
		# stub subscription (and the topic map) in order to create
		# new subscriptions we're just not going to worry about it
		# too much (20120608/straup)

		$type_id = $backup['type_id'];

		$map = flickr_backups_push_topics_map();

		if (! isset($map[$type_id])){
			return null;
		}

		# Stub subscription data

		$user = users_get_by_id($backup['user_id']);
		$topic_id = $map[$type_id];

		$sub = array(
			'user_id' => $user['id'],
			'topic_id' => $topic_id,
		);

		if (! flickr_backups_is_registered_push_subscription($sub)){
			return null;
		}

		if ($enable){
			$push_rsp = flickr_push_subscriptions_register_subscription($sub);
		}

		else {

			# Okay, now fetch the actual subscription in order to unsubscribe

			$sub = flickr_push_subscriptions_get_by_user_and_topic($user, $topic_id);

			# Keeping in mind that it may not actually exist...

			if (! $sub){
				return okay();
			}

			$push_rsp = flickr_push_subscriptions_remove_subscription($sub, 1);
		}

		return $push_rsp;
	}

	#################################################################

	function flickr_backups_users($ensure_enabled=1){

		$sql = "SELECT DISTINCT(user_id) FROM FlickrBackups";
		$rsp = db_fetch($sql);

		$users = array();

		foreach ($rsp['rows'] as $row){

			if (($ensure_enabled) && ($row['disabled'])){
				continue;
			}

			$users[] = users_get_by_id($row['user_id']);
		}

		return $users;
	}

	#################################################################

	function flickr_backups_has_push_backup(&$backup){

		return flickr_backups_is_push_backup($backup, "check subscription");
	}

	#################################################################

	function flickr_backups_is_push_backup(&$backup, $check_subscription=0){

		$push_features = array(
			"flickr_push",
			"flickr_push_backups",
		);

		if (! features_is_enabled($push_features)){
			return 0;
		}

		$type_id = $backup['type_id'];

		$map = flickr_backups_push_topics_map();

		if (! isset($map[$type_id])){
			return 0;
		}

		# Stub subscription data

		$user = users_get_by_id($backup['user_id']);
		$topic_id = $map[$type_id];

		$sub = array(
			'user_id' => $user['id'],
			'topic_id' => $topic_id,
		);

		if (! flickr_backups_is_registered_push_subscription($sub)){
			return 0;
		}

		if ($check_subscription){

			if (! flickr_push_subscriptions_get_by_user_and_topic($user, $topic_id)){
				return 0;
			}
		}

		return 1;
	}

	#################################################################

	function flickr_backups_is_registered_user(&$user, $more=array()){

		$defaults = array(
			'ensure_enabled' => 1,
		);

		$more = array_merge($defaults, $more);

		$enc_user = AddSlashes($user['id']);
		$sql = "SELECT * FROM FlickrBackups WHERE user_id='{$enc_user}'";

		$rsp = db_fetch($sql);
		$row = db_single($rsp);

		if (! $row){
			return 0;
		}

		if (($more['ensure_enabled']) && ($row['disabled'])){
			return 0;
		}

		return 1;
	}

	#################################################################

	function flickr_backups_ensure_registered_user($user=null){

		if ((! $user) && (! flickr_backups_is_registered_user($user))){
			error_disabled();
		}
	}

	# end of this is a parallel-flickr-ism

	#################################################################

	function flickr_backups_is_registered_subscription(&$sub){
		return flickr_backups_is_registered_push_subscription($sub);
	}

	function flickr_backups_is_registered_push_subscription(&$sub){

		loadlib("flickr_push");
		$map = flickr_push_topic_map("string keys");

		# for now, anyway...

		$valid = array(
			$map['my_photos'],
			$map['my_faves'],
			$map['commons'],
		);

		return (in_array($sub['topic_id'], $valid)) ? 1 : 0;
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

			$row['is_push_backup'] = flickr_backups_is_push_backup($row);
			$row['has_push_backup'] = 0;

			if ($row['is_push_backup']){
				$row['has_push_backup'] = flickr_backups_has_push_backup($row, 'check subscription');
			}

			$type = $map[$row['type_id']];
			$backups[$type] = $row;		
		}

		return $backups;
	}

	#################################################################

	function flickr_backups_get_photos(&$user){

		$backups = flickr_backups_for_user($user);

		if (! isset($backups['photos'])){

			return not_okay("backups not registered");
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

			return not_okay("backups for faves not registered");
		}

		#

		$flickr_user = flickr_users_get_by_user_id($user['id']);

		$backup = $backups['faves'];
		$update = array();

		$start_time = time();
		$more = array();

		# for debugging (20130528/straup)
		# $backup['date_lastupdate'] -= 100000;

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

			return not_okay("backups not registered");
		}

		$backup = $backups['contacts'];
		$update = array();

		$start_time = time();

		$flickr_user = flickr_users_get_by_user_id($user['id']);

		$more = array(
			'purge_existing_contacts' => 1
		);

		$rsp = flickr_contacts_import_for_nsid($flickr_user['nsid'], $more);

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

	function flickr_backups_get_geobookmarks(&$user){

		$backups = flickr_backups_for_user($user);

		if (! isset($backups['geobookmarks'])){

			# return not_okay("backups not registered");
		}

		$backup = $backups['geobookmarks'];
		$update = array();

		$start_time = time();

		$flickr_user = flickr_users_get_by_user_id($user['id']);

		$rsp = flickr_geobookmarks_import_for_nsid($flickr_user['nsid'], $more);

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
