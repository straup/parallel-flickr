<?php

	# EXPERIMENTAL (20111126/straup)

	# Also, see how there are no 'subscribe' pages? That's because
	# if we use this all the subscription stuff will happen on other
	# parallel-flickr related pages that will call library code directly
	# (20111126/straup)

	include("include/init.php");

	if (! $GLOBALS['cfg']['enable_feature_flickr_push']){
		error_disabled();
	}

	loadlib("flickr_push_subscriptions");
	loadlib("syndication_atom");

	$secret_url = get_str("secret_url");

	if (! $secret_url){
		error_404();
	}

	$subscription = flickr_push_subscriptions_get_by_secret_url($secret_url);

	if (! $subscription){
		error_404();
	}

	if ($verify_token = get_str("verify_token")){

		if ($subscription['verify_token'] != $verify_token){
			error_404();
		}

		$mode = get_str('mode');

		if ($mode == 'subscribe'){

			$update = array(
				'verified' => time(),
			);

			$rsp = flickr_push_subscriptions_update($subscription, $update);
		}

		else if ($mode == 'unsubscribe'){

			$rsp = flickr_push_subscriptions_delete($subscription);

			if (! $rsp['ok']){
				error_404();
			}
		}

		else {
			error_404();
		}

		echo get_str("challenge");
		exit();
	}

	# HEY LOOK. THIS IS WHERE YOU PARSE AND STORE PHOTOS.
	# NOTICE HOW WE'RE NOT DOING THAT YET?

	$xml = file_get_contents('php://input');
	$atom = syndication_atom_parse_str($xml);

	#

	$new = 0;

	foreach ($atom->items as $e){

		$photo = array(
			'photo_id' => $e['id'],
			'owner' => $e['flickr']['author_nsid'],
			'ownername' => $e['author'],
			'title' => $e['title'],
			'updated' => $e['updated'],
			'photo_url' => $e['media']['atom_content@url'],
			'thumb_url' => $e['media']['thumbnail@url'],
		);

		$enc_photo = json_encode($photo);
		$new ++;
	}

	$update = array(
		'last_update' => time(),
	);

	$rsp = flickr_push_subscriptions_update($subscription, $update);

	#

	exit();
?>
