<?php

	# EXPERIMENTAL (20111126/straup)

	#################################################################

	function flickr_push_subscriptions_urls_get_by_url($url){

		$enc_url = AddSlashes($url);
		$sql = "SELECT * FROM FlickrPushSubscriptionUrls WHERE url='{$enc_url}'";

		$rsp = db_single(db_fetch($sql));
		return $rsp;
	}

	#################################################################

	function flickr_push_subscriptions_urls_get_by_id($url_id){

		$enc_id = AddSlashes($url_id);
		$sql = "SELECT * FROM FlickrPushSubscriptionUrls WHERE id='{$enc_id}'";

		$rsp = db_single(db_fetch($sql));
		return $rsp;
	}

	#################################################################

	function flickr_push_subscriptions_urls_create($url, $label=null, $args=null){

		if ($row = flickr_push_subscriptions_urls_get_by_url($url)){

			return array(
				'ok' => 1,
				'url' => $row,
			);
		}

		$id = dbtickets_create();

		$row = array(
			'id' => $id,
			'url' => $url,
		);

		if (isset($label)){
			$row['label'] = $label;
		}

		if (isset($args)){
			$row['args'] = json_encode($args);
		}

		$insert = array();

		foreach ($row as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert('FlickrPushSubscriptionUrls', $insert);

		if ($rsp['ok']){
			$rsp['url'] = $row;
		}

		return $rsp;
	}

	#################################################################	
?>
