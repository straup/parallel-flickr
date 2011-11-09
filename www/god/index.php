<?php

	include("include/init.php");

	if (! auth_has_role('admin')){
		error_404();
	}


?>
