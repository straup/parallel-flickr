<?php

	#################################################################

	function solr_dates_prep_mysql_datetime($dt){

		$ts = strtotime($dt);
		return solr_dates_prep_timestamp($ts);
	}

	#################################################################

	function solr_dates_prep_timestamp($ts){

		$fmt = "Y-m-d\TH:i:s\Z";
		$dt = gmdate($fmt, $ts);

		return $dt;
	}

	#################################################################
?>
