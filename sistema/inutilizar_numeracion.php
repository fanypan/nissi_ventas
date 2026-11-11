<?php
ensure_session();

include "../conexion.php";
require_once __DIR__ . "/includes/sifen_client.php";

if (!sifen_esta_activo($conection)) {
	header('Location: index.php');
	exit;
}

$config = sifen_get_config($conection);
$aviso = '';
$avisoOk = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$tipo = (int)($_POST['tipoDocumento'] ?? 0);
	$desde = (int)($_POST['desde'] ?? 0);
	$hasta = (int)($_POST['hasta'] ?? 0);
	$motivo = trim((string)($_POST['motivo'] ?? ''));
	$permitidos = [1, 2, 4, 5, 6, 7];
	if (!in_array($tipo, $permitidos, true)) {
		$aviso = 'Elegí el tipo de comprobante.';
	} elseif ($desde < 1 || $hasta < $desde) {
		$aviso = 'El rango de números no es válido.';
	} elseif (($hasta - $desde) > 999) {
		$aviso = 'El rango no puede pasar de 1000 números.';
	} elseif ($motivo === '') {
		$aviso = 'El motivo es obligatorio.';
	} else {
		$resultado = sifen_enviar($conection, '/api/v1/events/inutilizacion', [
			'tipoDocumento' => $tipo,
			'establecimiento' => $config['establecimiento'] ?: '001',
			'punto' => $config['punto'] ?: '001',
			'desde' => $desde,
			'hasta' => $hasta,
			'motivo' => $motivo,
		]);
		$aviso = $resultado['msg'];
		$avisoOk = !empty($resultado['ok']);
	}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Inutilizar numeración</title>
</head>
<body>
	<?php include "includes/header.php"; ?>
	<section id="container">
		<h1><i class="fas fa-times-circle"></i> Inutilizar numeración</h1>
		<p>Informa números de timbrado que no se van a usar. Establecimiento <?= htmlspecialchars($config['establecimiento'] ?: '001'); ?>, punto <?= htmlspecialchars($config['punto'] ?: '001'); ?>.</p>
		<?php if ($aviso !== '') { ?>
			<p style="color: <?= $avisoOk ? '#23922d' : '#c0392b'; ?>;"><?= htmlspecialchars($aviso); ?></p>
		<?php } ?>
		<form method="post" class="containerPerfil" style="display:block;">
			<label>Tipo de comprobante</label>
			<select name="tipoDocumento" required>
				<option value="1">1 · Factura</option>
				<option value="2">2 · Exportación</option>
				<option value="4">4 · Autofactura</option>
				<option value="5">5 · Nota de crédito</option>
				<option value="6">6 · Nota de débito</option>
				<option value="7">7 · Remisión</option>
			</select>
			<label>Desde</label>
			<input type="number" name="desde" min="1" required>
			<label>Hasta</label>
			<input type="number" name="hasta" min="1" required>
			<label>Motivo</label>
			<input type="text" name="motivo" maxlength="500" required>
			<button type="submit" class="btn_new">Inutilizar</button>
		</form>
	</section>
	<?php include "includes/footer.php"; ?>
</body>
</html>
