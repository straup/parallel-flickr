<?php

	#################################################################

	function storage_utils_path_to_bytes($path){

		$size = filesize($path);
		$fh = fopen($path, "rb");
		$bytes = fread($fh, $size);
		fclose($fh);
		return $bytes;
	}

	#################################################################

	# the end
