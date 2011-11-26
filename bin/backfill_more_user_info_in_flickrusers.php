<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	include("include/init.php");

	loadlib("backfill");
	loadlib("flickr_api");
	loadlib("flickr_users");

	function _get_nsid($flickr_user, $more=array()){

		# TO DO: put this all in a function somewhere and
		# call/queue it when a user signs up...

		# As db_main:Users
		$user = users_get_by_id($flickr_user['user_id']);

		if (! $user){
			return;
		}

	        $method = 'flickr.people.getInfo';

	        $args = array(
			'user_id' => $flickr_user['nsid'],
		);

		$ret = flickr_api_call($method, $args);

		if (! $ret['ok']){
			dumper($args);
			dumper($ret);
			return;
		}

		$rsp = $ret['rsp']['person'];

		$path_alias = $rsp['path_alias'];
		$username = $rsp['username']['_content'];

		echo "[{$user['id']}] path alias: {$path_alias} screen name: {$username}\n";

		if ($path_alias != $flickr_user['path_alias']){

			$update = array(
				'path_alias' => $path_alias,
			);

			$rsp = flickr_users_update_user($flickr_user, $update);

			echo "[{$user['id']}] update path alias: {$rsp['ok']}\n";
		}

		if ($username != $user['username']){

			$update = array(
				'username' => $username,
			);

			$rsp = users_update_user($user, $update);

			echo "[{$user['id']}] update username: {$rsp['ok']}\n";
		}

	}

	# TO DO: a flag/option to only fetch users w/out a path_alias

	$sql = "SELECT * FROM FlickrUsers";
	backfill_db_users($sql, '_get_nsid');

	exit();
?>
