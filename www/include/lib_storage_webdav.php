<?php

	$GLOBALS['_storage_hooks']['get_file'] = 'storage_webdav_get_file';
	$GLOBALS['_storage_hooks']['put_file'] = 'storage_webdav_put_file';
	$GLOBALS['_storage_hooks']['delete_file'] = '';

	#################################################################

	loadlib("http");
	loadlib("http_webdav");

	#################################################################

	function storage_webdav_get_file($path, $more=array()){

		$uri = storage_webdav_path_to_url($path);

		return $http_get($uri);
	}

	#################################################################

	function storage_webdav_put_file($path, $bytes, $more=array()){

		$root = dirname($path);
		$tree = explode(FIXME_SEPARATOR, $root);

		$foo = array();

		foreach ($tree as $leaf){
			$foo[] = $leaf;
			
			$bar = implode(FIXME_SEPARATOR, $foo);
			$uri = storage_webdav_path_to_url($bar);

			$rsp = http_head($rsp);

			# TO DO: check HTTP status

			if ($rsp['ok']){
				continue;
			}

			$rsp = http_mkcol($uri);

			if (! $rsp['ok']){
				return $rsp;
			}
		}

		$uri = storage_webdav_path_to_url($path);
		
		$rsp = http_put($uri, $bytes);
		return $rsp;
	}

	#################################################################

	function storage_webdav_path_to_uri($path){

		$path = ltrim($path, "/");

		return $GLOBALS['cfg']['storage_webdav_abs_root_uri'] . $path;
	}

	#################################################################

	# the end
