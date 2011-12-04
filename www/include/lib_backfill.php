<?php

	#
	# $Id$
	#

	#
	# THIS IS SO NOT EVEN CLOSE TO BEING FINISHED
	# (20101104/straup)
	#

	$GLOBALS['backfill_tick'] = 0;

	#################################################################

	function backfill_db_users($sql, $callback, $more=array()){

		$GLOBALS['backfill_tick'] = 0;

		foreach ($GLOBALS['cfg']['db_users']['host'] as $cluster_id => $ignore){
			_backfill_db_users_shard($cluster_id, $sql, $callback, $more);
		}
	}

	#################################################################

	function _backfill_db_users_shard($cluster_id, $sql, $callback, $more=array()){

		$page_count = null;
		$total_count = null;

		$per_page = ($more['per_page']) ? $more['per_page'] : 1000;

		$args = array(
			'per_page' => $per_page,
			'page' => 1,
		);

		while((! isset($page_count)) || ($page_count >= $args['page'])){

			$rsp = db_fetch_paginated_users($cluster_id, $sql, $args);

			if (! $rsp['ok']){
				break;
			}

			if (! isset($page_count)){
				$page_count = $rsp['pagination']['page_count'];
				$total_count = $rsp['pagination']['total_count'];
			}

			foreach ($rsp['rows'] as $row){
				call_user_func_array($callback, array($row));
				backfill_tick();
			}

			$args['page'] ++;
		}
	}

	#################################################################

	function backfill_tick(){

		$GLOBALS['backfill_tick'] ++;
		echo ".";

		if ($GLOBALS['backfill_tick'] == 100){
			$GLOBALS['backfill_tick'] = 0;
			echo "\n";
		}
	}

	#################################################################
?>
