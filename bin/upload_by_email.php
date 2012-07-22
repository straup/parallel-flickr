<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	include("include/init.php");

	loadlib("flickr_photos_upload");
	loadlib("flickr_users");
	loadlib("flickr_backups");

	# https://code.google.com/p/php-mime-mail-parser/
	loadpear("MimeMailParser");

	if (! $GLOBALS['cfg']['enable_feature_uploads']){
		log_rawr("uploads are disabled");
	}

	if (! $GLOBALS['cfg']['enable_feature_uploads_by_email']){
		log_rawr("uploads by email are disabled");
	}

	# sudo make me less bad (also include hostname)
	$re = "/([a-zA-Z0-9\.]+)@/";

	$parser = new MimeMailParser();  
	$parser->setStream(STDIN);  

	$to = $parser->getHeader('to');  

	if (! preg_match($re, $to, $m)){
		log_rawr("failed to parse upload by email address");
	}

	$addr = $m[1];

	$user = users_get_by_uploadbyemail_address($addr);

	if (! $user){
		log_rawr("invalid magic email address");
	}

	# TO DO: in some magic future pony world we should allow
	# users to restrict uploads to one or more 'From' addresses
	# (20120209/straup)

	# strictly speaking both of these should always be true
	# because they are checked when a user creates their
	# upload by email address but measure twice and all that
	# (20120209/straup)

	if (! flickr_backups_is_registered_user($user)){
		log_rawr("not a registered backup user");
	}

	$flickr_user = flickr_users_get_by_user_id($user['id']);

	if (! flickr_users_has_token_perms($flickr_user, "write")){
		log_rawr("user has insufficient token perms");
	}

	$attachments = $parser->getAttachments();

	if (! count($attachments)){
		log_rawr("no attachments");
	}

	$subject = $parser->getHeader('subject');  
	$filtr = null;

	if (preg_match("/(\s?f:([a-z]+))/i", $subject, $m)){
		$filtr = $m[2];
		$subject = str_replace($m[1], "", $subject);
	}

	$uploads = array();

	$tmpdir = sys_get_temp_dir();
	$pid = getmypid();

	$max_bytes = $GLOBALS['cfg']['uploads_by_email_maxbytes'];
	$bytes_read = 0;

	foreach ($attachments as $att){

		$type = $att->content_type;

		# Flickr goes to a lot of trouble to pull images out
		# of HTML forms and remote services. parallel-flickr
		# does not. (20120209/straup)

		if (! preg_match("/^image\//", $type)){
			continue;
		}

		$filename = $att->filename;
		$path = "{$tmpdir}/{$pid}-{$filename}";

		$fh = fopen($path, "w");

		if (! $fh){
			echo "failed to open {$path}";
			continue;
		}

		while ($bytes = $att->read()){

			$bytes_read += strlen($bytes);

			if (($max_bytes) && ($bytes_read >= $max_bytes)){

				foreach ($uploads as $path){
					unlink($path);
				}

				log_rawr("upload by email exceeded max bytes ({$max_bytes})");
			}

			fwrite($fh, $bytes);
		}

		fclose($fh);

		$uploads[] = $path;
	}

	if (! count($uploads)){
		log_rawr("no valid uploads");
	}

	foreach ($uploads as $path){

		$args = array(
			'http_timeout' => 60
		);

		if (($filtr) && features_is_enabled("uploads_filtr")){
			$args['filtr'] = $filtr;
		}

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
