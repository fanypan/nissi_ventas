<?php 

	nissi_ensure_session();
	session_destroy();

	header('location: ../');
?>
