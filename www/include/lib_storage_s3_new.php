<?php

	$GLOBALS['_storage_hooks']['file_exists'] = 'storage_s3_file_exists';
	$GLOBALS['_storage_hooks']['get_file'] = 'storage_s3_get_file';
	$GLOBALS['_storage_hooks']['put_file'] = 'storage_s3_put_file';
	$GLOBALS['_storage_hooks']['delete_file'] = '';
	
	loadlib('s3');

	########################################################################
	
	function storage_s3_file_exists($path, $more=array()) {

		$bucket = storage_s3_bucket();

		$rsp = s3_head($bucket, $path);
		return $rsp;
	}

	########################################################################

	# not tested (20130529/straup)

	function storage_s3_get_file($path, $more=array()){

		$bucket = storage_s3_bucket();

		$rsp = s3_get($bucket, $path);
		return $rsp;
	}

	########################################################################
		
	function storage_s3_put_file($path, $bytes, $more=array()) {

		if ($more['type']) {
			$type = $more['type'];
		}

		else {
			loadlib('mime_type');
			$type = mime_type_identify($object_id);
		}
		
		$meta = array(
			'date-synced' => time(),
                );

		$args = array(
			'id' => $path,
			'acl' => 'public-read',
			'content_type' => $type,
			'data' => $bytes,
			'meta' => $meta,
		);

		$put = s3_put(storage_s3_bucket(), $put_args);
	
		return $put;
	}

	########################################################################
	
	function storage_s3_delete_file($path, $more=array()){
		# TO DO: grab code from mirror project (20130529/straup)
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
