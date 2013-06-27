<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);
	include("include/init.php");

	# https://secure.flickr.com/services/api/flickr.auth.oauth.getAccessToken.html
	# https://secure.flickr.com/groups/api/discuss/72157630417775168/

	echo "please write me\n";
	echo "the documentation for this method doesn't actually make any sense...\n";

	exit();

	loadlib("backfill");
	loadlib("flickr_users");
	loadlib("flickr_api");
	loadlib("flickr_api_oauth");

	function update_user($flickr_user){

		if (! $flickr['auth_token']){
			return;
		}

		if ($flickr['oauth_token']){
			return;
		}

		$method = 'flickr.auth.oauth.getAccessToken';

		$args = array(
			'auth_token' => $flickr_user['auth_token'],
		);

		$rsp = flickr_api_call($method, $args);

		if (! $rsp['ok']){
			dumper($rsp);
			return;
		}

		# Yes?
		$token = $rsp['rsp']['auth']['access_token'];

		$update = array(
			'oauth_token' => $token['oauth_token'],
			'oauth_secret' => $token['oauth_token_secret'],
		);

		flickr_users_update_user($flickr_user $update);
	}

	$sql = "SELECT * FROM FlickrUsers";
	# backfill_db_main($sql, "update_user");

?>
