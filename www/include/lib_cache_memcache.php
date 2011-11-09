<?php

	#################################################################

	function cache_memcache_connect(){

		if (! isset($GLOBALS['remote_cache_conns']['memcache'])){
			
			$host = $GLOBALS['cfg']['memcache_host'];
			$port = $GLOBALS['cfg']['memcache_port'];

			$start = microtime_ms();

			$memcache = new Memcache();

			if (! $memcache->connect($host, $port)){
				$memcache = null;
			}

			if (! $memcache){
				log_fatal("Connection to memcache {$host}:{$port} failed");
			}

			$end = microtime_ms();
			$time = $end - $start;

			log_notice("cache", "connect to memcache {$host}:{$port} ({$time}ms)");
			$GLOBALS['remote_cache_conns']['memcache'] = $memcache;

			$GLOBALS['timings']['memcache_conns_count']++;
			$GLOBALS['timings']['memcache_conns_time'] += $time;
		}

		return $GLOBALS['remote_cache_conns']['memcache'];
	}

	#################################################################

	function cache_memcache_get($cache_key){

		$memcache = cache_memcache_connect();

		if (! $memcache){
			return array( 'ok' => 0, 'error' => 'failed to connect to memcache' );
		}

		$rsp = $memcache->get($cache_key);

		if (! $rsp){
			return array( 'ok' => 0 );
		}

		return array(
			'ok' => 1,
			'data' => unserialize($rsp),
		);
	}

	#################################################################

	function cache_memcache_set($cache_key, $data){

		if (! $data){
			log_notice("cache", "missing data to set key {$cache_key}");
			return array( 'ok' => 0, 'error' => 'missing data' );
		}

		$memcache = cache_memcache_connect();

		if (! $memcache){
			return array( 'ok' => 0, 'error' => 'failed to connect to memcache' );
		}

		$ok = $memcache->set($cache_key, serialize($data));
		return array( 'ok' => $ok );
	}

	#################################################################

	function cache_memcache_unset($cache_key){

		$memcache = cache_memcache_connect();

		if (! $memcache){
			return array( 'ok' => 0, 'error' => 'failed to connect to memcache' );
		}

		$ok = $memcache->delete($cache_key);
		return array( 'ok' => $ok );
	}

	#################################################################

?>
