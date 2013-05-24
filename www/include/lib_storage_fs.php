<?php

	$GLOBALS['_storage_hooks']['get_file'] = 'storage_fs_get_file';
	$GLOBALS['_storage_hooks']['put_file'] = 'storage_fs_put_file';
	$GLOBALS['_storage_hooks']['delete_file'] = 'storage_fs_put_file';

	#################################################################

	function storage_fs_get_file($path, $more=array()){

		$uri = storage_fs_path_to_uri($path);

		if (! file_exists($uri)){
			return array('ok' => 0, 'error' => 'File not found');
		}

		# TO DO: return what exactly? filehandle or ... ?

		return array(
			'ok' => 1
		);
	}

	#################################################################

	function storage_fs_put_file($path, $bytes, $more=array()){

		$uri = storage_fs_path_to_uri($path);

		$root = dirname($uri);

		if (! file_exists($root)){

			# TO DO: perms

			if (! mkdir($root, 0755, true)){
				return array('ok' => 0, 'error' => 'Failed to make directory');
			}
		}

		$fh = fopen($uri, 'w');

		if (! $fh){
			return array('ok' => 0, 'error' => 'Failed to create file');
		}

		fwrite($fh, $bytes);
		fclose($fh);

		return array('ok' => 1);
	}

	#################################################################

	function storage_fs_delete_file($path, $more=array()){

		# sudo make me not shit 
		# $path = str_replace("../", "", $path);

		$uri = storage_fs_path_to_uri($path);

		if (! unlink($uri)){
			return array('ok' => 0, 'error' => 'Failed to delete file');
		}

		return array('ok' => 1);
	}

	#################################################################

	function storage_fs_path_to_uri($path){

		$path = ltrim($path, DIRECTORY_SEPARATOR);

		return $GLOBALS['cfg']['flickr_static_path'] . $path;
	}

	#################################################################

	# the end
