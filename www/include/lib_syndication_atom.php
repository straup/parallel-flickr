<?php

	include_once("magpie/rss_fetch.inc");
	include_once("magpie/rss_parse.inc");

	function syndication_atom_parse_str($xml){
		return new MagpieRSS($xml);
	}

?>
