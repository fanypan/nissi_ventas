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
	$razon = trim((string)($_POST['razonSocial'] ?? ''));
	$ruc = trim((string)($_POST['ruc'] ?? ''));
	$email = trim((string)($_POST['email'] ?? ''));
	$pais = strtoupper(trim((string)($_POST['pais'] ?? 'PRY')));
	$descripcion = trim((string)($_POST['descripcion'] ?? ''));
	$cantidad = (float)($_POST['cantidad'] ?? 0);
	$precio = (float)($_POST['precio'] ?? 0);
	$iva = (int)($_POST['iva'] ?? $config['iva_por_defecto']);
	if (!in_array($iva, [0, 5, 10], true)) {
		$iva = 10;
	}

	if (!in_array($tipo, [2, 4, 5, 6, 7], true)) {
		$aviso = 'Elegí el tipo de comprobante.';
	} elseif ($razon === '' || $descripcion === '' || $cantidad <= 0) {
		$aviso = 'La razón social, la descripción y la cantidad son obligatorias.';
	} elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$aviso = 'El correo no es válido.';
	} elseif ($tipo === 2 && ($pais === '' || $pais === 'PRY')) {
		$aviso = 'La exportación exige un país distinto de PRY.';
	} elseif (in_array($tipo, [5, 6], true) && trim((string)($_POST['cdc'] ?? '')) === '' && (trim((string)($_POST['timbrado_asoc'] ?? '')) === '' || (int)($_POST['numero_asoc'] ?? 0) < 1)) {
		$aviso = 'La nota necesita el CDC del comprobante asociado, o el timbrado y el número.';
	} else {
		$cliente = [
			'contribuyente' => sifen_es_ruc($ruc),
			'razonSocial' => $razon,
		];
		if ($ruc !== '') {
			$cliente['ruc'] = $ruc;
		}
		if ($email !== '') {
			$cliente['email'] = $email;
		}
		if ($tipo === 2) {
			$cliente['pais'] = $pais;
		}
		$total = $cantidad * $precio;
		$payload = [
			'tipoDocumento' => $tipo,
			'establecimiento' => $config['establecimiento'] ?: '001',
			'punto' => $config['punto'] ?: '001',
			'moneda' => $config['moneda'] ?: 'PYG',
			'idempotency_key' => 'de-' . $tipo . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)),
			'cliente' => $cliente,
			'items' => [[
				'descripcion' => $descripcion,
				'cantidad' => $cantidad,
				'precioUnitario' => $precio,
				'unidadMedida' => 77,
				'ivaTipo' => 1,
				'iva' => $iva,
				'ivaProporcion' => 100,
			]],
		];
		if ($tipo === 2) {
			$payload['detalleTransporte'] = [
				'condicionNegociacion' => trim((string)($_POST['condicionNegociacion'] ?? 'FCA')) ?: 'FCA',
				'paisDestino' => $pais,
			];
		} elseif ($tipo === 4) {
			$payload['autoFactura'] = ['tipo' => 1];
		} elseif (in_array($tipo, [5, 6], true)) {
			$payload['notaCreditoDebito'] = ['motivo' => max(1, (int)($_POST['motivo_nota'] ?? 1))];
			$cdc = trim((string)($_POST['cdc'] ?? ''));
			if ($cdc !== '') {
				$payload['documentoAsociado'] = ['cdc' => $cdc];
			} else {
				$payload['documentoAsociado'] = [
					'timbrado' => trim((string)$_POST['timbrado_asoc']),
					'numero' => (int)$_POST['numero_asoc'],
				];
			}
		} elseif ($tipo === 7) {
			$payload['remision'] = ['motivo' => max(1, (int)($_POST['motivo_remision'] ?? 1))];
		}
		if ($tipo !== 7) {
			$payload['condicion'] = [
				'tipo' => 1,
				'entregas' => [[
					'tipo' => 1,
					'monto' => (string)round($total),
					'moneda' => $payload['moneda'],
					'cambio' => 0,
				]],
			];
		}

		$resultado = sifen_enviar($conection, '/api/v1/documents', $payload);
		$aviso = $resultado['msg'];
		$avisoOk = !empty($resultado['ok']);
		if ($avisoOk) {
			$doc = $resultado['doc'] ?? [];
			sifen_guardar($conection, [
				'noventa' => 0,
				'document_id' => $doc['id'] ?? null,
				'status' => $doc['status'] ?? 'queued',
				'cdc' => $doc['cdc'] ?? null,
				'idempotency_key' => $payload['idempotency_key'],
				'numero' => $doc['number'] ?? null,
				'error_message' => $doc['error_message'] ?? null,
				'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
				'response_raw' => $resultado['raw'] ?? '',
			]);
		}
	}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Otro comprobante electrónico</title>
