<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	#

	include("include/init.php");
	loadlib("flickr_api");

	$method = "flickr.auth.getFrob";

	$args = array();

	$more = array(
		'sign' => 1,
	);

	$rsp = flickr_api_call($method, $args, $more);

	if (! $rsp['ok']){
		dumper($rsp);
		exit();
	}

	$perms = "delete";
	$extra = null;

	$frob = $rsp['rsp']['frob']['_content'];

	$url = flickr_api_auth_url($perms, $extra, $frob);
	echo $url . "\n";

	$stdin = fopen('php://stdin', 'r');

	$ok = 0;

	while (1){

		echo "can has auth: ";

		$line = fgets($stdin);
		$line = trim($line);

 		if (preg_match("/^y/i", $line)){
			$ok = 1;
			break;
		}

 		if (preg_match("/^n/i", $line)){
			break;
		}

	}

	if (! $ok){
		echo "okay, quitting\n";
		exit();
	}

	$method = "flickr.auth.getToken";

	$args = array(
		'frob' => $frob,
	);

	$more = array(
		'sign' => 1,
	);

	$rsp = flickr_api_call($method, $args, $more);

	if (! $rsp['ok']){
		dumper($rsp);
		exit();
	}

	$token = $rsp['rsp']['auth']['token']['_content'];

	echo "new token is {$token}\n";
	exit();

?>
