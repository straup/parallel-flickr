<?php

	########################################################################

	# FIX ME... which really means, make sure I work...

	function storage_s3_url_photo($photo, $size='z', $more=array()) {
		$path = storage_s3_utils_oldskool_photo_path($photo, $size, $more);
		return s3_unsigned_object_url(storage_s3_bucket(), $path);
	}

	########################################################################

	function storage_s3_utils_oldskool_photo_prefix(&$photo, $more=array()){

		$prefix = $photo['user_id'];
		
		$dir = join('/', str_split(substr(md5($photo['id']), 0, 8), 2));
		$path = "{$prefix}/photos/$dir/";

		return $path;
	}

	########################################################################

	function storage_s3_utils_oldskool_photo_path($photo, $size='z', $more=array()) {

		$photo_prefix = storage_s3_utils_oldskool_photo_prefix($photo, $more);
		
		if ($size == 'o'){

			if ($photo['originalsecret']){
				return "{$photo_prefix}{$photo['id']}_{$photo['originalsecret']}_o.{$photo['originalformat']}";
			}

			else {
				return "{$photo_prefix}{$photo['id']}_{$photo['secret']}_b.jpg";
			}
		}
		
		# else "small"
		
		return "{$photo_prefix}{$photo['id']}_{$photo['secret']}_z.jpg";
	}

	########################################################################

	# the end
