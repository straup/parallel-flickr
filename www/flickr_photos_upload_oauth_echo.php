<?php

	require('include/init.php');
	loadlib('http');
	loadlib("flickr_users");
	loadlib("flickr_backups");
	loadlib('flickr_photos_upload');

	if (! $GLOBALS['cfg']['enable_feature_oauth_upload']) {
		error_disabled();
	}

	if (! $GLOBALS['cfg']['enable_feature_uploads']){
		error_disabled();
	}

	$auth_url = $_SERVER['HTTP_X_AUTH_SERVICE_PROVIDER'];

	// Unless we validated which service is the auth provider, anyone
	// could send a valid user ID and post on behalf of known p-flickr
	// and twitter users. Also, there may be more providers but Twitter
	// is the big one.
	if (! preg_match("#^https://api.twitter.com/#", $auth_url)) {
		exit;
	}

	$headers = array('Authorization' => $_SERVER['HTTP_X_VERIFY_CREDENTIALS_AUTHORIZATION']);
	$res = http_get($auth_url, $headers);

	if (! $res['ok']) {
		exit;
	}

	$body = json_decode($res['body'], true);
	$twitter_id = $body['id'];

	// TODO: this should really be a page that user's can access through settings
	if (! isset($GLOBALS['cfg']['oauth_upload_user_mapping'][$twitter_id])) {
		exit;
	}

	$user = users_get_by_id($GLOBALS['cfg']['oauth_upload_user_mapping'][$twitter_id]);

	$is_registered = flickr_backups_is_registered_user($user);
	$can_upload = $is_registered;

	if ($can_upload){
		$flickr_user = flickr_users_get_by_user_id($user['id']);
		$can_upload = flickr_users_has_token_perms($flickr_user, "write");
	}

	if (! $can_upload) {
		exit;
	}

	$filepath = $_FILES['media']['tmp_name'];

	if (! $filepath) {
		exit;
	}

	// See that blank space? If a Twitter message starts with an '@' symbol,
	// that gets interpreted by curl as a *filepath*. Flickr strips that 
	// blank space on save so this hack gets around that (nolan-20120929)
	$args = array(
		'title' => ' ' . $_POST['message'],
	);

	$res = flickr_photos_upload($user, $filepath, $args);

	if ($res['ok']) {
		print "<mediaurl>http://www.flickr.com/photos/{$flickr_user['nsid']}/{$res['photo_id']}/</mediaurl>";
	}

