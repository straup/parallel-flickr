<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	#

	include("include/init.php");

	loadlib("flickr_api");
	loadlib("flickr_users");
	loadlib("flickr_contacts");

	loadlib("random");

	$nsid = '35034348999@N01';

	$flickr_user = flickr_users_get_by_nsid($nsid);
	$user = users_get_by_id($flickr_user['user_id']);

	# purge and restore

	$cluster_id = $user['cluster_id'];
	$enc_id = AddSlashes($user['id']);

	$sql = "DELETE FROM FlickrContacts WHERE user_id='{$enc_id}'";
	$rsp = db_write_users($cluster_id, $sql);

	# re-fetch

	$method = 'flickr.contacts.getList';

	$args = array(
		'auth_token' => $flickr_user['auth_token'],
		'per_page' => 100,
		'page' => 1,
	);

	$pages = null;

	while ((! isset($pages)) || ($pages >= $args['page'])){

		$rsp = flickr_api_call($method, $args);

		if (! $rsp){
			exit();
		}

		if (! isset($pages)){
			$pages = $rsp['rsp']['contacts']['pages'];
		}

		$contacts = $rsp['rsp']['contacts']['contact'];

		if (! is_array($contacts)){
			exit;
		}

		foreach ($contacts as $contact){

			$contact_nsid = $contact['nsid'];
			$contact_username = $contact['username'];

			#

			$flickr_contact = flickr_users_get_by_nsid($contact_nsid);

			if (! $flickr_contact){

				$password = random_string(32);

				$user_contact = users_create_user(array(
					"username" => $contact_username,
					"email" => "{$contact_username}@donotsend-flickr.com",
					"password" => $password,
				));

				$flickr_contact = flickr_users_create_user(array(
					'user_id' => $user_contact['id'],
					'nsid' => $contact_nsid,
					# note the lack of an auth_token
				));
			}

			#

			$friend = $contact['friend'];
			$family = $contact['family'];

			# TODO: put me in a function...

			$map = flickr_contacts_relationship_map('string keys');
			$rel = 0;

			if (($family) && ($friend)){
				$rel = $map['frfa'];
			}

			else if ($family){
				$rel = $map['family'];
			}

			else if ($friend){
				$rel = $map['friend'];
			}

			else {
				$rel = $map['contact'];
			}

			# echo "{$contact_username} ({$flickr_contact['user_id']}) fr:{$friend} fa:{$family} rel:{$rel}\n";

			#

			$insert = array(
				'user_id' => $user['id'],
				'contact_id' => $flickr_contact['user_id'],
				'rel' => $rel,
			);

			$contact = flickr_contacts_add_contact($insert);
		}

		$args['page'] += 1;
	}

	exit();
?>
