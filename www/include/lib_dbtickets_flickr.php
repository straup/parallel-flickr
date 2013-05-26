<?php

	loadlib("flickr_api");

	########################################################################

	# TO DO: figure out if this should use a given user's account or always
	# just use $GLOBALS['cfg']['dbtickets_flickr_auth_token'] – I am inclined
	# towards the latter solution but I'm not sure. Doing the former would
	# also mean patching the push feeds receiver to ignore certain photos
	# based on something like a tag (for example 'flickr:push=ignore). Dunno.
	# (20130520/straup)
	
	function dbtickets_flickr_create(){

		# This is a terrible fucking hack to account for the fact that p-flickr
		# is a 'web app' and tries to redirect. This is not the way it should be
		# it's just a way to make shit work at 8 in the morning (20130521/straup)

		$api_key = $GLOBALS['cfg']['flickr_api_key'];
		$api_secret = $GLOBALS['cfg']['flickr_api_secret'];

		$GLOBALS['cfg']['flickr_api_key'] = $GLOBALS['cfg']['dbtickets_flickr_api_key'];
		$GLOBALS['cfg']['flickr_api_secret'] = $GLOBALS['cfg']['dbtickets_flickr_api_secret'];

		#

		$tmp_dir = sys_get_temp_dir();
		$tmp_file = tempnam($tmp_dir, "dbtickets_flickr") . ".gif";

		if (! file_exists($tmp_file)){
			$fh = fopen($tmp_file, 'wb');
			fwrite($fh, base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw=='));
			fclose($fh);
		}

		$auth_token = $GLOBALS['cfg']['dbtickets_flickr_auth_token'];

		$args = array(
			'auth_token' => $auth_token,
			'is_public' => 0,
			# 'tags' => 'flickr:push=ignore',
		);

		$more = array(
			'http_timeout' => 30,
		);

		$rsp = flickr_api_upload($tmp_file, $args, $more);

		# unlink($tmp_file);

		if (! $rsp['ok']){
			return $rsp;
		}

		$photo_id = $rsp['photo_id'];

		$args = array(
			'photo_id' => $photo_id,
			'auth_token' => $auth_token,
		);

		$rsp = flickr_api_call('flickr.photos.delete', $args);

		# See above

		$GLOBALS['cfg']['flickr_api_key'] = $api_key;
		$GLOBALS['cfg']['flickr_api_secret'] = $api_secret;

		return array(
			'ok' => 1,
			'id' => $photo_id,
		);
	}

	########################################################################

	# the end
