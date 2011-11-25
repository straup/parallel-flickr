<?php

	include("include/init.php");
	loadlib("flickr_backups");

	# it is unclear whether this is working correctly
	# (20111125/straup)

	loadlib("invite_codes");

	if ($GLOBALS['cfg']['enable_feature_invite_codes']){

		if (! invite_codes_get_by_cookie()){

			$cookie = login_get_cookie('invite');

			if (! $cookie){

				header("location: /invite/?redir=" . urlencode("/account/backups"));
				exit();
			}
		}
	}

	if (! $GLOBALS['cfg']['enable_feature_backups']){
		error_disabled();
	}

	login_ensure_loggedin("account/backups/");

	$map = flickr_backups_type_map('string keys');
	$GLOBALS['smarty']->assign_by_ref("map", $map);

	$backups = flickr_backups_for_user($GLOBALS['cfg']['user']);

	if ((! count($backups)) && (! $GLOBALS['cfg']['backups_enable_registrations'])){
		error_disabled();
	}

	$crumb_key = 'backups';
	$smarty->assign("crumb_key", $crumb_key);

	$crumb_ok = crumb_check($crumb_key);

	if ($crumb_ok){

		if (post_str("setup")){

			$created = array();

			foreach ($map as $ignore => $type_id){

				$rsp = flickr_backups_create($GLOBALS['cfg']['user'], $type_id);
				$created[$type_id] = (($rsp['ok']) || ($rsp['error_code'] == 1062)) ? 1 : 0;
			}

			$GLOBALS['smarty']->assign_by_ref("created", $created);
			$backups = flickr_backups_for_user($GLOBALS['cfg']['user']);
		}

		else if ($type = post_str("type")){

			if ((isset($map[$type])) && (isset($backups[$type]))){
			
				$backup = $backups[$type];
				$disabled = (post_str('action') == 'stop') ? 1 : 0;
				$update = array('disabled' => $disabled);

				$rsp = flickr_backups_update($backup, $update);

				if ($rsp['ok']){
					$backups = flickr_backups_for_user($GLOBALS['cfg']['user']);
				}
			}
		}

		else {}

	}

	$GLOBALS['smarty']->assign_by_ref("backups", $backups);

	$GLOBALS['smarty']->display("page_account_backups.txt");
	exit();
?>
