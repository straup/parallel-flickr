<?php

	##############################################################################

	function api_utils_ensure_pagination_args(&$args){

		if ($page = request_int32("page")){
			$args['page'] = $page;
		}

		if ($per_page = request_int32("per_page")){
			$args['per_page'] = $per_page;
		}

		if (! $args['page']){
			$args['page'] = 1;
		}

		if (! $args['per_page']){
			$args['per_page'] = $GLOBALS['cfg']['api_per_page_default'];
		}

		else if ($args['per_page'] > $GLOBALS['cfg']['api_per_page_max']){
			$args['per_page'] = $GLOBALS['cfg']['api_per_page_max'];
		}

		# note the pass by ref
	}

	##############################################################################

	function api_utils_ensure_pagination_results(&$out, &$pagination){

		$out['total'] = $pagination['total_count'];
		$out['page'] = $pagination['page'];
		$out['per_page'] = $pagination['per_page'];
		$out['pages'] = $pagination['page_count'];

		# note the pass by ref
	}
	
	##############################################################################

	# the end
