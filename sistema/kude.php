<?php
ensure_session();

include "../conexion.php";
require_once __DIR__ . "/includes/sifen_client.php";

$noventa = (int)($_GET['v'] ?? 0);
$pedido = trim((string)($_GET['doc'] ?? ''));
$documentId = '';
if ($pedido !== '' && strlen($pedido) <= 64) {
	$stmt = mysqli_prepare(
		$conection,
		"SELECT document_id FROM factura_electronica WHERE document_id = ? LIMIT 1"
	);
	if ($stmt) {
		mysqli_stmt_bind_param($stmt, 's', $pedido);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $documentId);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
	}
} elseif ($noventa > 0) {
	$stmt = mysqli_prepare(
		$conection,
		"SELECT document_id FROM factura_electronica
		 WHERE noventa = ? AND document_id IS NOT NULL AND document_id <> ''
		 ORDER BY id DESC LIMIT 1"
	);
	if ($stmt) {
		mysqli_stmt_bind_param($stmt, 'i', $noventa);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $documentId);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
	}
}

$config = sifen_get_config($conection);
$html = '';
if ($documentId !== '') {
	$html = sifen_bajar_kude_html($config['api_url'], $config['api_token'], $documentId);
}

if ($html === '') {
	http_response_code(404);
	header('Content-Type: text/html; charset=UTF-8');
	echo '<!DOCTYPE html><html lang="es"><body><p>El KuDE todavía no está disponible.</p></body></html>';
	exit;
}

header('Content-Type: text/html; charset=UTF-8');
echo $html;
