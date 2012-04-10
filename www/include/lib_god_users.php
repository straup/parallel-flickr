<?php

	#################################################################

	function god_users_get_users($args=array()){

		$sql = "SELECT * FROM users ORDER BY created DESC";
		return db_fetch_paginated($sql, $args);
	}

	#################################################################
?>
