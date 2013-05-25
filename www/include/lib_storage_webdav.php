<?php

	$GLOBALS['_storage_hooks']['get_file'] = 'storage_webdav_get_file';
	$GLOBALS['_storage_hooks']['put_file'] = 'storage_webdav_put_file';
	$GLOBALS['_storage_hooks']['delete_file'] = '';

	#################################################################

	loadlib("http");
	loadlib("http_webdav");

	#################################################################

	function storage_webdav_get_file($path, $more=array()){

		$uri = storage_webdav_path_to_uri($path);
		return http_get($uri);
	}

	#################################################################

	function storage_webdav_put_file($path, $bytes, $more=array()){

		$info = pathinfo($path);
		$root = $info['dirname'];

		# sudo make me less shit (20130525/straup)
		$tree = ($root == ".") ? array() : explode("/", $root);

		$leaves = array();

		foreach ($tree as $leaf){

			$leaves[] = $leaf;
			$leaf = implode("/", $leaves);

			$uri = storage_webdav_path_to_uri($leaf);

			$rsp = http_head($uri);

			if (! $rsp['ok']){
				return $rsp;
			}

			if (($rsp['ok']) && ($rsp['info']['http_code'] == 200)){
				continue;
			}

			if ($rsp['info']['http_code'] == 403){
				return array('ok' => 0, 'error' => 'Forbidden');
			}

			$rsp = http_mkcol($uri);

			if (! $rsp['ok']){
				return $rsp;
			}
		}

		$uri = storage_webdav_path_to_uri($path);
		$rsp = http_put($uri, $bytes);

		return $rsp;
	}

	#################################################################

	function storage_webdav_path_to_uri($path){

		return $GLOBALS['cfg']['storage_webdav_abs_root_uri'] . ltrim($path, "/");
	}

	#################################################################

	# the end
