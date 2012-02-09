<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	include("include/init.php");
	loadlib("flickr_photos_upload");

	# THIS IS SO NOT FINISHED (20120209/straup)
	error_disabled();

	# https://code.google.com/p/php-mime-mail-parser/
	loadpear("MimeMailParser");

	$parser = new MimeMailParser();  
	$parser->setStream(STDIN);  
  
	$to = $parser->getHeader('to');  
	
	# TO DO: WRITE ME

	$user = users_get_by_magic_email($to);

	# $subject = $parser->getHeader('subject');  

	$attachments = $parser->getAttachments();

	if (! count($attachments)){
		echo "no attachments";
		exit();
	}

	$uploads = array();
	$tmpdir = sys_get_temp_dir();

	foreach ($attachments as $file){

		# TO DO: check mime type

		$filename = $file->filename;
		$path = "{$tmpdir}/{$filename}";

		$fh = fopen($path, "w");

		if (! $fh){
			echo "failed to open {$path}";
			continue;
		}

		# TO DO: check buffer size

		while ($bytes = $attachment->read()){
			fwrite($fh, $bytes);
		}

		fclose($fh);

		$uploads[] = $path;
	}

	foreach ($uploads as $path){

		$args = array();

		$rsp = flickr_photos_upload($user, $path, $args);

		# TO DO: check me...
	}

	foreach ($uploads as $path){
		unlink($path);
	}

	exit();
?>
