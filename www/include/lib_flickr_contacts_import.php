<?php

	loadlib("flickr_api");
	loadlib("flickr_users");
	loadlib("flickr_contacts");
	loadlib("random");

	#################################################################

	function flickr_contacts_import_for_nsid($nsid, $more=array()){

		$flickr_user = flickr_users_get_by_nsid($nsid);
		$user = users_get_by_id($flickr_user['user_id']);

		if (! $user){
			return array(
				'ok' => 0,
				'error' => 'not a valid user',
			);
		}

		$method = 'flickr.contacts.getList';
		$count_contacts = 0;

		$args = array(
			'auth_token' => $flickr_user['auth_token'],
			'per_page' => 100,
			'page' => 1,
		);

		$pages = null;

		while ((! isset($pages)) || ($pages >= $args['page'])){

			$rsp = flickr_api_call($method, $args);

			if (! $rsp){
				return array(
					'ok' => 0,
					'error' => 'The Flickr API is wigging out...',
				);
			}

			if (! isset($pages)){
				$pages = $rsp['rsp']['contacts']['pages'];
			}

			$contacts = $rsp['rsp']['contacts']['contact'];

			if (! is_array($contacts)){
				return array(
					'ok' => 0,
					'error' => 'The Flickr API did not return any contacts',
				);
			}

			foreach ($contacts as $contact){

				$contact_nsid = $contact['nsid'];
				$contact_username = $contact['username'];

				$flickr_contact = flickr_users_get_by_nsid($contact_nsid);

				if (! $flickr_contact){

					$password = random_string(32);

					$user_contact = users_create_user(array(
						"username" => $contact_username,
						"email" => "{$contact_username}@donotsend-flickr.com",
						"password" => $password,
					));

					#

					$method = 'flickr.people.getInfo';

					$args = array(
						'user_id' => $contact_nsid,
					);

					$rsp = flickr_api_call($method, $args);
					$path_alias = ($rsp['ok']) ? $rsp['rsp']['person']['path_alias'] : '';

					#

					$flickr_contact = flickr_users_create_user(array(
						'user_id' => $user_contact['id'],
						'nsid' => $contact_nsid,
						'path_alias' => $path_alias,
						# note the lack of an auth_token
					));
				}

				$rel = flickr_contacts_calculate_relationship($contact);
				# echo "{$contact_username} : {$rel} ({$contact['friend']} {$contact['family']})\n";

				$insert = array(
					'user_id' => $user['id'],
					'contact_id' => $flickr_contact['user_id'],
					'rel' => $rel,
				);

				$contact = flickr_contacts_add_contact($insert);
				$count_contacts ++;
			}

			$args['page'] += 1;
		}

		return array(
			'ok' => 1,
			'count_imported' => $count_contacts,
		);
	}

	#################################################################

?>
