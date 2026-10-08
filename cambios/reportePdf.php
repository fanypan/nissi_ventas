<?php 
	$subtotal 	= 0;
	$iva 	 	= 0;
	$impuesto 	= 0;
	$tl_sniva   = 0;
	$total 		= 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Reporte de Ventas</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		
		body {
			font-family: Arial, sans-serif;
			font-size: 10px;
			padding: 20px;
		}
		
		.header-container {
			width: 100%;
			margin-bottom: 40px;
			border-bottom: 2px solid #5dade2;
			padding-bottom: 20px;
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
		}
		
		.header-left {
			flex: 1;
			text-align: center;
		}
		
		.header-left h1 {
			font-size: 16px;
			color: #2980b9;
			margin: 0;
			padding: 0;
			line-height: 1.2;
			margin-bottom: 5px;
		}
		
		.header-left p {
			font-size: 9px;
			color: #555;
			margin: 2px 0;
			padding: 0;
			line-height: 1.3;
		}
		
		.header-right {
			text-align: right;
			font-size: 9px;
			color: #333;
			line-height: 1.4;
		}
		
		.header-right strong {
			display: block;
			margin-bottom: 3px;
		}
		
		table {
			width: 100%;
			border-collapse: collapse;
		}
		
		.ventas-table {
			margin-bottom: 20px;
		}
		
		.ventas-table thead {
			background-color: #5dade2;
			color: white;
		}
		
		.ventas-table thead th {
			padding: 10px 5px;
			font-size: 9px;
			text-align: left;
			border: 1px solid #3498db;
			font-weight: bold;
		}
		
		.ventas-table tbody td {
			padding: 8px 5px;
			font-size: 8px;
			border: 1px solid #ddd;
			vertical-align: top;
		}
		
		.ventas-table tbody tr:nth-child(even) {
			background-color: #f0f8ff;
		}
		
		.ventas-table tfoot {
			background-color: #5dade2;
			color: white;
		}
		
		.ventas-table tfoot td {
			padding: 12px 5px;
			font-size: 11px;
			font-weight: bold;
			border: 1px solid #3498db;
		}
		
		.col-no { width: 6%; }
		.col-fecha { width: 13%; }
		.col-cliente { width: 18%; }
		.col-vendedor { width: 12%; }
		.col-pago { width: 28%; }
		.col-estado { width: 7%; text-align: center; }
		.col-total { width: 16%; text-align: right; }
		
		.resumen {
			margin-top: 20px;
			padding: 15px;
			background-color: #ecf0f1;
			border: 1px solid #bdc3c7;
		}
		
		.resumen table td {
			padding: 6px;
			font-size: 10px;
			border: none;
		}
		
		.resumen-valor {
			text-align: right;
			font-weight: bold;
		}
	</style>
