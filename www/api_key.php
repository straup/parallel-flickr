<?php

	include("include/init.php");

	features_ensure_enabled("api");

	login_ensure_loggedin();

	loadlib("api_keys");
	loadlib("api_keys_utils");
	loadlib("api_oauth2_access_tokens");

	$key_more = array(
		'allow_disabled' => 1,
	);

	$key_row = api_keys_utils_get_from_url($key_more);

	$crumb_key = 'this_api_key';
	$GLOBALS['smarty']->assign("crumb_key", $crumb_key);

	$token_count = api_oauth2_access_tokens_count_for_key($key_row);
	$GLOBALS['smarty']->assign("token_count", $token_count);

	if (post_isset('delete') && crumb_check($crumb_key) && (! $key_row['disabled'])){

		$conf = post_str("confirm");

		if ($conf){

			$rsp = api_keys_delete($key_row);
			$GLOBALS['smarty']->assign_by_ref("delete_rsp", $rsp);
		}		

		$GLOBALS['smarty']->assign_by_ref("key", $key_row);

		$GLOBALS['smarty']->display("page_api_key_delete.txt");
		exit();
	}

	else if (post_isset('done') && crumb_check($crumb_key) && (! $key_row['disabled'])){

		$ok = 1;
		$update = array();

		$title = filter_strict(post_str("title"));
		$description = filter_strict(post_str("description"));
		$callback = filter_strict(post_str("callback"));

		if (($ok) && (! $title)){
			$GLOBALS['smarty']->assign("error", "no_title");
			$ok = 0;
		}

		if (($ok) && (! $description)){
			$GLOBALS['smarty']->assign("error", "no_description");
			$ok = 0;
		}

		if (($ok) && ($callback)){

			if (! api_keys_utils_is_valid_callback($callback)){
				$GLOBALS['smarty']->assign("error", "invalid_callback");
				$ok = 0;
			}
		}

		if ($ok){

			if ($title != $key_row['app_title']){
				$update['app_title'] = $title;
			}

			if ($description != $key_row['app_description']){
				$update['app_description'] = $description;
			}

			if ($callback != $key_row['app_callback']){
				$update['app_callback'] = $callback;
			}

			if (count($update)){

				$GLOBALS['smarty']->assign("do_update", 1);

				$rsp = api_keys_update($key_row, $update);

				if ($rsp['ok']){
					$GLOBALS['smarty']->assign("update_ok", 1);
					$key_row = $rsp['key'];
				}

				else {
					$GLOBALS['smarty']->assign("update_ok", 0);
					$GLOBALS['smarty']->assign("error", $rsp['error']);
				}
			}
		}
		
	}

	else {}

	$GLOBALS['smarty']->assign_by_ref("key", $key_row);

	if ($token_row = api_oauth2_access_tokens_get_for_user_and_key($GLOBALS['cfg']['user'], $key_row)){
		$GLOBALS['smarty']->assign_by_ref("self_token", $token_row);
		$GLOBALS['smarty']->assign("has_self_token", 1);
	}

	# TO DO: get API keys stats, etc.

	$GLOBALS['smarty']->display("page_api_key.txt");
	exit();

?>
