<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	set_time_limit(0);

	include("include/init.php");

	# http://www.smarty.net/docs/en/api.compile.all.templates.tpl (version 3)
	# $GLOBALS['smarty']->compileAllTemplates('.txt', true);

	$pattern = "{$GLOBALS['cfg']['smarty_template_dir']}/*.txt";
	$templates = array();

	foreach (glob($pattern) as $f){
		$templates[] = basename($f);
	}

	$GLOBALS['smarty']->force_compile = true;

	foreach ($templates as $t){
		$GLOBALS['smarty']->fetch($t);
	}

	exit();
?>
