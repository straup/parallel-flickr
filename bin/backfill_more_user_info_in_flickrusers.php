<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	include("include/init.php");

	loadlib("backfill");
	loadlib("flickr_api");
	loadlib("flickr_users");

	function _get_nsid($row, $more=array()){

        $method = 'flickr.people.getInfo';

        $args = array(
            'user_id' => $row['nsid'],
        );

        $ret = flickr_api_call($method, $args);

        if ($ret['ok']) {

            $rsp = $ret['rsp']['person'];
            $path_alias = $rsp['path_alias'];
            $username = $rsp['username']['_content'];

            print "$path_alias -- $username\n";

            $user = array('user_id' => $row['user_id']);
            $update = array(
                'path_alias' => $path_alias,
                'username' => $username,
            );
            flickr_users_update_user($user, $update);
        }
	}

	$sql = "SELECT * FROM FlickrUsers";
	backfill_db_users($sql, '_get_nsid');

	exit();
?>
