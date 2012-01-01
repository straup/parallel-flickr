<?php

    /*
    This file finds all variables in config.php.sample that aren't in config.php. Anything more complex appears to need
    human interaction. 'diff' is good but it's hard to see the trees for the forest at times.
    */

    $dir = dirname(__FILE__);

    unset($GLOBALS['cfg']);

    $sample_file = include("$dir/../www/include/config.php.example");
    $sample_config = $GLOBALS['cfg'];
    unset($GLOBALS['cfg']);

    $prod_file = include("$dir/../www/include/config.php");
    $prod_config = $GLOBALS['cfg'];
    unset($GLOBALS['cfg']);

    $diff = array_diff_key($sample_config, $prod_config);
    print_r($diff);

    print "\n\n";

