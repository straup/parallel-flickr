<?php

	include("include/init.php");

	loadlib("flickr_api");
	loadlib("flickr_users");
	loadlib("random");

	# Some basic sanity checking like are you already logged in?

	if ($GLOBALS['cfg']['user']['id']){
		header("location: {$GLOBALS['cfg']['abs_root_url']}");
		exit();
	}


	if (! $GLOBALS['cfg']['enable_feature_signin']){
		$GLOBALS['smarty']->display("page_signin_disabled.txt");
		exit();
	}

	# Make sure that Flickr has sent back a frob

	$frob = get_str("frob");
	$extra = get_str("extra");

	if (! $frob){
		$GLOBALS['error']['missing_frob'] = 1;
		$GLOBALS['smarty']->display("page_auth_callback_flickr_flickrauth.txt");
		exit();
	}

	if ($extra){
		$_extra = urldecode($extra);
		parse_str($_extra, $extra);
	}

	# Now we exchange the frob for an access token that
	# we can call the Flickr API with.

	$args = array(
		"frob" => $frob,
	);

	$more = array(
		'sign' => 1,
	);

	$rsp = flickr_api_call("flickr.auth.getToken", $args, $more);

	if (! $rsp['ok']){
		$GLOBALS['error']['missing_token'] = 1;
		$GLOBALS['smarty']->display("page_auth_callback_flickr_flickrauth.txt");
		exit();
	}

	# Hey look! If we've gotten this far then that means we've been able
	# to use the Flickr API to validate the user and we've got an auth_token
	# we can use to call the Flickr API with on the user's behalf.

	$auth = $rsp['rsp']['auth'];

	$nsid = $auth['user']['nsid'];
	$username = $auth['user']['username'];
	$token = $auth['token']['_content'];

	# The first thing we do is check to see if we already have an account
	# matching that user's Flickr NSID.

	$flickr_user = flickr_users_get_by_nsid($nsid);

	if ($user_id = $flickr_user['user_id']){

		$user = users_get_by_id($user_id);

		if ((! $flickr_user['auth_token']) || ($flickr_user['auth_token'] != $token)){

			$update = array(
				'auth_token' => $token,
			);

			$rsp = flickr_users_update_user($flickr_user, $update);

			if (! $rsp['ok']){
				$GLOBALS['error']['dberr_flickruser_update'] = 1;
				$GLOBALS['smarty']->display("page_auth_callback_flickr_flickrauth.txt");
				exit();
			}
		}
	}

	# If we don't ensure that new users are allowed to create
	# an account (locally).

	else if (! $GLOBALS['cfg']['enable_feature_signup']){
		$GLOBALS['smarty']->display("page_signup_disabled.txt");
		exit();
	}

	# Hello, new user! This part will create entries in two separate
	# databases: Users and FlickrUsers that are joined by the primary
	# key on the Users table.

	else {

		$password = random_string(32);

		$user = users_create_user(array(
			"username" => $username,
			"email" => "{$username}@donotsend-flickr.com",
			"password" => $password,
		));

		if (! $user){
			$GLOBALS['error']['dberr_user'] = 1;
			$GLOBALS['smarty']->display("page_auth_callback_flickr_flickrauth.txt");
			exit();
		}

		#

		$method = 'flickr.people.getInfo';

		$args = array(
			'user_id' => $nsid,
		);

		$rsp = flickr_api_call($method, $args);
		$path_alias = ($rsp['ok']) ? $rsp['rsp']['person']['path_alias'] : '';

		#

		$flickr_user = flickr_users_create_user(array(
			'user_id' => $user['id'],
			'nsid' => $nsid,
			'path_alias' => $path_alias,
			'auth_token' => $token,
		));

		if (! $flickr_user){
			$GLOBALS['error']['dberr_flickruser'] = 1;
			$GLOBALS['smarty']->display("page_auth_callback_flickr_flickrauth.txt");
			exit();
		}
	}

	# Okay, now finish logging the user in (setting cookies, etc.) and
	# redirecting them to some specific page if necessary.

	$redir = (isset($extra['redir'])) ? $extra['redir'] : '';

	login_do_login($user, $redir);
	exit();

?>
