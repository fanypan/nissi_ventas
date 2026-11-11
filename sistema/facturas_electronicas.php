<?php
ensure_session();

include "../conexion.php";
require_once __DIR__ . "/includes/sifen_client.php";

if (!sifen_esta_activo($conection)) {
	header('Location: index.php');
	exit;
}

$aviso = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'actualizarEstadosFE') {
	$cantidad = sifen_refrescar_pendientes($conection);
	$aviso = $cantidad > 0
		? 'Se consultaron ' . $cantidad . ' facturas que seguían en proceso.'
		: 'No había facturas en proceso para consultar.';
}

$estados = [
	'queued' => 'En cola',
	'processing' => 'Procesando',
	'approved' => 'Aprobada',
	'rejected' => 'Rechazada',
	'error' => 'Error',
	'contingency' => 'Contingencia',
	'cancelled' => 'Anulada',
];

$filas = [];
$query = mysqli_query(
	$conection,
	"SELECT fe.document_id, fe.noventa, fe.status, fe.cdc, fe.numero, fe.error_message, fe.date_add,
	        v.totalventa, cl.nombre
	 FROM factura_electronica fe
	 LEFT JOIN venta v ON v.noventa = fe.noventa
	 LEFT JOIN cliente cl ON cl.idcliente = v.codcliente
	 ORDER BY fe.id DESC
	 LIMIT 200"
);
if ($query) {
	while ($fila = mysqli_fetch_assoc($query)) {
		$filas[] = $fila;
	}
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Facturas electrónicas</title>
</head>
<body>
	<?php include "includes/header.php"; ?>
	<section id="container">
		<h1><i class="fas fa-file-alt"></i> Facturas electrónicas</h1>
		<form method="post" style="margin-bottom: 12px;">
			<input type="hidden" name="action" value="actualizarEstadosFE">
			<button type="submit" class="btn_new"><i class="fas fa-sync-alt"></i> Actualizar estados</button>
		</form>
		<?php if ($aviso !== '') { ?>
			<p><?= htmlspecialchars($aviso); ?></p>
		<?php } ?>
		<div class="containerTable">
			<table>
				<tr>
					<th>Venta</th>
					<th>Fecha</th>
					<th>Cliente</th>
					<th>Número</th>
					<th>Estado</th>
					<th>CDC</th>
					<th>Detalle</th>
					<th>KuDE</th>
					<th>Eventos</th>
				</tr>
				<?php if (count($filas) === 0) { ?>
					<tr><td colspan="9">Todavía no hay facturas electrónicas enviadas.</td></tr>
				<?php } ?>
				<?php foreach ($filas as $fila) {
					$estado = $estados[$fila['status']] ?? $fila['status'];
					$docId = trim((string)($fila['document_id'] ?? ''));
					?>
					<tr>
						<td><?= (int)$fila['noventa']; ?></td>
						<td><?= htmlspecialchars((string)$fila['date_add']); ?></td>
						<td><?= htmlspecialchars((string)($fila['nombre'] ?? '')); ?></td>
						<td><?= htmlspecialchars((string)($fila['numero'] ?? '')); ?></td>
						<td><?= htmlspecialchars($estado); ?></td>
						<td><?= htmlspecialchars((string)($fila['cdc'] ?? '')); ?></td>
						<td><?= htmlspecialchars((string)($fila['error_message'] ?? '')); ?></td>
						<td><?php if ($docId !== '') { ?><a href="kude.php?doc=<?= urlencode($docId); ?>" target="_blank" rel="noopener">Ver KuDE</a><?php } ?></td>
						<td><?php if ($docId !== '') { ?><a href="eventos_electronicos.php?doc=<?= urlencode($docId); ?>">Registrar</a><?php } ?></td>
					</tr>
				<?php } ?>
			</table>
		</div>
	</section>
	<?php include "includes/footer.php"; ?>
</body>
</html>
