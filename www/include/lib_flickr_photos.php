<?php

	loadlib("flickr_photos_lookup");
	loadlib("flickr_photos_search");
	loadlib("flickr_photos_permissions");

	#################################################################

	function flickr_photos_media_map($string_keys=0){

		$map = array(
			0 => 'photo',
			1 => 'video',
		);

		if ($string_keys){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	function flickr_photos_get_by_id($id){

		$cache_key = "photo_{$id}";

		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$lookup = flickr_photos_lookup_photo($id);

		if (! $lookup){
			return;
		}

		$user = users_get_by_id($lookup['user_id']);
		$cluster_id = $user['cluster_id'];

		# Temporary – this should never happen

		if (! $cluster_id){
			dumper($lookup);
			return null;
		}

		$enc_id = AddSlashes($id);
		$sql = "SELECT * FROM FlickrPhotos WHERE id='{$enc_id}'";

		$rsp = db_fetch_users($cluster_id, $sql);
		$row = db_single($rsp);

		if ($row){
			cache_set($cache_key, $row, "cache_locally");
		}

		return $row;
	}

	#################################################################

	function flickr_photos_update_photo(&$photo, $update){

		$cache_key = "photo_{$photo['id']}";

		#

		$lookup = flickr_photos_lookup_photo($photo['id']);

		if (! $lookup){
			return;
		}

		$user = users_get_by_id($lookup['user_id']);
		$cluster_id = $user['cluster_id'];

		$enc_id = AddSlashes($photo['id']);
		$where = "id={$enc_id}";

		# see also: git:parallel-flickr/solr/conf/schema.xml

		$solr_fields = array(
			'perms',
			'geoperms',
			'geocontext',
			'media',
			'latitude',
			'longitude',
			'accuracy',
			'woeid',
			'datetaken',
			'dateupload',
			'title',
			'description'

			# what about exif?
		);

		$solr_update = 0;

		$hash = array();

		foreach ($update as $k => $v){
			$hash[$k] = AddSlashes($v);

			if (in_array($k, $solr_fields)){
				$solr_update++;
			}
		}

		$rsp = db_update_users($cluster_id, 'FlickrPhotos', $hash, $where);

		if (! $rsp['ok']){
			return $rsp;
		}

		cache_unset($cache_key);

		if (($GLOBALS['cfg']['enable_feature_solr']) && ($solr_update)){

			$photo = flickr_photos_get_by_id($photo['id']);	

			# This is a quick hack that may become permanent. Basically 
			# we need to refetch the data in flickr.photos.getInfo in 
			# order to update the solr db. Normally the _index_photo pulls
			# this information from disk; the files having been written
			# by the bin/backup_photos.php script. As I write this the www
			# server does not have write permissions on the static photos
			# directory. If it did, this whole problem would go away and in
			# the end that may be the simplest possible solution. Until then
			# we'll fetch the (meta) data directly from the API and force
			# feed it to the search indexer. If you're wondering: Yes, it means
			# that the local solr db and the actual JSON dump of photos.getInfo
			# will be out of sync but that will sort itself out the next
			# time bin/backup_photos.php is run (20111231/straup)

			loadlib("flickr_photos_metadata");
			$meta = flickr_photos_metadata_fetch($photo, 'inflate');

			flickr_photos_search_index_photo($photo, $meta);
		}

		return $rsp;
	}

	#################################################################

	function flickr_photos_count_for_user(&$user, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);

		$cluster_id = $user['cluster_id'];
		$enc_user = AddSlashes($user['id']);

		if ($perms = flickr_photos_permissions_photos_where($user['id'], $more['viewer_id'])){
			$str_perms = implode(",", $perms);
			$extra = " AND perms IN ({$str_perms})";
		}

		$sql = "SELECT COUNT(id) AS cnt FROM FlickrPhotos WHERE user_id='{$enc_user}' {$extra}";
		$row = db_single(db_fetch_users($cluster_id, $sql));

		return $row['cnt'];
	}

	#################################################################

	function flickr_photos_for_user(&$user, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);

		$cluster_id = $user['cluster_id'];
		$enc_user = AddSlashes($user['id']);

		$extra = array();

		if ($perms = flickr_photos_permissions_photos_where($user['id'], $more['viewer_id'])){
			$str_perms = implode(",", $perms);
			$extra[] = "perms IN ({$str_perms})";
		}

		$extra = implode(" AND ", $extra);

		if (strlen($extra)){
			$extra = " AND {$extra}";
		}

		$sql = "SELECT * FROM FlickrPhotos WHERE user_id='{$enc_user}' {$extra} ORDER BY dateupload DESC";

		if (isset($more['with'])) {
			# Here, we are asking for a the page which a particular photo occurs in a person's stream, which
			# means we'll be passing in the determining the page number ourselves. We do this by figuring out
			# how many photos are before this one in the stream and then dividing.

			$photo = flickr_photos_get_by_id($more['with']);
			$can_see_photo = $photo ? flickr_photos_permissions_can_view_photo($photo, $GLOBALS['cfg']['user']['id']) : false;

			if ($can_see_photo) {

				# The only reason we need this is for spill messing with per-page amounts
				$pagination_more = $more;
				$pagination_more['just_pagination'] = 1;

				$pagination = db_fetch_paginated_users($cluster_id, $sql, $pagination_more);

				$offset_where = " AND dateupload >= '{$photo['dateupload']}'";
				
				$offset_sql = "SELECT COUNT(*) FROM FlickrPhotos WHERE user_id='{$enc_user}' {$extra} {$offset_where} ORDER BY dateupload DESC";

				$ret = db_fetch_users($cluster_id, $offset_sql);
				if($ret['ok']) {
					$offset_count = intval(array_pop($ret['rows'][0]));

					$per_page	= isset($more['per_page'])	? max(1, $more['per_page'])	: $GLOBALS['cfg']['pagination_per_page'];

					$page = ceil($offset_count / $per_page);

					if ($page > $pagination['page_count']){
						$page--;
					}

					$more['page'] = $page;
				}
			}
		}

		return db_fetch_paginated_users($cluster_id, $sql, $more);
	}

	#################################################################

	function flickr_photos_add_photo($photo){

		$user = users_get_by_id($photo['user_id']);
		$cluster_id = $user['cluster_id'];

		$insert = array();

		foreach ($photo as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert_users($cluster_id, 'FlickrPhotos', $insert);

		if (! $rsp['ok']){
			return $rsp;
		}

		$rsp['photo'] = $photo;
		return $rsp;
	}

	#################################################################

	function flickr_photos_get_bookends_for_user(&$user, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
			'context' => 'datetaken',
		);

		$more = array_merge($defaults, $more);

		if (! in_array($more['context'], array('datetaken', 'dateupload'))){
			return not_okay("invalid date context");
		}

		$cluster_id = $user['cluster_id'];
		$enc_user = AddSlashes($user['id']);

		if ($perms = flickr_photos_permissions_photos_where($user['id'], $more['viewer_id'])){
			$str_perms = implode(",", $perms);
			$extra = " AND perms IN ({$str_perms})";
		}

		# TO DO: INDEXES

		$sql = "SELECT MIN(`{$more['context']}`) AS start, MAX(`{$more['context']}`) AS end FROM FlickrPhotos WHERE user_id = '{$enc_user}' {$extra}";
		$rsp = db_fetch_users($cluster_id, $sql);

		if (! $rsp['ok']){
			return $rsp;
		}

		$row = db_single($rsp);

		if (! $row){
			return not_okay("no photos to bookend!");
		}

		return okay($row);
	}

	#################################################################

	function flickr_photos_get_bookends(&$photo, $more=array()){

		$defaults = array(
			'viewer_id' => 0,
		);

		$more = array_merge($defaults, $more);

		$user = users_get_by_id($photo['user_id']);
		$cluster_id = $user['cluster_id'];

		$enc_id = AddSlashes($photo['id']);
		$enc_user = AddSlashes($photo['user_id']);

		if ($perms = flickr_photos_permissions_photos_where($user['id'], $more['viewer_id'])){
			$str_perms = implode(",", $perms);
			$extra = " AND perms IN ({$str_perms})";
		}

		# TO DO: INDEXES

		$sql = "SELECT * FROM FlickrPhotos WHERE user_id = '{$enc_user}' AND id < '{$enc_id}' {$extra} ORDER BY id DESC LIMIT 1";
		$rsp = db_fetch_users($cluster_id, $sql);

		$before = $rsp['rows'];

		# TO DO: INDEXES

		$sql = "SELECT * FROM FlickrPhotos WHERE user_id='{$enc_user}' AND id > '{$enc_id}' {$extra} ORDER BY id ASC LIMIT 1";
		$rsp = db_fetch_users($cluster_id, $sql);

		$after = $rsp['rows'];

		return array(
			'ok' => 1,
			'before' => $before,
			'after' => $after,
		);
	}

	#################################################################

	function flickr_photos_id_to_path($id){

		$parts = array();

		while (strlen($id)){

			$parts[] = substr($id, 0, 3);
			$id = substr($id, 3);
		}

		return implode("/", $parts);
	}

	#################################################################
?>
