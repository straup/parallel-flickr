<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	#

	include("include/init.php");
	loadlib("flickr_faves_import");

	$nsid = '35034348999@N01';
	$rsp = flickr_faves_import_faves($nsid, 3);

	exit();
?>
