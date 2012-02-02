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

		$all_contacts = array();
		$count_contacts = 0;

		$args = array(
			'auth_token' => $flickr_user['auth_token'],
			'per_page' => 100,
			'page' => 1,
		);

		$pages = null;

		while ((! isset($pages)) || ($pages >= $args['page'])){

			$api_ok = 0;
			$api_error = '';

			# Can I just say this is so profoundly annoying. Why why why
			# are API calls to a federated database table failing? Anyway.
			# (20120201/straup)

			$retries = 0;
			$max_retries = 10;

			while (! $api_ok){

				$retries += 1;

				$rsp = flickr_api_call($method, $args);
				$api_ok = $rsp['ok'];

				if (! $api_ok){
					$api_error = "The Flickr API is wigging out: {$rsp['error']}";
				}

				else {
					$contacts = $rsp['rsp']['contacts']['contact'];

					if (! is_array($contacts)){
						$api_error = "The Flickr API did not return any contacts";
						$api_ok = 0;
					}
				}

				echo "page: {$args['page']}/{$pages} tries: {$retries}/{$max_retries} ok: {$api_ok}\n";

				if (! $api_ok){

					if ($retries == $max_retries){
						return not_okay("Unable to fetch contacts: {$api_error}");
					}
				}
			}

			if (! isset($pages)){
				$pages = $rsp['rsp']['contacts']['pages'];
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

				$all_contacts[] = $insert;
			}

			$args['page'] += 1;
		}

		if (isset($more['purge_existing_contacts'])){

			$rsp = flickr_contacts_purge_contacts($user);

			if (! $rsp['ok']){
				return not_okay("failed to purge existing contacts: {$rsp['error']}");
			}
		}

		# echo "import " . count($all_contacts) . " contacts\n";

		foreach ($all_contacts as $insert){

			if (flickr_contacts_add_contact($insert)){
				$count_contacts ++;
			}
		}

		return array(
			'ok' => 1,
			'count_imported' => $count_contacts,
		);
	}

	#################################################################

?>
