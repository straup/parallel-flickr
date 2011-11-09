<?php

	include("include/init.php");
	loadlib("flickr_api");
	loadlib("flickr_users");

	if (! $GLOBALS['cfg']['user']['id']){
		error_404();
	}

	$flickr_user = flickr_users_get_by_user_id($GLOBALS['cfg']['user']['id']);

	$method = 'flickr.activity.recentActivity';

	$args = array(
		'auth_token' => $flickr_user['auth_token'],
	);

	$rsp = flickr_api_call($method, $args);

	# dumper($rsp);
	# exit;

	$items = $rsp['rsp']['items']['item'];

	foreach ($items as $item){

		$subject = $item['type'];
		$title = $item['title']['_content'];
		$owner = $item['ownername'];

		foreach ($item['activity']['event'] as $event){

			$action = $event['type'];
			$actor = $event['username'];
			$when = date("c", $event['dateadded']);
			dumper($event);

			$e = sprintf("%s %s-ed %s's %s at %s", $actor, $action, $owner, $subject, $when);
			echo "{$e} <br />";

			if ($action == 'comment'){
				echo "<q>{$event['_content']}</q><br />";
			}
		}
	}

	$GLOBALS['smarty']->display("page_recent_activity.txt");
	exit();
?>
