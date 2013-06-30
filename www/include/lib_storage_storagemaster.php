<?php

	#################################################################

	function storage_storagemaster_init(){
		$GLOBALS['_storage_hooks']['file_exists'] = 'storage_storagemaster_file_exists';
		$GLOBALS['_storage_hooks']['get_file'] = 'storage_storagemaster_get_file';
		$GLOBALS['_storage_hooks']['put_file'] = 'storage_storagemaster_put_file';
		$GLOBALS['_storage_hooks']['delete_file'] = 'storage_storagemaster_delete_file';
	}

	#################################################################

	function storage_storagemaster_file_exists($path, $more=array()){

		$rsp = storage_storagemaster_connect();

		if (! $rsp['ok']){
			return $rsp;
		}

		$socket = $rsp['socket'];

		list($msg, $len) = storage_storagemaster_message("EXISTS", $path);

		socket_write($socket, $msg, $len);

		$rsp = storage_storagemaster_status($socket);

		if (! $rsp['ok']){
			return $rsp;
		}

		$out = socket_read($socket, 1024);
		socket_close($socket);

		$rsp['body'] = $out;
		return $rsp;
	}

	#################################################################

	function storage_storagemaster_get_file($path, $more=array()){

		$rsp = storage_storagemaster_connect();

		if (! $rsp['ok']){
			return $rsp;
		}

		$socket = $rsp['socket'];

		list($msg, $len) = storage_storagemaster_message("GET", $path);

		socket_write($socket, $msg, $len);

		$rsp = storage_storagemaster_status($socket);

		if (! $rsp['ok']){
			return $rsp;
		}

		$data = '';

		while ($bytes = socket_read($socket, 1024)){
			$data .= $bytes;
		}

		socket_close($socket);

		$path = "php://memory";
		$fh = fopen($path, 'w');

		fwrite($fh, $data);
		fseek($fh, 0);

		$rsp = array(
			'ok' => 1,
			'fh' => $fh,
		);

		return $rsp;
	}

	#################################################################

	function storage_storagemaster_put_file($path, $bytes, $more=array()){

		$rsp = storage_storagemaster_connect();

		if (! $rsp['ok']){
			return $rsp;
		}

		$socket = $rsp['socket'];

		list($msg, $len) = storage_storagemaster_message("PUT", $path, $bytes);

		socket_write($socket, $msg, $len);

		$rsp = storage_storagemaster_status($socket);

		if (! $rsp['ok']){
			return $rsp;
		}

		$out = socket_read($socket, 1024);
		socket_close($socket);

		$rsp['body'] = $out;
		return $rsp;
	}

	#################################################################

	function storage_storagemaster_delete_file($path, $more=array()){

		$rsp = storage_storagemaster_connect();

		if (! $rsp['ok']){
			return $rsp;
		}

		$socket = $rsp['socket'];

		list($msg, $len) = storage_storagemaster_message("DELETE", $path);

		socket_write($socket, $msg, $len);

		$rsp = storage_storagemaster_status($socket);

		if (! $rsp['ok']){
			return $rsp;
		}

		$out = socket_read($socket, 1024);
		socket_close($socket);

		$rsp['body'] = $out;
		return $rsp;
	}

	#################################################################

	function storage_storagemaster_connect(){

		$host = $GLOBALS['cfg']['storage_storagemaster_host'];
		$port = $GLOBALS['cfg']['storage_storagemaster_port'];

	        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	        $ok = socket_connect($socket, $host, $port);

		if (! $ok){
			$err = socket_strerror(socket_last_error($socket));
			return array('ok' => 0, 'error' => $err);
		}

		return array('ok' => 1, 'socket' => $socket);
	}

	#################################################################

	function storage_storagemaster_message($action, $path, $body=null){

		$parts = array($action, $path);

		if ($body){
			$parts[] = strlen($body);
			$parts[] = $body;
		}

		$msg = implode("\C", $parts);

		$len = strlen($msg);
		return array($msg, $len);
	}

	#################################################################

	function storage_storagemaster_status($socket){

		if ($ok = socket_read($socket, 1)){
			return array('ok' => 1);
		}

		$error = socket_read($socket, 1024);
		socket_close($socket);

		return array('ok' => 0, 'error' => $error);
	}

	#################################################################

	# the end
