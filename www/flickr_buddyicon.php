<?php

	include("include/init.php");
	loadlib("flickr_api");

	# http://www.flickr.com/services/api/flickr.people.getInfo.html
	# http://www.flickr.com/services/api/misc.buddyicons.html

	$nsid = get_str("nsid");

	if (! $nsid){
		error_404();
	}

	$cache_key = "flickr_buddyicon_{$nsid}";
	$cache = cache_get($cache_key);

	if ($cache['ok']){
		$buddyicon = $cache['data'];
	}

	else {
		$method = "flickr.people.getInfo";

		$args = array(
			"user_id" => $nsid,
		);

		$rsp = flickr_api_call($method, $args);

		if (! $rsp['ok']){
			error_500();
		}

		$icon_server = $rsp['rsp']['person']['iconserver'];
		$icon_farm = $rsp['rsp']['person']['iconfarm'];

		if (! $icon_server){
			$buddyicon = "http://www.flickr.com/images/buddyicon.jpg";
		}

		else {
			$buddyicon = "http://farm{$icon_farm}.static.flickr.com/{$icon_server}/buddyicons/{$nsid}.jpg";
		}

		cache_set($cache_key, $buddyicon);
	}

	header("location: {$buddyicon}");
	exit();

?>
