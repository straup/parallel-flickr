<?php

	loadlib("bergcloud_littleprinter");

	########################################################################

	function flickr_push_littleprinter($spr){

		$farm = $spr['farm'];
		$server = $spr['server'];
		$id = $spr['id'];
		$secret = $spr['secret'];

		$img = "https://farm{$farm}.staticflickr.com/{$server}/{$id}_{$secret}_n.jpg";

		$html = '<div style="font-size:18pt; font-weight: 700;">';
		$html .= '<p>A photo by ' . $spr['ownername'] . '</p>';
		$html .= '<img src="' . $img . '" />';
		$html .= '</div>';

		# log_info("[PUSH] HTML " . $html);

		$code = $GLOBALS['cfg']['littleprinter_direct_print_code'];
		$rsp = bergcloud_littleprinter_direct_print($html, $code);

		return $rsp;
	}

	########################################################################