</head>
<body>

	<!-- CABECERA -->
	<?php
		if($result_conf > 0){
			$moned = $configuracion['moneda'];
		}
	?>
	
	<table style="width: 100%; margin-bottom: 40px; border-bottom: 2px solid #5dade2;">
		<tr>
			<td style="width: 100%; text-align: right; font-size: 10px; padding: 5px 10px 15px 10px; vertical-align: top;">
				<?php echo date('d/m/Y H:i'); ?>
			</td>
		</tr>
		<tr>
			<td style="width: 100%; text-align: center; padding: 10px;">
				<h1 style="font-size: 18px; color: #2980b9; margin: 0; padding: 0; font-weight: bold;"><?php echo strtoupper($configuracion['nombre']); ?></h1>
			</td>
		</tr>
	</table>

	<!-- TABLA DE VENTAS -->
	<table class="ventas-table">
		<thead>
			<tr>
				<th class="col-no">No.</th>
				<th class="col-fecha">Fecha</th>
				<th class="col-cliente">Cliente</th>
				<th class="col-vendedor">Vendedor</th>
				<th class="col-pago">Método de Pago</th>
				<th class="col-estado">Est.</th>
				<th class="col-total">Total</th>
			</tr>
		</thead>
		<tbody>
			<?php
			if ($result > 0) {
				$ventas_totales = 0;
				$total_efectivo = 0;
				$total_transferencia = 0;
				$contador_ventas = 0;
				
				while ($data = mysqli_fetch_array($query)) {
					$contador_ventas++;
					
					$estatus_icon = '';
					$estatus_color = '#95a5a6';
					if ($data['status'] == 1) {
						$estatus_icon = 'OK';
						$estatus_color = '#27ae60';
					} elseif ($data['status'] == 4) {
						$estatus_icon = 'AB';
						$estatus_color = '#f39c12';
					} elseif ($data['status'] == 2) {
						$estatus_icon = 'X';
						$estatus_color = '#e74c3c';
					} else {
						$estatus_icon = '-';
					}
					
					$totalfactura = $moned.' '.number_format($data["totalventa"], 2);
					if ($data["totalventa"] == 0) {
						$totalfactura = $moned.' '.number_format($data["abono"], 2);
					}
					
					$nofactura = $data["noventa"];
					$fecha = $data["fecha"];
					$cliente = $data["cliente"];
					$vendedor = $data["vendedor"];
					$abono = $data["abono"];
					$totalfact = $data["totalventa"];
					$ventas_totales = $ventas_totales + $totalfact + $abono;
					
					$query_trans_existe = mysqli_query($conection, "SELECT t.numero_referencia, b.nombre as banco 
						FROM transferencias t 
						LEFT JOIN bancos b ON t.banco_id = b.id 
						WHERE t.noventa = $nofactura LIMIT 1");
					$result_trans = mysqli_fetch_assoc($query_trans_existe);
					$es_transferencia = $result_trans !== null;
					
					$metodo_pago_html = '';
					if ($es_transferencia) {
						$total_transferencia += $totalfact + $abono;
						$ref = $result_trans['numero_referencia'];
						$banco = !empty($result_trans['banco']) ? $result_trans['banco'] : 'N/A';
						$metodo_pago_html = '<strong style="color:#3498db;">TRANSFERENCIA</strong><br>';
						$metodo_pago_html .= '<span style="font-size:7px;color:#555;">Banco: '.$banco.'</span><br>';
						$metodo_pago_html .= '<span style="font-size:7px;color:#666;">Ref: '.$ref.'</span>';
					} else {
						$total_efectivo += $totalfact + $abono;
						$metodo_pago_html = '<strong style="color:#27ae60;">EFECTIVO</strong>';
					}
			?>
			<tr>
				<td class="col-no"><?php echo $nofactura; ?></td>
				<td class="col-fecha"><?php echo date('d/m/Y H:i', strtotime($fecha)); ?></td>
				<td class="col-cliente"><?php echo $cliente; ?></td>
				<td class="col-vendedor"><?php echo $vendedor; ?></td>
				<td class="col-pago"><?php echo $metodo_pago_html; ?></td>
				<td class="col-estado">
					<strong style="color:<?php echo $estatus_color; ?>;"><?php echo $estatus_icon; ?></strong>
				</td>
				<td class="col-total"><?php echo $totalfactura; ?></td>
			</tr>
			<?php
				}
			}
			?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="6" style="text-align: right;">TOTAL VENTAS</td>
				<td style="text-align:right;"><?php echo $moned.' '.number_format($ventas_totales, 2); ?></td>
			</tr>
		</tfoot>
	</table>
	
	<!-- RESUMEN DE PAGOS -->
	<?php if (isset($total_efectivo) || isset($total_transferencia)): ?>
	<div class="resumen">
		<table>
			<tr>
				<td style="width: 60%;"><strong>Total de ventas:</strong></td>
				<td class="resumen-valor" style="width: 40%;"><?php echo $contador_ventas; ?> ventas</td>
			</tr>
			<tr>
				<td><strong>Total en Efectivo:</strong></td>
				<td class="resumen-valor"><?php echo $moned.' '.number_format($total_efectivo, 2); ?></td>
			</tr>
			<tr>
				<td><strong>Total en Transferencias:</strong></td>
				<td class="resumen-valor"><?php echo $moned.' '.number_format($total_transferencia, 2); ?></td>
			</tr>
		</table>
	</div>
	<?php endif; ?>

</body>
</html>