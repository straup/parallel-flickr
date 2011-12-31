<?php
	
	loadlib('s3');
	
	function storage_s3_url_photo($photo, $size='z', $more=array()) {
		$photo_prefix = storage_s3_path_photo($photo, $more);
		
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
	
	function storage_s3_path_photo($photo, $more=array()) {
		$ownername = $photo['ownername'];
        $dir = join('/', str_split(substr(md5($photo['id']), 0, 8), 2));
        $path = "{$ownername}/photos/$dir/";
        return $path;
	}
	
	
	function storage_s3_file_store($object_id, $data, $more=array()) {
		if ($more['type']) {
			$type = $more['type'];
		} else {
			loadlib('mime_type');
			$type = mime_type_identify($object_id);
		}
		
		$put = s3_put($GLOBALS['cfg']['remote_s3_bucket'],
            array(
				'id' => $id,
				'acl' => 'public-read',
				'content_type' => $type,
				'data' => $rsp['body'],
				'meta' => array(
					'date-synced' => time(),
                )
		));
	
		return $put;
	}
	
	function storage_s3_file_exists($object_id, $more=array()) {
		$rsp = s3_head($GLOBALS['cfg']['remote_s3_bucket'], $object_id);
	
		if ($rsp['ok']) {
			return 1;
		} else {
			return 0;
		}
	}
	
	