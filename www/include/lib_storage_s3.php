<?php

	loadlib('s3');

	########################################################################

	function storage_s3_init(){
		$GLOBALS['_storage_hooks']['file_exists'] = 'storage_s3_file_exists';
		$GLOBALS['_storage_hooks']['get_file'] = 'storage_s3_get_file';
		$GLOBALS['_storage_hooks']['put_file'] = 'storage_s3_put_file';
		$GLOBALS['_storage_hooks']['delete_file'] = 'storage_s3_delete_file';
	}

	########################################################################

	# TO DO: decide whether bucket should be passed around in $more
	# (20130529/straup)

	########################################################################
	
	function storage_s3_file_exists($path, $more=array()) {

		$bucket = storage_s3_bucket();

		$rsp = s3_head($bucket, $path);
		return $rsp;
	}

	########################################################################

	function storage_s3_get_file($path, $more=array()){

		$bucket = storage_s3_bucket();

		$rsp = s3_get($bucket, $path);

		if (! $rsp['ok']){
			return $rsp;
		}

		$path = "php://memory";

		$fh = fopen($path, 'wb');
		fwrite($fh, $rsp['body']);
		fseek($fh, 0);

		$rsp['fh'] = $fh;
		$rsp['content-length'] = $rsp['headers']['content-length'];

		return $rsp;
	}

	########################################################################
		
	# TO DO: Check to see if this will create nested directories like mkdir -p
	# and if not then write that code. Boring... (20130627/straup)
	
	function storage_s3_put_file($path, $bytes, $more=array()) {

		$defaults = array(
			'acl' => 'public-read',
		);

		$more = array_merge($defaults, $more);

		if (isset($more['type'])){
			$type = $more['type'];
		}

		else {
			loadlib('mime_type');
			$type = mime_type_identify($path);
		}
		
		$meta = array(
			'date-synced' => time(),
                );

		$put_args = array(
			'id' => $path,
			'acl' => $more['acl'],
			'content_type' => $type,
			'data' => $bytes,
			'meta' => $meta,
		);

		$rsp = s3_put(storage_s3_bucket(), $put_args, $more);
		return $rsp;
	}

	########################################################################
	
	function storage_s3_delete_file($path, $more=array()){

		$bucket = storage_s3_bucket();

		$rsp = s3_delete($bucket, $path);
		return $rsp;
	}

	########################################################################

	function storage_s3_bucket(){

		return array(
			'id' => $GLOBALS['cfg']['amazon_s3_bucket_name'],
			'key' => $GLOBALS['cfg']['amazon_s3_access_key'],
			'secret' => $GLOBALS['cfg']['amazon_s3_secret_key'],
		);
	}
	
	########################################################################

	# the end
