<?php

	loadlib("flickr_photos_upload");

	#################################################################

	function api_flickr_photos_upload(){

		if (! $GLOBALS['cfg']['enable_feature_uploads']){
			api_output_error(999, "uploads are disabled");
		}

		if (! $_FILES['photo']){
			api_output_error(999, "missing photo");
		}

		if ($_FILES['photo']['error']){
			api_output_error(999, "server error: {$_FILES['photo']['error']}");
		}

		$file = $_FILES['photo']['tmp_name'];

		# FIX ME: pull in title, etc.

		$args = array();

		$rsp = flickr_photos_upload($GLOBALS['cfg']['user'], $file, $args);

		unlink($file);

		if (! $rsp['ok']){
			api_output_error(999, $rsp['error']);
		}

		api_output_ok($rsp);
		exit();
	}

	#################################################################

?>
