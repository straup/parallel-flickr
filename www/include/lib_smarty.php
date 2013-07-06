<?
	#
	# $Id$
	#

	$GLOBALS['timings']['smarty_comp_count']	= 0;
	$GLOBALS['timings']['smarty_comp_time']	= 0;

	define('FLAMEWORK_SMARTY_DIR', FLAMEWORK_INCLUDE_DIR.'/smarty_2.6.26/');
	require(FLAMEWORK_SMARTY_DIR . 'Smarty.class.php');

	$GLOBALS['smarty'] = new Smarty();

	$GLOBALS['smarty']->template_dir = $GLOBALS['cfg']['smarty_template_dir'];
	$GLOBALS['smarty']->compile_dir  = $GLOBALS['cfg']['smarty_compile_dir'];
	$GLOBALS['smarty']->compile_check = $GLOBALS['cfg']['smarty_compile'];
	$GLOBALS['smarty']->force_compile = $GLOBALS['cfg']['smarty_force_compile'];

	$GLOBALS['smarty']->assign_by_ref('cfg', $GLOBALS['cfg']);

	#######################################################################################

	function smarty_timings(){

		$GLOBALS['timings']['smarty_timings_out'] = microtime_ms();

		echo "<table class=\"debugtimings\" border=\"1\" align=\"center\">\n";
		echo "<tr>\n";
		echo "<th>Item</th>";
		echo "<th>Count</th>";
		echo "<th>Time</th>";
		echo "</tr>\n";

		# we add this one last so it goes at the bottom of the list
		$GLOBALS['timing_keys']['smarty_comp'] = 'Templates Compiled';

		foreach ($GLOBALS['timing_keys'] as $k => $v){
			$c = intval($GLOBALS['timings']["{$k}_count"]);
			$t = intval($GLOBALS['timings']["{$k}_time"]);
			echo "<tr><td>$v</td><td>$c</td><td>$t ms</td></tr>\n";
		}

		$map2 = array(
			array("Startup &amp; Libraries", $GLOBALS['timings']['init_end'] - $GLOBALS['timings']['execution_start']),
			array("Page Execution", $GLOBALS['timings']['smarty_start_output'] - $GLOBALS['timings']['init_end']),
			array("Smarty Output", $GLOBALS['timings']['smarty_timings_out'] - $GLOBALS['timings']['smarty_start_output']),
			array("<b>Total</b>", $GLOBALS['timings']['smarty_timings_out'] - $GLOBALS['timings']['execution_start']),
		);

		foreach ($map2 as $a){
			echo "<tr><td colspan=\"2\">$a[0]</td><td>$a[1] ms</td></tr>\n";
		}

		echo "</table>";
	}

	$GLOBALS['smarty']->register_function('timings', 'smarty_timings');

	#######################################################################################

	# keep this there or in... what ?

	function escape_javascript($str){

		$to_replace = array(
			'\\' => '\\\\',
			"'"  => "\\'",
			'"'  => '\\"',
			"\r" => '\\r',
			"\n" => '\\n',
			'</' => '<\/'
		);

		return strtr($str, $to_replace);
	}

	#######################################################################################
?>
