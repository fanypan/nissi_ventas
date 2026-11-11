<?php

ensure_session();

if (empty($_SESSION['rol']) || (int)$_SESSION['rol'] !== 1) {
	header('Location: index.php');
	exit;
}

include "../conexion.php";

$fe = [
	'api_url' => '',
	'ambiente' => 'test',
	'establecimiento' => '001',
	'punto' => '001',
	'moneda' => 'PYG',
	'iva_por_defecto' => 10,
	'activo' => 0,
	'timbrado' => '',
	'api_token' => '',
];
$queryFe = mysqli_query($conection, "SELECT * FROM config_facturacion_electronica ORDER BY id ASC LIMIT 1");
if ($queryFe && mysqli_num_rows($queryFe) > 0) {
	$fe = mysqli_fetch_assoc($queryFe);
}
$tokenConfigurado = !empty($fe['api_token']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Configuración factura electrónica</title>
</head>
<body>
	<?php include "includes/header.php"; ?>
	<section id="container">
		<div class="divInfoSistema">
			<div>
				<h1 class="titlePanelControl">Configuración factura electrónica</h1>
			</div>
			<div class="containerPerfil">
				<div class="containerDataEmpresa" style="width: 100%;">
					<form action="" method="post" name="frmFacturacionElectronica" id="frmFacturacionElectronica">
						<input type="hidden" name="action" value="updateConfigFE">
						<div>
							<label>URL de la API:</label>
							<input type="url" name="fe_api_url" id="fe_api_url" placeholder="https://api.sifen.arandutech.com" value="<?= htmlspecialchars($fe['api_url'] ?? ''); ?>">
						</div>
						<div>
							<label>Token:</label>
							<input type="password" name="fe_api_token" id="fe_api_token" placeholder="<?= $tokenConfigurado ? 'Token guardado. Dejar vacío para conservarlo.' : 'sk_test_… o sk_live_…'; ?>" autocomplete="new-password">
						</div>
						<div>
							<label>Ambiente:</label>
							<select name="fe_ambiente" id="fe_ambiente">
								<option value="test" <?= (($fe['ambiente'] ?? '') === 'test') ? 'selected' : ''; ?>>Homologación (test)</option>
								<option value="produccion" <?= (($fe['ambiente'] ?? '') === 'produccion') ? 'selected' : ''; ?>>Producción</option>
							</select>
						</div>
						<div>
							<label>Establecimiento:</label>
							<input type="text" name="fe_establecimiento" id="fe_establecimiento" value="<?= htmlspecialchars($fe['establecimiento'] ?? '001'); ?>">
						</div>
						<div>
							<label>Punto de expedición:</label>
							<input type="text" name="fe_punto" id="fe_punto" value="<?= htmlspecialchars($fe['punto'] ?? '001'); ?>">
						</div>
						<div>
							<label>Timbrado:</label>
							<input type="text" name="fe_timbrado" id="fe_timbrado" value="<?= htmlspecialchars($fe['timbrado'] ?? ''); ?>">
						</div>
						<div>
							<label>Moneda:</label>
							<input type="text" name="fe_moneda" id="fe_moneda" value="<?= htmlspecialchars($fe['moneda'] ?? 'PYG'); ?>">
						</div>
						<div>
							<label>IVA por defecto (%):</label>
							<select name="fe_iva" id="fe_iva">
								<?php foreach ([10, 5, 0] as $tasa) { ?>
									<option value="<?= $tasa; ?>" <?= ((int)($fe['iva_por_defecto'] ?? 10) === $tasa) ? 'selected' : ''; ?>><?= $tasa; ?></option>
								<?php } ?>
							</select>
						</div>
						<div>
							<label>
								<input type="checkbox" name="fe_activo" id="fe_activo" value="1" <?= !empty($fe['activo']) ? 'checked' : ''; ?>>
								Activar emisión al elegir Factura
							</label>
						</div>
						<div class="alertFormFE" style="display: none; text-align: center;"></div>
						<div>
							<button type="submit" class="btn_save"><i class="far fa-save fa-lg"></i> Guardar facturación</button>
							<button type="button" class="btn_save" id="btnProbarFE"><i class="fas fa-check-circle"></i> Probar conexión</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</section>

	<?php include "includes/footer.php"; ?>

	<script>
		$('#frmFacturacionElectronica').submit(function (e) {
			e.preventDefault();
			var datos = $(this).serialize();
			if (!$('#fe_activo').is(':checked')) {
				datos += '&fe_activo=0';
			}
			$.ajax({
				url: 'ajax.php',
				type: 'POST',
				data: datos,
				success: function (response) {
					var info = {};
					try { info = JSON.parse(response); } catch (err) { info = { msg: 'Respuesta inválida del servidor.' }; }
					var color = info.cod === '00' ? '#23922d' : 'red';
					$('.alertFormFE').html('<p style="color:' + color + ';">' + (info.msg || 'Sin respuesta') + '</p>').slideDown();
				}
			});
		});

		$('#btnProbarFE').click(function (e) {
			e.preventDefault();
			$.ajax({
				url: 'ajax.php',
				type: 'POST',
				data: {
					action: 'probarConexionFE',
					fe_api_url: $('#fe_api_url').val(),
					fe_api_token: $('#fe_api_token').val()
				},
				success: function (response) {
					var info = {};
					try { info = JSON.parse(response); } catch (err) { info = { msg: 'Respuesta inválida del servidor.' }; }
					var color = info.cod === '00' ? '#23922d' : 'red';
					$('.alertFormFE').html('<p style="color:' + color + ';">' + (info.msg || 'Sin respuesta') + '</p>').slideDown();
				}
			});
		});
	</script>
</body>
</html>
