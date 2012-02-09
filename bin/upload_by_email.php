<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	include("include/init.php");
	loadlib("flickr_photos_upload");
	loadlib("flickr_users");
	loadlib("flickr_backups");

	# THIS IS SO NOT FINISHED (20120209/straup)

	# https://code.google.com/p/php-mime-mail-parser/
	loadpear("MimeMailParser");

	if (! $GLOBALS['cfg']['enable_feature_uploads']){
		log_fatal("uploads are disabled");
	}

	if (! $GLOBALS['cfg']['enable_feature_uploads_by_email']){
		log_fatal("uploads by email are disabled");
	}

	$parser = new MimeMailParser();  
	$parser->setStream(STDIN);  
  
	$to = $parser->getHeader('to');  

	# TO DO: WRITE ME

	$user = users_get_by_magic_email($to);

	if (! $user){
		log_fatal("invalid magic email address");
	}

	if (! flickr_backups_is_registered_user($user)){
		log_fatal("not a registered backup user");
	}

	$flickr_user = flickr_users_get_by_user_id($user['id']);

	if (! flickr_users_has_token_perms($flickr_user, "write")){
		log_fatal("user has insufficient token perms");
	}

	# $subject = $parser->getHeader('subject');  

	$attachments = $parser->getAttachments();

	if (! count($attachments)){
		log_fatal("no attachments");
	}

	$uploads = array();

	$tmpdir = sys_get_temp_dir();
	$pid = getmypid();

	foreach ($attachments as $att){

		if (! preg_match("/^image\//", $att->content_type)){
			continue;
		}

		$filename = $att->filename;
		$path = "{$tmpdir}/{$pid}-{$filename}";

		$fh = fopen($path, "w");

		if (! $fh){
			echo "failed to open {$path}";
			continue;
		}

		# TO DO: check buffer size

		while ($bytes = $att->read()){
			fwrite($fh, $bytes);
		}

		fclose($fh);

		$uploads[] = $path;
	}

	if (! count($uploads)){
		log_fatal("no valid uploads");
	}

	foreach ($uploads as $path){

		$args = array();

		$rsp = flickr_photos_upload($user, $path, $args);

		# THROW AN ERROR ?

		if (! $rsp['ok']){

			echo "failed to upload '{$path}' : {$rsp['error']}";
			continue;
		}
	}

	foreach ($uploads as $path){
		unlink($path);
	}

	exit();
?>
