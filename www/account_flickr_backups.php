<?php

	include("include/init.php");

	login_ensure_loggedin();

	loadlib("flickr_backups");
	loadlib("invite_codes");

	$backups = flickr_backups_for_user($GLOBALS['cfg']['user']);
	$registered = (count($backups)) ? 1 : 0;

	if ((! $registered) && (! $GLOBALS['cfg']['backups_enable_registrations'])){
		error_disabled();
	}
	
	if ($GLOBALS['cfg']['enable_feature_invite_codes']){

		if ((! $registered) && (! invite_codes_get_by_cookie())){

			$cookie = login_get_cookie('invite');

			if (! $cookie){

				header("location: /invite/?redir=" . urlencode("account/flickr/backups"));
				exit();
			}
		}
	}

	features_ensure_enabled("backups");

	$map = flickr_backups_type_map('string keys');
	$GLOBALS['smarty']->assign_by_ref("map", $map);

	$crumb_key = 'backups';
	$smarty->assign("crumb_key", $crumb_key);

	$crumb_ok = crumb_check($crumb_key);

	if ($crumb_ok){

		if (post_str("setup")){

			$created = array();
			$details = array();

			foreach ($map as $ignore => $type_id){

				$rsp = flickr_backups_create($GLOBALS['cfg']['user'], $type_id);
				$created[$type_id] = (($rsp['ok']) || ($rsp['error_code'] == 1062)) ? 1 : 0;

				$rsp['type_id'] = $type_id;
				$details[] = $rsp;
			}

			$GLOBALS['smarty']->assign_by_ref("created", $created);
			$GLOBALS['smarty']->assign_by_ref("created_details", $details);

			$backups = flickr_backups_for_user($GLOBALS['cfg']['user']);
		}

		else if ($type = post_str("type")){

			if ((isset($map[$type])) && (isset($backups[$type]))){
			
				$backup = $backups[$type];
				$action = post_str("action");
				$context = post_str("context");

				if ($context=="push"){

					$enabled = ($action == 'start') ? 1 : 0;
					$rsp = flickr_backups_toggle_push_subscription($backup, $enabled);
				}

				else {
					$disabled = ($action == 'stop') ? 1 : 0;
					$update = array('disabled' => $disabled);

					$rsp = flickr_backups_update($backup, $update);
				}

				if ($rsp['ok']){
					$backups = flickr_backups_for_user($GLOBALS['cfg']['user']);
				}

				$rsp['action'] = $action;
				$rsp['context'] = $context;
				$GLOBALS['smarty']->assign_by_ref("update_rsp", $rsp);
			}
		}

		else {}

	}

	$GLOBALS['smarty']->assign_by_ref("backups", $backups);

	$GLOBALS['smarty']->display("page_account_flickr_backups.txt");
	exit();
?>
