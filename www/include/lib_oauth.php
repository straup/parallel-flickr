<?
	#
	# $Id$
	#

	#
	# lib_oauth - A standalone PHP4 OAuth library
	#
	# By Cal Henderson <cal@iamcal.com>
	#
	# Heavily based on the PHP5 OAuth library
	# http://code.google.com/p/oauth-php/
	#
	# Patches from:
	#  * Kellan <kellan@pobox.com>
	#    - Flickr compatibility fix
	#  * Zhihong Zhang <zhihong.zhang@corp.aol.com>
	#    - quoted key names for E_WARNINGS mode
	#    - caught the urlencode() vs rawurlencode() bug
	#  * Paul Webster <paul@dabdig.com>
	#    - POST support
	#    - cURL support
	#
	# This program is free software; you can redistribute it and/or modify
	# it under the terms of the GNU General Public License as published by
	# the Free Software Foundation; either version 2 of the License, or
	# (at your option) any later version.
	# 
	# This program is distributed in the hope that it will be useful,
	# but WITHOUT ANY WARRANTY; without even the implied warranty of
	# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	# GNU General Public License for more details.
	# 
	# You should have received a copy of the GNU General Public License
	# along with this program; if not, write to the Free Software
	# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	#

	################################################################################################

	#
	# use fopen wrappers for GETs - this will usually work on servers
	# that don't have cURL installed, but wont work for POST requests
	#

	$GLOBALS['oauth_use_fopen_wrappers'] = false;


	#
	# seconds before HTTP requests time out (cURL only)
	#

	$GLOBALS['oauth_http_timeout'] = 2;


	#
	# this gets filled after each HTTP request
	#

	$GLOBALS['oauth_last_request'] = array();

	################################################################################################

	function oauth_sign($key_bucket, $url, $params=array(), $method="GET"){

		#
		# fold query params passed on the URL into params array
		#

		$url_parsed = parse_url($url);
		if (isset($url_parsed['query'])){
			parse_str($url_parsed['query'], $url_params);
			$params = array_merge($params, $url_params);
		}
		

		#
		# create the request thingy
		#

		$oauth_params = $params;
		$oauth_params['oauth_version']		= '1.0';
		$oauth_params['oauth_nonce']		= oauth_generate_nonce();
		$oauth_params['oauth_timestamp']	= oauth_generate_timestamp();
		$oauth_params['oauth_consumer_key']	= $key_bucket['oauth_key'];

		if (isset($key_bucket['user_key'])){
			$oauth_params['oauth_token']		= $key_bucket['user_key'];
		}

		$oauth_params['oauth_signature_method']	= 'HMAC-SHA1';
		$oauth_params['oauth_signature']	= oauth_build_signature($key_bucket, $url, $oauth_params, $method);

		return $oauth_params;
	}

	################################################################################################

	function oauth_sign_get($key_bucket, $url, $params=array(), $method="GET"){

		$params = oauth_sign($key_bucket, $url, $params, $method);

		$url = oauth_normalize_http_url($url) . "?" . oauth_to_postdata($params);

		return $url;
	}

	################################################################################################

	function oauth_request($key_bucket, $url, $params=array(), $method="GET"){

		$url = oauth_sign_get($key_bucket, $url, $params, $method);

		if ($method == 'POST'){
			list($url, $postdata) = explode('?', $url, 2);
		}else{
			$postdata = null;
		}

		return oauth_http_request($url, $method, $postdata);
	}

	################################################################################################	

	function oauth_build_signature($key_bucket, $url, $params, $method){

		$sig = array(
			rawurlencode(StrToUpper($method)),
			preg_replace('/%7E/', '~', rawurlencode(oauth_normalize_http_url($url))),
			rawurlencode(oauth_get_signable_parameters($params)),
		);

		$key = rawurlencode($key_bucket['oauth_secret']) . "&";

		if (isset($key_bucket['user_key'])){
			$key .= rawurlencode($key_bucket['user_secret']);
		}

		$raw = implode("&", $sig);
		#echo "base string: $raw\n";

		$hashed = base64_encode(oauth_hmac_sha1($raw, $key, TRUE));
		return $hashed;
	}

	################################################################################################	

	function oauth_normalize_http_url($url){
		$parts = parse_url($url);
		$port = "";
		if (array_key_exists('port', $parts) && $parts['port'] != '80'){
			$port = ':' . $parts['port'];
		}
		return "{$parts['scheme']}://{$parts['host']}{$port}{$parts['path']}"; 
	}

	################################################################################################	

	function oauth_get_signable_parameters($params){
		$sorted = $params;
		ksort($sorted);

		$total = array();
		foreach ($sorted as $k => $v) {
			if ($k == "oauth_signature") continue;
			$total[] = rawurlencode($k) . "=" . rawurlencode($v);
		}
		return implode("&", $total);
	}

	################################################################################################	

	function oauth_to_postdata($params){
		$total = array();
		foreach ($params as $k => $v) {
			$total[] = rawurlencode($k) . "=" . rawurlencode($v);
		}
		$out = implode("&", $total);
		return $out;
	}

	################################################################################################	

	function oauth_generate_timestamp(){
		return time();
	}

	################################################################################################	

	function oauth_generate_nonce(){
		$mt = microtime();
		$rand = mt_rand();
		return md5($mt . $rand); // md5s look nicer than numbers
	}

	################################################################################################	

	function oauth_hmac_sha1($data, $key, $raw=TRUE){

		if (strlen($key) > 64){
			$key =  pack('H40', sha1($key));
		}

		if (strlen($key) < 64){
			$key = str_pad($key, 64, chr(0));
		}

		$_ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
		$_opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));

		$hex = sha1($_opad . pack('H40', sha1($_ipad . $data)));

		if ($raw){
			$bin = '';
			while (strlen($hex)){
				$bin .= chr(hexdec(substr($hex, 0, 2)));
				$hex = substr($hex, 2);
			}
			return $bin;
		}

		return $hex;
	}

	################################################################################################	

	function oauth_get_auth_token(&$key_bucket, $url, $params=array()){

		$url = oauth_sign_get($key_bucket, $url, $params);
		$bits = oauth_url_to_hash($url);

		$key_bucket['request_key']	= $bits['oauth_token'];
		$key_bucket['request_secret']	= $bits['oauth_token_secret'];

		if ($key_bucket['request_key'] && $key_bucket['request_secret']){
			return 1;
		}

		return 0;
	}

	################################################################################################	

	function oauth_url_to_hash($url){

		$crap = oauth_http_request($url);
		$bits = explode("&", $crap);

		$out = array();
		foreach ($bits as $bit){
			list($k, $v) = explode('=', $bit, 2);
			$out[urldecode($k)] = urldecode($v);
		}

		return $out;
	}

	################################################################################################

	function oauth_get_auth_url(&$key_bucket, $url, $params=array()){

		return $url . "?oauth_token=$key_bucket[request_key]";
	}

	################################################################################################

	function oauth_get_access_token(&$key_bucket, $url, $params=array()){

		$key_bucket['user_key']		= $key_bucket['request_key'];
		$key_bucket['user_secret']	= $key_bucket['request_secret'];

		$url = oauth_sign_get($key_bucket, $url, $params);
		$bits = oauth_url_to_hash($url);

		$key_bucket['user_key']		= $bits['oauth_token'];
		$key_bucket['user_secret']	= $bits['oauth_token_secret'];

		if ($key_bucket['user_key'] && $key_bucket['user_secret']){
			return 1;
		}

		return 0;
	}

	################################################################################################
	
	function oauth_http_request($url, $method="GET", $postdata=null){

		#
		# use fopen wrappers?
		#

		if ($GLOBALS['oauth_use_fopen_wrappers'] && $method == 'GET'){

			$response = implode("", file($url));

			$GLOBALS['oauth_last_request'] = array(
				'request'	=> array(
					'url'		=> $url,
					'method'	=> $method,
				),
				'body'		=> $response,
			);

			return $response;
		}


		#
		# use curl
		#

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); 	// Get around error 417
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $GLOBALS['oauth_http_timeout']);

		if ($method == 'GET'){
			# nothing special for GETs
		}else if ($method == 'POST'){
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		}else{
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		}
		
		$response = curl_exec($ch);
		$headers = curl_getinfo($ch);

		curl_close($ch);

		$GLOBALS['oauth_last_request'] = array(
			'request'	=> array(
				'url'		=> $url,
				'method'	=> $method,
				'postdata'	=> $postdata,
			),
			'headers'	=> $headers,
			'body'		=> $response,
		);

	        if ($headers['http_code'] != "200"){

			return '';
		}

		return $response;
	}
	
	################################################################################################
?>
