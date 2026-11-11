<?php 

	ensure_session();
	session_destroy();

	header('location: ../');
?>
