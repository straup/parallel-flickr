<?

	$GLOBALS['timings']['http_count']	= 0;
	$GLOBALS['timings']['http_time']	= 0;
	$GLOBALS['timing_keys']['http'] = 'HTTP Requests';

	########################################################################

	function http_head($url, $headers=array(), $more=array()){

		$ch = _http_curl_handle($url, $headers, $more);

		# ensure NOBODY is set so that headers are returned

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

		if ($more['return_curl_handle']){
			return $ch;
		}

		return _http_request($ch, $url, $more);
	}

	########################################################################

	function http_get($url, $headers=array(), $more=array()){

		$ch = _http_curl_handle($url, $headers, $more);

		if ($more['return_curl_handle']){
			return $ch;
		}

		return _http_request($ch, $url, $more);
	}

	########################################################################

	function http_post($url, $post_fields, $headers=array(), $more=array()){

		$ch = _http_curl_handle($url, $headers, $more);

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

		if ($more['return_curl_handle']){
			return $ch;
		}

		return _http_request($ch, $url, $more);
	}

	########################################################################

	# uncertain what to think about $post_fields as different servers
	# expect different things (aka params sent as GET/query args)...
	# thanks, Roy (20120601/straup)

	function http_delete($url, $post_fields, $headers=array(), $more=array()){

		$ch = _http_curl_handle($url, $headers, $more);

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

		if ($post_fields){
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
		}

		if ($more['return_curl_handle']){
			return $ch;
		}

		return _http_request($ch, $url, $more);
	}

	########################################################################

	function http_put($url, $bytes, $headers=array(), $more=array()){

		$ch = _http_curl_handle($url, $headers, $more);

		# See the monster you've created, Roy? See???!?!?!!

		if (isset($more['donotsend_transfer_encoding'])){
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		}

		else {
			curl_setopt($ch, CURLOPT_PUT, true);
		}

		curl_setopt($ch, CURLOPT_POSTFIELDS, $bytes);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

		# TODO: sort out PUT-ing files
		# curl_setopt($ch, CURLOPT_INFILE, $bytes);
		# curl_setopt($ch, CURLOPT_INFILESIZE, strlen($bytes));

		if ($more['return_curl_handle']){
			return $ch;
		}

		return _http_request($ch, $url, $more);
	}

	########################################################################

	function http_multi(&$requests){

		$handles = array();
		$responses = array();

		foreach ($requests as $req){

			$url = $req['url'];

			$method = (isset($req['method'])) ? strtoupper($req['method']) : 'GET';
			$body = (is_array($req['body'])) ? $req['body'] : null;
			$headers = (is_array($req['headers'])) ? $req['headers'] : array();
			$more = (is_array($req['more'])) ? $req['more'] : array();

			$more['return_curl_handle'] = 1;

			if ($method == 'HEAD'){
				$ch = http_head($url, $headers, $more);
			}

			else if ($method == 'GET'){
				$ch = http_get($url, $headers, $more);
			}

			else if ($method == 'POST'){
				$ch = http_post($url, $body, $headers, $more);
			}

			else if ($method == 'DELETE'){
				$ch = http_delete($url, $body, $headers, $more);
			}

			else if ($method == 'PUT'){
				$ch = http_put($url, $body, $headers, $more);
			}

			else {
				log_warning("http", "unsupported HTTP method : {$method}");
				continue;
			}

			$handles[] = $ch;
		}

		# http://us.php.net/manual/en/function.curl-multi-init.php

		$mh = curl_multi_init();

		foreach ($handles as $ch){
			curl_multi_add_handle($mh, $ch);
		}

		$active = null;
		$start = microtime_ms();

		# this syntax makes my eyes bleed but whatever...
		# (20110822/straup)

		do {
			$mrc = curl_multi_exec($mh, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);

		while ($active && $mrc == CURLM_OK){
			if (curl_multi_select($mh) != -1){
				do {
					$mrc = curl_multi_exec($mh, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}
		}

		$end = microtime_ms();

		$GLOBALS['timings']['http_count'] += count($handlers);
		$GLOBALS['timings']['http_time'] += $end-$start;

		foreach ($handles as $ch){

			$raw = curl_multi_getcontent($ch);
			$info = curl_getinfo($ch);

			curl_multi_remove_handle($mh, $ch);

			$rsp = _http_parse_response($raw, $info);
			$responses[] = $rsp;
		}

		curl_multi_close($mh);

		return $responses;
	}

	########################################################################

	# returns a plain vanilla curl handler with basic/common options set

	function _http_curl_handle($url, $headers=array(), $more=array()){

		$defaults = array(
			'http_timeout' => $GLOBALS['cfg']['http_timeout'],
		);

		$more = array_merge($defaults, $more);

		$headers_prepped = _http_prepare_outgoing_headers($headers);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_prepped);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $more['http_timeout']);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_HEADER, true);

		if ($more['http_port']){
			curl_setopt($ch, CURLOPT_PORT, $more['http_port']);
		}

		if ($more['follow_redirects']){
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, intval($more['follow_redirects']));
		}

		return $ch;
	}

	########################################################################

	function _http_request($ch, $url, $more=array()){

		$start = microtime_ms();

		$raw = curl_exec($ch);
		$info = curl_getinfo($ch);

		$end = microtime_ms();

		curl_close($ch);

		$GLOBALS['timings']['http_count']++;
		$GLOBALS['timings']['http_time'] += $end-$start;

		return _http_parse_response($raw, $info);
	}

	########################################################################

	function _http_parse_response($raw, $info){

		list($head, $body) = explode("\r\n\r\n", $raw, 2);
		list($head_out, $body_out) = explode("\r\n\r\n", $info['request_header'], 2);
		unset($info['request_header']);

		$headers_in = http_parse_headers($head, '_status');
		$headers_out = http_parse_headers($head_out, '_request');

		preg_match("/^([A-Z]+)\s/", $headers_out['_request'], $m);
		$method = $m[1];

		# log_notice("http", "{$method} {$url}", $end-$start);

		# http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.2
		# http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#2xx_Success (note HTTP 207 WTF)

		$status = $info['http_code'];

		if (($status < 200) || ($status > 299)){

			return array(
				'ok'		=> 0,
				'error'		=> 'http_failed',
				'code'		=> $info['http_code'],
				'method'	=> $method,
				'url'		=> $info['url'],
				'info'		=> $info,
				'req_headers'	=> $headers_out,
				'headers'	=> $headers_in,
				'body'		=> $body,
			);
		}

		return array(
			'ok'		=> 1,
			'code'		=> $info['http_code'],
			'method'	=> $method,
			'url'		=> $info['url'],
			'info'		=> $info,
			'req_headers'	=> $headers_out,
			'headers'	=> $headers_in,
			'body'		=> $body,
		);
	}

	########################################################################

	function http_parse_headers($raw, $first){

		#
		# first, deal with folded lines
		#

		$raw_lines = explode("\r\n", $raw);

		$lines = array();
		$lines[] = array_shift($raw_lines);

		foreach ($raw_lines as $line){
			if (preg_match("!^[ \t]!", $line)){
				$lines[count($lines)-1] .= ' '.trim($line);
			}else{
				$lines[] = trim($line);
			}
		}


		#
		# now split them out
		#

		$out = array(
			$first => array_shift($lines),
		);

		foreach ($lines as $line){
			list($k, $v) = explode(':', $line, 2);
			$out[StrToLower($k)] = trim($v);
		}

		return $out;
	}

	########################################################################

	function _http_prepare_outgoing_headers($headers=array()){

		$prepped = array();

		if (! isset($headers['Expect'])){
			$headers['Expect'] = '';	# Get around error 417
		}

		foreach ($headers as $key => $value){
			$prepped[] = "{$key}: {$value}";
		}

		return $prepped;
	}

	########################################################################

	# the end
