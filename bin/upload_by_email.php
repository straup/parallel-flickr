<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	include("include/init.php");
	loadlib("flickr_photos_upload");

	# THIS IS SO NOT FINISHED (20120209/straup)

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
		echo "no valid uploads";
		exit();
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
