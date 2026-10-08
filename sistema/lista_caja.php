<?php
session_start(); 

include "../conexion.php";

$user = $_SESSION['idUser'];

$query_conf = mysqli_query($conection,"SELECT * FROM configuracion ");
$result_conf = mysqli_num_rows($query_conf);
if ($result_conf > 0) {
			$data_conf = mysqli_fetch_assoc($query_conf);
			$moneda = $data_conf['moneda'];
		}

//$query = mysqli_query($conection,"SELECT * FROM caja WHERE usuario = $user AND status = 1");
$query = mysqli_query($conection,"SELECT * FROM caja WHERE status = 1");
$result = mysqli_num_rows($query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"?>
	<title>Lista de caja</title>
</head>
<body>
	<?php include "includes/header.php"?>
	<section id="container">

		<h1><i class="fa fa-file-alt fa-w-12"></i> Lista de caja</h1>

		<!--<form action="" method="" class="form_search">
			<input type="text" name="busquedaEgresos" id="busquedaEgresos" placeholder="Buscar">	
		</form>-->
		<?php 
				if ($result > 0) {
			 		$data = mysqli_fetch_assoc($query);
			 		$id = $data['id'];
			 		// Obtener totales por método de pago
			 		$query_dash = mysqli_query($conection,"
			 			SELECT 
			 				(SELECT IFNULL(SUM(totalventa),0) FROM venta WHERE caja = $id AND status = 1 AND (tipo_pago_detalle IS NULL OR tipo_pago_detalle = 1 OR tipo_pago_detalle = '1')) as efectivo,
			 				(SELECT IFNULL(SUM(totalventa),0) FROM venta WHERE caja = $id AND status = 1 AND (tipo_pago_detalle = 2 OR tipo_pago_detalle = '2')) as transferencia,
			 				(SELECT IFNULL(SUM(totalventa),0) FROM venta WHERE caja = $id AND status = 1 AND (tipo_pago_detalle = 4 OR tipo_pago_detalle = '4')) as qr,
			 				(SELECT IFNULL(SUM(totalventa),0) FROM venta WHERE caja = $id AND status = 1 AND (tipo_pago_detalle = 5 OR tipo_pago_detalle = '5')) as tarjeta,
			 				(SELECT IFNULL(SUM(totalventa),0) FROM venta WHERE caja = $id AND status = 3) as credito,
			 				(SELECT IFNULL(SUM(cantidad),0) FROM detalle_recibo WHERE caja = $id) as abonos,
			 				(SELECT IFNULL(SUM(cantidad),0) FROM egresos WHERE caja = $id) as egreso
			 		");
						$result_das = mysqli_num_rows($query_dash);
						if ($result_das > 0) {
							$data_dash = mysqli_fetch_assoc($query_dash);
							// Calcular ventas totales (todos los métodos de pago)
							$ventas_totales = $data_dash['efectivo'] + $data_dash['transferencia'] + $data_dash['qr'] + $data_dash['tarjeta'];
							// Mantener compatibilidad
							$data_dash['ventas'] = $ventas_totales;
		}
		?>
		<center><table style="width: 25%;">
			<thead>
				<th colspan="3" class="textcenter">Cierre de caja</th>
			</thead>
				<tbody>
					<tr>
					<td class="textright">Inicio</td>
					<td class="textright"><?= $moneda;?></td>
					<td id="td_inicio"><?= formatCant($data['inicio']);?>
						<?php if ($_SESSION['rol'] == 1) { ?>
						<a href="#" onclick="event.preventDefault(); editarInicioCaja(<?= $data['id']; ?>, <?= $data['inicio']; ?>);" style="color:#3498db; margin-left:8px;" title="Editar monto inicial"><i class="fas fa-edit"></i></a>
						<?php } ?>
					</td>
				</tr>
					<tr>
						<td class="textright">Ventas en Efectivo</td>
						<td class="textright"><?= $moneda;?></td>
						<td id=""><?= formatCant($data_dash['efectivo']);?></td>
					</tr>
					<tr>
						<td class="textright">Ventas con Transferencia</td>
						<td class="textright"><?= $moneda;?></td>
						<td id=""><?= formatCant($data_dash['transferencia']);?></td>
					</tr>
					<tr>
						<td class="textright">Ventas con QR</td>
						<td class="textright"><?= $moneda;?></td>
						<td id=""><?= formatCant($data_dash['qr']);?></td>
					</tr>
					<tr>
						<td class="textright">Ventas con Tarjeta</td>
						<td class="textright"><?= $moneda;?></td>
						<td id=""><?= formatCant($data_dash['tarjeta']);?></td>
					</tr>
					<tr>
						<td class="textright">Abonos</td>
						<td class="textright"><?= $moneda;?></td>
						<td id=""><?= formatCant($data_dash['abonos']);?></td>
					</tr>
					<tr>
						<td class="textright">Créditos</td>
						<td class="textright"><?= $moneda;?></td>
						<td id=""><?= formatCant($data_dash['credito']);?></td>
					</tr>
					<tr>
						<td class="textright">Egresos</td>
						<td class="textright"><?= $moneda;?></td>
						<td id=""><?= formatCant($data_dash['egreso']);?></td>
					</tr>
<!-- <tr style="font-weight: bold;">
    <td class="textright">Total efectivo</td>
    <td class="textright"><?= $moneda;?></td>
    <td id=""><?= formatCant($data['inicio'] + $data_dash['efectivo'] + $data_dash['abonos'] - $data_dash['egreso']);?></td>
</tr> -->
					<tr style="font-weight: bold; background-color: #f0f0f0; border-top: 2px solid #333;">
						<td class="textright">TOTAL GENERAL</td>
						<td class="textright"><?= $moneda;?></td>
						<td id=""><?= formatCant($data['inicio'] + $data_dash['efectivo'] + $data_dash['transferencia'] + $data_dash['qr'] + $data_dash['tarjeta'] + $data_dash['abonos'] - $data_dash['egreso']);?></td>
					</tr>
				</tbody>
				<tfoot>
					<?php if ($data['usuario'] == $_SESSION['idUser']) { ?>
						<tr>
						<td colspan="3" class="textcenter">
							<form name="form_cierre_caja" id="form_cierre_caja" class="form_cuentas_cobrar" onsubmit="event.preventDefault(); cerrarCaja();">
								<input type="hidden" name="action" value="cerrarCaja">
								<input type="hidden" name="id_caja" id="id_caja" value="<?= $data['id'];?>">
								<input type="hidden" name="cant_inicio" id="cant_inicio" value="<?=$data['inicio'];?>">
								<input type="hidden" name="cant_ventas" id="cant_ventas" value="<?=$data_dash['ventas'];?>">
								<input type="hidden" name="cant_efectivo" id="cant_efectivo" value="<?=$data_dash['efectivo'];?>">
								<input type="hidden" name="cant_transferencia" id="cant_transferencia" value="<?=$data_dash['transferencia'];?>">
								<input type="hidden" name="cant_qr" id="cant_qr" value="<?=$data_dash['qr'];?>">
								<input type="hidden" name="cant_tarjeta" id="cant_tarjeta" value="<?=$data_dash['tarjeta'];?>">
								<input type="hidden" name="cant_abonos" id="cant_abonos" value="<?=$data_dash['abonos'];?>">
								<input type="hidden" name="cant_creditos" id="cant_creditos" value="<?=$data_dash['credito'];?>">
								<input type="hidden" name="cant_egreso" id="cant_egreso" value="<?=$data_dash['egreso'];?>">
								<input type="hidden" name="total_cierre" id="total_cierre" value="<?= $data['inicio'] + $data_dash['efectivo'] + $data_dash['transferencia'] + $data_dash['qr'] + $data_dash['tarjeta'] + $data_dash['abonos'] - $data_dash['egreso'];?>">
								<div class="alert alertAddProduct"></div>
								<button type="submit" class="btn_new" title="Cerrar caja">Cerrar caja</button>

							</form>
						</td>
						</tr>

				<?php } ?>
				</tfoot>
		</table>
		</center>
	<?php }else{ ?><a href="#" class="btn_new" id="abrir_caja"><i class="fas fa-plus"></i> Abrir caja</a><?php } ?>
	<form action="buscar_cliente.php" method="post" class="form_search">
			<input type="date" name="busquedaCaja" id="busquedaCaja" placeholder="Buscar fecha">	
		</form>
		<div style="width: 120px; margin-bottom: 5px">
						
						<p>
							<strong>Mostrar por : </strong>
							<select name="cantidad_mostrar_caja" id="cantidad_mostrar_caja">
								<option value="10">10</option>
								<option value="25">25</option>
								<option value="50">50</option>
								<option value="100">100</option>
							</select>
						</p>

					</div>
		<div class="containerTable" id="listaCaja">
			<!--CONTENIDO AJAX-->
		</div>
		<div class="paginador" id="paginadoCaja">
			<!--CONTENIDO AJAX-->
		</div>
	</section>


		<?php include "includes/footer.php"?>

<script>
function editarInicioCaja(idCaja, montoActual) {
	$('.bodyModal').html(
		'<form id="form_edit_inicio_caja" style="width: 400px; padding: 20px; background: #fff; border-radius: 10px;">' +
		'<h1><i class="fas fa-cash-register" style="font-size: 35pt;"></i><br>Editar Monto Inicial</h1><br>' +
		'<input type="hidden" name="action" value="editarInicioCaja">' +
		'<input type="hidden" name="id_caja" value="' + idCaja + '">' +
		'<label>Nuevo Monto Inicial:</label>' +
		'<input type="number" name="nuevo_inicio" id="nuevo_inicio" value="' + montoActual + '" required min="0" step="any" style="width:100%; padding:10px; font-size:1.2em;"><br>' +
		'<div class="alert alertAddProduct"></div>' +
		'<div style="display:flex; justify-content:space-between; margin-top:15px;">' +
		'<button type="button" class="btn_ok" onclick="coloseModal();"><i class="fas fa-ban"></i> Cancelar</button>' +
		'<button type="submit" class="btn_save"><i class="fas fa-save"></i> Guardar</button>' +
		'</div>' +
		'</form>'
	);
	$('.modal').fadeIn();
}

$(document).on('submit', '#form_edit_inicio_caja', function(e) {
	e.preventDefault();
	$.ajax({
		url: 'ajax.php',
		type: 'POST',
		data: $(this).serialize(),
		success: function(response) {
			try {
				var res = JSON.parse(response);
				if (res.cod == '00') {
					$('.alertAddProduct').html('<p style="color:green;">' + res.msg + '</p>');
					setTimeout(function() {
						coloseModal();
						location.reload();
					}, 1200);
				} else {
					$('.alertAddProduct').html('<p style="color:red;">' + res.msg + '</p>');
				}
			} catch(e) {
				$('.alertAddProduct').html('<p style="color:red;">Error en la respuesta del servidor</p>');
			}
		}
	});
});
</script>

</body>

</html>