<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	#

	include("include/init.php");
	loadlib("dbtickets_flickr");

	$rsp = dbtickets_flickr_create();
	dumper($rsp);
?>
