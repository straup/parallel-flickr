<?php
	
	loadlib('s3');
	
	function storage_s3_url_photo($photo, $size='z', $more=array()) {
		$path = storage_s3_path_photo($photo, $size, $more);
		return s3_unsigned_object_url(storage_s3_bucket(), $path);
	}
	
	function storage_s3_path_photo($photo, $size='z', $more=array()) {
		$photo_prefix = storage_s3_prefix_photo($photo, $more);
		
		if ($size == 'o') {			
			if ($photo['originalsecret']) {
				return "{$photo_prefix}{$photo['id']}_{$photo['originalsecret']}_o.{$photo['originalformat']}";
			} else {
				return "{$photo_prefix}{$photo['id']}_{$photo['secret']}_b.jpg";
			}
		}
		
		# else "small"
		
		return "{$photo_prefix}{$photo['id']}_{$photo['secret']}_z.jpg";
	}
	
	function storage_s3_prefix_photo($photo, $more=array()) {
		$prefix = $photo['user_id'];
		
		$dir = join('/', str_split(substr(md5($photo['id']), 0, 8), 2));
		$path = "{$prefix}/photos/$dir/";
		return $path;
	}
	
	
	function storage_s3_file_store($object_id, $data, $more=array()) {
		if ($more['type']) {
			$type = $more['type'];
		} else {
			loadlib('mime_type');
			$type = mime_type_identify($object_id);
		}
		
	
		$put = s3_put(storage_s3_bucket(),
            array(
				'id' => $object_id,
				'acl' => 'public-read',
				'content_type' => $type,
				'data' => $data,
				'meta' => array(
					'date-synced' => time(),
                )
		));
	
		return $put;
	}
	
	function storage_s3_file_exists($object_id, $more=array()) {
		$rsp = s3_head(storage_s3_bucket(), $object_id);
	
		if ($rsp['ok']) {
			log_debug('s3', "exists: $object_id");
			return 1;
		} else {
			return 0;
		}
	}
	
	function storage_s3_bucket() {
		return array(
			'id' => $GLOBALS['cfg']['amazon_s3_bucket_name'],
			'key' => $GLOBALS['cfg']['amazon_s3_access_key'],
			'secret' => $GLOBALS['cfg']['amazon_s3_secret_key'],
		);
	}
	
	
