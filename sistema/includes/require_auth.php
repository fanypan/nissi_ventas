<?php

if (!function_exists('nissi_ensure_session')) {
	function nissi_ensure_session(): void
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
	}
}

$script = basename($_SERVER['SCRIPT_FILENAME'] ?? '');

if (preg_match('/^(test_|trace_|debug_)/i', $script) || $script === 'ajax_debug.php') {
	nissi_ensure_session();
	return;
}

if ($script === 'salir.php') {
	nissi_ensure_session();
	return;
}

nissi_ensure_session();

if (!empty($_SESSION['active'])) {
	return;
}

$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$isBackend = str_contains($scriptPath, '/action/')
	|| str_contains($scriptPath, 'ajax')
	|| str_contains($scriptPath, '/factura/');

if ($isBackend) {
	http_response_code(401);
	header('Content-Type: text/plain; charset=utf-8');
	echo 'Sesión no válida. Inicie sesión en la página principal.';
	exit;
}

header('Location: /');
exit;
