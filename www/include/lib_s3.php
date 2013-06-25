<?php

	loadlib("http");

	########################################################################

	function s3_get_bucket_url($bucket){

		$url = "http://{$bucket['id']}.s3.amazonaws.com/";
		return $url;
	}

	########################################################################
	
	function s3_get_bucket_exists($bucket) {
	   
		$url = s3_signed_object_url($bucket, '');
		$rsp = http_get($url);
		
		return $rsp;
		
	}


	# list contents of a bucket
	#   params support added for marker and prefix
	#
	
	function s3_get_bucket($bucket, $more) {
		$url = s3_signed_object_url($bucket, '', array('params' => $more));
		$rsp = http_get($url);
		return $rsp;
	}

	########################################################################

	# FIX ME: allow for optionally signed requests, etc.

	function s3_get($bucket, $object_id, $args=array()){

		$query = array();

		# Note: it is your responsibility to urlencode parameters
		# because AWS is too fussy to accept things like acl=1 so
		# we can't use http_build_query (20120716/straup)

		if (isset($args['acl'])){
			$query[] = urlencode('acl');
		}

		if (count($query)){
			$query = implode("&", $query);
		}

		$date = date('D, d M Y H:i:s T');
		$path = "/{$bucket['id']}/{$object_id}";

		if ($query){
			$path .= "?{$query}";
		}

		$parts = array(
			'GET',
			'',
			'',
			$date,
			$path
		);

		$raw = implode("\n", $parts);

		$sig = s3_sign_auth_string($bucket, $raw);
		$sig = base64_encode($sig);

		$auth = "AWS {$bucket['key']}:{$sig}";

		$headers = array(
			'Date' => $date,
			'Authorization' => $auth,
		);

		$bucket_url = s3_get_bucket_url($bucket);
		$object_url = $bucket_url . $object_id;

		if ($query){
			$object_url .= "?{$query}";
		}

		return http_get($object_url, $headers);
	}
	
	########################################################################

	function s3_get_acl($bucket, $object_id){

		$args = array(
			'acl' => 1
		);

		$rsp = s3_get($bucket, $object_id, $args);

		if (! $rsp['ok']){
			return $rsp;
		}

		# I mean this works but still it makes me want to
		# be sad... (20120716/straup)

		$xml = new SimpleXMLElement($rsp['body']);
		$json = json_encode($xml);
		$json = json_decode($json, 'as hash');

		return okay(array(
			'acl' => $json
		));

	}

	########################################################################

	function s3_put($bucket, $args){

		$defaults = array(
			'acl' => 'private',
		);

		$args = array_merge($defaults, $args);

		# TO DO: account for PUT-ing of a file and
		# not just bits (aka $args['data'])

		$bytes_hashed = md5($args['data'], true);
		$bytes_enc = base64_encode($bytes_hashed);

		$date = date('D, d M Y H:i:s T');
		$path = "/{$bucket['id']}/{$args['id']}";

		$parts = array();

		$parts[] = 'PUT';
		$parts[] = $bytes_enc;
		$parts[] = $args['content_type'];
		$parts[] = $date;
		$parts[] = "x-amz-acl:{$args['acl']}";
		
		if ($args['meta']){

			ksort($args['meta']);

			foreach ($args['meta'] as $k => $v){
				$parts[] = "x-amz-meta-$k:$v";
			}
		}
		
		$parts[] = $path;
		
		$raw = implode("\n", $parts);

		$sig = s3_sign_auth_string($bucket, $raw);
		$sig = base64_encode($sig);

		$auth = "AWS {$bucket['key']}:{$sig}";

		$headers = array(
			'Date' => $date,
			'X-Amz-Acl' => $args['acl'],
			'Content-Type' => $args['content_type'],
			'Content-MD5' => $bytes_enc,
			'Content-Length' => strlen($args['data']),
			'Authorization' => $auth,
		);

		if ($args['meta']){
			foreach ($args['meta'] as $k => $v){
				$headers["X-Amz-Meta-$k"] = $v;
			}
		}
		
		# See this? It's important. AWS freaks out at the mere presence
		# of the 'Transfer-Encoding' header. Thanks, Roy...

		$more = array(
			'donotsend_transfer_encoding' => 1,
		);

		if ($args['http_timeout']){
			$more['http_timeout'] = $args['http_timeout'];
		}

		$bucket_url = s3_get_bucket_url($bucket);

		# enurl-ify ?
		$object_url = $bucket_url . $args['id'];

		$rsp = http_put($object_url, $args['data'], $headers, $more);
		return $rsp;
	}

	########################################################################

	function s3_delete($bucket, $object_id){

		$date = date('D, d M Y H:i:s T');
		$path = "/{$bucket['id']}/{$object_id}";

		$parts = array(
			"DELETE",
			'',
			'text/plain',
			$date,
			$path
		);

		$raw = implode("\n", $parts);

		$sig = s3_sign_auth_string($bucket, $raw);
		$sig = base64_encode($sig);

		$auth = "AWS {$bucket['key']}:{$sig}";

		$headers = array(
			'Date' => $date,
			'Authorization' => $auth,
			'Content-Type' => 'text/plain',
			'Content-Length' => 0
		);

		# See this? It's important. AWS freaks out at the mere presence
		# of the 'Transfer-Encoding' header. Thanks, Roy...

		$more = array(
			'donotsend_transfer_encoding' => 1,
		);

		$bucket_url = s3_get_bucket_url($bucket);
		$object_url = $bucket_url . $object_id;

		$rsp = http_delete($object_url, '', $headers, $more);
		return $rsp;
	}

	########################################################################

	function s3_rename($bucket, $old_object_id, $new_object_id, $args=array()){

		$rsp = array(
			'ok' => 0,
			'get' => null,
			'put' => null,
			'delete' => null,
			'old_object_id' => $old_object_id,
			'new_object_id' => $new_object_id,
		);

		$get_rsp = s3_get($bucket, $old_object_id);
		$rsp['get'] = $get_rsp;

		if (! $get_rsp['ok']){
			return $rsp;
		}

		# FIX ME: get ACL (if not specified in $args)

		$put_args = array(
			'id' => $new_object_id,
			'content_type' => $get_rsp['headers']['content_type'],
			'data' => $get_rsp['body'],
		);

		# note the order of precedence
		$put_args = array_merge($args, $put_args);

		$put_rsp = s3_put($bucket, $put_args);
		$rsp['put'] = $put_rsp;

		if (! $put_rsp['ok']){
			return $rsp;
		}

		$del_rsp = s3_delete($bucket, $old_object_id);
		$rsp['delete'] = $del_rsp;

		if (! $del_rsp['ok']){
			return $rsp;
		}

		$rsp['ok'] = 1;
		return $rsp;
	}

	########################################################################

	# see also: https://doc.s3.amazonaws.com/proposals/post.html

	function s3_signed_post_params($bucket, $args=array()){

		$defaults = array(
			'expires' => time() + 300,
			'acl' => 'private',
			'dirname' => '',
			'filename' => "\${filename}",
		);

		$args = array_merge($defaults, $args);

		if ($args['dirname']){
			$args['dirname'] = ltrim($args['dirname'], '/');
		}

		$key = $args['dirname'] . $args['filename'];

		$conditions = array(
			array('bucket' => $bucket['id']),
			array('acl' => $args['acl']),
			array('starts-with', '$key', $args['dirname']),
			array('redirect' => $args['redirect'])
		);

		if (isset($args['content_type'])){
			$conditions[] = array('starts-with', '$Content-Type', $args['content_type']);
		}

		if (is_array($args['amz_headers'])){

			foreach ($args['amz_headers'] as $k => $v){
				$conditions[] = array( "x-amz-meta-{$k}" => $v );
			}
		}

		$ymd = gmdate('Y-m-d', $args['expires']);
		$hmd = gmdate('H:i:s', $args['expires']);

		$policy = array(
			'expiration' => "{$ymd}T{$hmd}Z",
			'conditions' => $conditions,
		);

		$policy = json_encode($policy);
		$policy = base64_encode($policy);

		$sig = s3_sign_auth_string($bucket, $policy);
		$sig = base64_encode($sig);

		$params = array(
			'policy' => $policy,
			'signature' => $sig,
			'acl' => $args['acl'],
			'key' => $key,
			'redirect' => $args['redirect'],
			'AWSAccessKeyId' => $bucket['key'],
		);

		if (isset($args['content_type'])){
			$params['content-type'] = $args['content_type'];
		}

		if (is_array($args['amz_headers'])){

			foreach ($args['amz_headers'] as $k => $v){
				$params["x-amz-meta-{$k}"] = $v;
			}
		}

		return $params;
	}

	########################################################################

	function s3_verify_etag($bucket, $object_id, $etag){

		$more = array(
			'expires' => time() + 300,
			'method' => 'HEAD',
		);
		
		$rsp = s3_head($bucket, $object_id, $more);
		
		if (! $rsp['ok']){
			return $rsp;
		}

		$ok = ($rsp['headers']['etag'] == $etag) ? 1 : 0;

		return array(
			'ok' => $ok,
		);
	}

	########################################################################

	function s3_head($bucket, $object_id, $args=array()) {
		
		$defaults = array(
			'expires' => time() + 300,
			'method' => 'HEAD',
		);
		
		$args = array_merge($defaults, $args);
	
		$url = s3_signed_object_url($bucket, $object_id, $args);

		$rsp = http_head($url);

		return $rsp;
	}
	
	########################################################################
		
	function s3_unsigned_object_url($bucket, $object_id){

		$bucket_url = s3_get_bucket_url($bucket);
		$object_id = s3_enurlify_object_id($object_id);

		$object_url = $bucket_url . $object_id;
		return $object_url;
	}

	########################################################################

	function s3_signed_object_url($bucket, $id, $more=array()){

		$defaults = array(
			'method' => 'GET',
			'expires' => time() + 300,
		);

		$args = array_merge($defaults, $more);

		$id = s3_enurlify_object_id($id);
		$path = "/{$bucket['id']}/{$id}";

		$parts = array(
			$args['method'],
			null,
			null,
			$args['expires'],
			$path,
		);

		$raw = implode("\n", $parts);

		$sig = s3_sign_auth_string($bucket, $raw);
		$sig = base64_encode($sig);

		$query = array(
			'Signature' => $sig,
			'AWSAccessKeyId' => $bucket['key'],
			'Expires' => $args['expires'],
		);

		if ($args['params']) {
		    $query = array_merge($query, $args['params']);
		}

		$query = http_build_query($query);

		$url = s3_unsigned_object_url($bucket, $id);

		return $url . "?" . $query;
	}

	########################################################################

	function s3_sign_auth_string(&$bucket, $raw){

		return hash_hmac('sha1', $raw, $bucket['secret'], true);
	}

	########################################################################

	function s3_enurlify_object_id($object_id){

		$object_id = rawurlencode($object_id);
		$object_id = str_replace('%2F', '/', $object_id);
		$object_id = str_replace('+', '%20', $object_id);
		return $object_id;
	}

	########################################################################

	# the end
