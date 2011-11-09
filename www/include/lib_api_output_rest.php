<?php

	#
	# $Id$
	#

	#################################################################

	function api_output_ok($rsp=array()){

		$rsp['stat'] = 'ok';

		_api_output_rest_send_response($rsp);
	}

	#################################################################

	function api_output_error($code=999, $msg='self destruct'){

		$rsp = array(
			'stat' => 'fail',
			'error' => array(
				'code' => $code,
				'message' => $msg,
			),
		);

		_api_output_rest_send_response($rsp);
	}

	#################################################################

	function _api_output_rest_send_response(&$rsp){

		$format = $GLOBALS['cfg']['api']['formats']['current'];
		$function = "_api_output_rest_send_{$format}";

		call_user_func_array($function, array($rsp));
		exit();
	}

	#################################################################

	function _api_output_rest_send_xml(&$rsp){

		utf8_headers('text/xml');

		echo '<?xml version="1.0" ?>';
		echo _api_output_rest_serialize_xml('rsp', $rsp);
	}

	#################################################################

	function _api_output_rest_serialize_xml($root, &$data){

		$root = htmlspecialchars($root);
		$xml = "<{$root}";

		$kids = 0;

		foreach ($data as $key => $value){

			if (is_array($value)){
				$kids ++;
				continue;
			}

			$attr = htmlspecialchars($key);
			$cdata = htmlspecialchars($value);
			$xml .= " {$attr}=\"{$cdata}\"";
		}

		#
		# There are no child elements, so
		# just finish up and go home...
		#

		if (! $kids){
			$xml .= "/>";
			return $xml;
		}

		#
		# Carry on, counting sheep
		#

		$xml .= ">";

		foreach ($data as $key => $value){

			if (! is_array($value)){
				continue;
			}

			if (isset($value['_content'])){
				$el = htmlspecialchars($key);
				$cdata = htmlspecialchars($value['_content']);
				$xml .= "<{$el}>{$cdata}</{$el}>";
			}

			else {
				$xml.= _api_output_rest_serialize_xml($key, $value);
			}
		}

		$xml .= "</{$root}>";
		return $xml;
	}

	#################################################################

	function _api_output_rest_send_json_headers(){

		$content_type = 'application/json';

		if (request_isset('_jsondebug')){
			$content_type = 'text/plain';
		}

		utf8_headers($content_type);
	}

	#################################################################

	function _api_output_rest_send_json(&$rsp){

		_api_output_rest_send_json_headers();
		echo json_encode($rsp);
	}

	#################################################################

	function _api_output_rest_send_jsonp(&$rsp){

		$callback = request_str('callback');
		$callback = filter_strict($callback);

		if (! $callback){
			$callback = "makeItSo";
		}

		$callback = htmlspecialchars($callback);

		_api_output_rest_send_json_headers();
		echo $callback . "(" . json_encode($rsp) . ")";
	}

	#################################################################

?>