</head>
<body>
	<?php include "includes/header.php"; ?>
	<section id="container">
		<h1><i class="fas fa-file-alt"></i> Otro comprobante electrónico</h1>
		<p>Exportación, autofactura, nota de crédito, nota de débito o remisión. La factura de venta sigue saliendo desde Nueva venta.</p>
		<?php if ($aviso !== '') { ?>
			<p style="color: <?= $avisoOk ? '#23922d' : '#c0392b'; ?>;"><?= htmlspecialchars($aviso); ?></p>
		<?php } ?>
		<form method="post" class="containerPerfil" style="display:block;" id="formOtroDe">
			<label>Tipo</label>
			<select name="tipoDocumento" id="tipoDocumento" required>
				<option value="2">2 · Exportación</option>
				<option value="4">4 · Autofactura</option>
				<option value="5">5 · Nota de crédito</option>
				<option value="6">6 · Nota de débito</option>
				<option value="7">7 · Remisión</option>
			</select>
			<label>Razón social</label>
			<input type="text" name="razonSocial" maxlength="255" required>
			<label>RUC</label>
			<input type="text" name="ruc" maxlength="20">
			<label>Correo</label>
			<input type="email" name="email">
			<div class="bloque-tipo bloque-2">
				<label>País destino (distinto de PRY)</label>
				<input type="text" name="pais" maxlength="3" value="URY">
				<label>Condición de negociación</label>
				<input type="text" name="condicionNegociacion" value="FCA" maxlength="10">
			</div>
			<div class="bloque-tipo bloque-5 bloque-6" style="display:none;">
				<label>Motivo de la nota</label>
				<input type="number" name="motivo_nota" min="1" value="1">
				<label>CDC del comprobante asociado</label>
				<input type="text" name="cdc" maxlength="44">
				<label>O timbrado asociado</label>
				<input type="text" name="timbrado_asoc">
				<label>Número asociado</label>
				<input type="number" name="numero_asoc" min="1">
			</div>
			<div class="bloque-tipo bloque-7" style="display:none;">
				<label>Motivo de la remisión</label>
				<input type="number" name="motivo_remision" min="1" value="1">
			</div>
			<label>Descripción</label>
			<input type="text" name="descripcion" required>
			<label>Cantidad</label>
			<input type="number" name="cantidad" min="0.01" step="any" value="1" required>
			<label>Precio unitario</label>
			<input type="number" name="precio" min="0" step="any" value="0" required>
			<label>IVA</label>
			<select name="iva">
				<?php foreach ([10, 5, 0] as $tasa) { ?>
					<option value="<?= $tasa; ?>" <?= ((int)$config['iva_por_defecto'] === $tasa) ? 'selected' : ''; ?>><?= $tasa; ?> %</option>
				<?php } ?>
			</select>
			<button type="submit" class="btn_new">Emitir</button>
		</form>
	</section>
	<?php include "includes/footer.php"; ?>
	<script>
		function mostrarBloquesTipo() {
			var tipo = $('#tipoDocumento').val();
			$('.bloque-tipo').hide();
			$('.bloque-' + tipo).show();
		}
		$('#tipoDocumento').on('change', mostrarBloquesTipo);
		mostrarBloquesTipo();
	</script>
</body>
</html>
