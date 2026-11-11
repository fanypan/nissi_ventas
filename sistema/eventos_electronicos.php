<?php
ensure_session();

include "../conexion.php";
require_once __DIR__ . "/includes/sifen_client.php";

if (!sifen_esta_activo($conection)) {
	header('Location: index.php');
	exit;
}

$aviso = '';
$avisoOk = false;
$docSeleccionado = trim((string)($_POST['document_id'] ?? $_GET['doc'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$accion = $_POST['action'] ?? '';
	$docSeleccionado = trim((string)($_POST['document_id'] ?? ''));
	if ($docSeleccionado === '') {
		$aviso = 'Elegí el comprobante.';
	} elseif ($accion === 'cancelarFE') {
		$motivo = trim((string)($_POST['motivo'] ?? ''));
		$resultado = $motivo === ''
			? ['ok' => false, 'msg' => 'El motivo es obligatorio.']
			: sifen_enviar($conection, '/api/v1/documents/' . rawurlencode($docSeleccionado) . '/cancel', ['motivo' => $motivo]);
		$aviso = $resultado['msg'];
		$avisoOk = !empty($resultado['ok']);
	} elseif ($accion === 'nominarFE') {
		$ruc = trim((string)($_POST['ruc'] ?? ''));
		$razon = trim((string)($_POST['razonSocial'] ?? ''));
		$email = trim((string)($_POST['email'] ?? ''));
		if ($ruc === '' || $razon === '') {
			$aviso = 'El RUC y la razón social son obligatorios.';
		} elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$aviso = 'El correo no es válido.';
		} else {
			$cuerpo = ['ruc' => $ruc, 'razonSocial' => $razon];
			if ($email !== '') {
				$cuerpo['email'] = $email;
			}
			$resultado = sifen_enviar($conection, '/api/v1/documents/' . rawurlencode($docSeleccionado) . '/nominate', $cuerpo);
			$aviso = $resultado['msg'];
			$avisoOk = !empty($resultado['ok']);
		}
	} elseif ($accion === 'receptorFE') {
		$tipo = (string)($_POST['event_type'] ?? '');
		$motivo = trim((string)($_POST['motivo'] ?? ''));
		$permitidos = ['conformidad', 'disconformidad', 'desconocimiento', 'notificacion'];
		if (!in_array($tipo, $permitidos, true)) {
			$aviso = 'Elegí el tipo de evento del receptor.';
		} elseif ($tipo === 'disconformidad' && $motivo === '') {
			$aviso = 'La disconformidad necesita un motivo.';
		} else {
			$cuerpo = ['event_type' => $tipo];
			if ($motivo !== '') {
				$cuerpo['motivo'] = $motivo;
			}
			$resultado = sifen_enviar($conection, '/api/v1/documents/' . rawurlencode($docSeleccionado) . '/receptor-event', $cuerpo);
			$aviso = $resultado['msg'];
			$avisoOk = !empty($resultado['ok']);
		}
	}
}

$documentos = [];
$query = mysqli_query(
	$conection,
	"SELECT fe.document_id, fe.noventa, fe.numero, fe.status, cl.nombre
	 FROM factura_electronica fe
	 LEFT JOIN venta v ON v.noventa = fe.noventa
	 LEFT JOIN cliente cl ON cl.idcliente = v.codcliente
	 WHERE fe.document_id IS NOT NULL AND fe.document_id <> ''
	 ORDER BY fe.id DESC
	 LIMIT 200"
);
if ($query) {
	while ($fila = mysqli_fetch_assoc($query)) {
		$documentos[] = $fila;
	}
}

function fe_opciones_documentos($documentos, $seleccionado)
{
	$html = '';
	foreach ($documentos as $doc) {
		$etiqueta = 'Venta ' . (int)$doc['noventa'];
		if (!empty($doc['numero'])) {
			$etiqueta .= ' · N° ' . $doc['numero'];
		}
		if (!empty($doc['nombre'])) {
			$etiqueta .= ' · ' . $doc['nombre'];
		}
		$etiqueta .= ' · ' . $doc['status'];
		$sel = $doc['document_id'] === $seleccionado ? ' selected' : '';
		$html .= '<option value="' . htmlspecialchars($doc['document_id']) . '"' . $sel . '>' . htmlspecialchars($etiqueta) . '</option>';
	}
	return $html;
}

$opciones = fe_opciones_documentos($documentos, $docSeleccionado);
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Eventos electrónicos</title>
</head>
<body>
	<?php include "includes/header.php"; ?>
	<section id="container">
		<h1><i class="fas fa-check-circle"></i> Eventos electrónicos</h1>
		<p>Cancelar, nominar o registrar un evento del receptor sobre un comprobante ya enviado.</p>
		<?php if ($aviso !== '') { ?>
			<p style="color: <?= $avisoOk ? '#23922d' : '#c0392b'; ?>;"><?= htmlspecialchars($aviso); ?></p>
		<?php } ?>
		<?php if (count($documentos) === 0) { ?>
			<p>Todavía no hay comprobantes con identificador de la API.</p>
		<?php } else { ?>
		<div class="containerPerfil" style="display:block;">
			<h2>Cancelar</h2>
			<form method="post">
				<input type="hidden" name="action" value="cancelarFE">
				<label>Comprobante</label>
				<select name="document_id" required><?= $opciones; ?></select>
				<label>Motivo</label>
				<input type="text" name="motivo" maxlength="500" required>
				<button type="submit" class="btn_new">Cancelar comprobante</button>
			</form>

			<h2>Nominar</h2>
			<form method="post">
				<input type="hidden" name="action" value="nominarFE">
				<label>Comprobante</label>
				<select name="document_id" required><?= $opciones; ?></select>
				<label>RUC</label>
				<input type="text" name="ruc" maxlength="20" required>
				<label>Razón social</label>
				<input type="text" name="razonSocial" maxlength="255" required>
				<label>Correo</label>
				<input type="email" name="email">
				<button type="submit" class="btn_new">Nominar</button>
			</form>

			<h2>Evento del receptor</h2>
			<form method="post">
				<input type="hidden" name="action" value="receptorFE">
				<label>Comprobante</label>
				<select name="document_id" required><?= $opciones; ?></select>
				<label>Tipo</label>
				<select name="event_type" required>
					<option value="conformidad">Conformidad</option>
					<option value="disconformidad">Disconformidad</option>
					<option value="desconocimiento">Desconocimiento</option>
					<option value="notificacion">Notificación de recepción</option>
				</select>
				<label>Motivo (obligatorio en disconformidad)</label>
				<input type="text" name="motivo" maxlength="500">
				<button type="submit" class="btn_new">Registrar evento</button>
			</form>
		</div>
		<?php } ?>
	</section>
	<?php include "includes/footer.php"; ?>
</body>
</html>
