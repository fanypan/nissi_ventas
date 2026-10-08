<?php 
	$subtotal 	= 0;
	$iva 	 	= 0;
	$impuesto 	= 0;
	$tl_sniva   = 0;
	$total 		= 0;
	
	// ============================================
	// FUNCIÓN PARA OBTENER TIPO DE PAGO
	// ============================================
	function obtenerTipoPagoHTML($conection, $tipo_pago_detalle, $noventa) {
		$tipos = array(
			1 => array('nombre' => 'EFECTIVO', 'color' => '#27ae60'),
			2 => array('nombre' => 'TRANSFERENCIA', 'color' => '#3498db'),
			3 => array('nombre' => 'CRÉDITO', 'color' => '#e67e22'),
			4 => array('nombre' => 'QR', 'color' => '#9b59b6'),
			5 => array('nombre' => 'TARJETA', 'color' => '#e74c3c')
		);
		
		$tipoPago = isset($tipos[$tipo_pago_detalle]) ? $tipos[$tipo_pago_detalle] : $tipos[1];
		$metodo_pago_html = '<strong style="color:'.$tipoPago['color'].';">'.$tipoPago['nombre'].'</strong>';
		
		// Si es transferencia, obtener banco
		if ($tipo_pago_detalle == 2) {
			$query_trans = mysqli_query($conection, "SELECT t.numero_referencia, b.nombre as banco 
													  FROM transferencias t
													  LEFT JOIN bancos b ON t.banco_id = b.id
													  WHERE t.noventa = $noventa LIMIT 1");
			if ($query_trans && mysqli_num_rows($query_trans) > 0) {
				$trans = mysqli_fetch_assoc($query_trans);
				$banco = !empty($trans['banco']) ? $trans['banco'] : 'N/A';
				$ref = $trans['numero_referencia'];
				$metodo_pago_html .= '<br><span style="font-size:7px;color:#555;">Banco: '.$banco.'</span>';
				$metodo_pago_html .= '<br><span style="font-size:7px;color:#666;">Ref: '.$ref.'</span>';
			}
		}
		
		// Si es QR, obtener tipo de QR
		if ($tipo_pago_detalle == 4) {
			$query_qr = mysqli_query($conection, "SELECT tipo_qr, numero_referencia, numero_celular 
												   FROM pagos_qr 
												   WHERE noventa = $noventa LIMIT 1");
			if ($query_qr && mysqli_num_rows($query_qr) > 0) {
				$qr = mysqli_fetch_assoc($query_qr);
				$tipoQR = str_replace('_', ' ', ucwords(str_replace('_', ' ', $qr['tipo_qr'])));
				$metodo_pago_html .= '<br><span style="font-size:7px;color:#555;">'.$tipoQR.'</span>';
				$metodo_pago_html .= '<br><span style="font-size:7px;color:#666;">Ref: '.$qr['numero_referencia'].'</span>';
				if (!empty($qr['numero_celular'])) {
					$metodo_pago_html .= '<br><span style="font-size:7px;color:#666;">Cel: '.$qr['numero_celular'].'</span>';
				}
			}
		}
		
		// Si es tarjeta, obtener tipo y banco
		if ($tipo_pago_detalle == 5) {
			$query_tarjeta = mysqli_query($conection, "SELECT tipo_tarjeta, banco, ultimos_digitos 
														FROM pagos_tarjeta 
														WHERE noventa = $noventa LIMIT 1");
			if ($query_tarjeta && mysqli_num_rows($query_tarjeta) > 0) {
				$tarjeta = mysqli_fetch_assoc($query_tarjeta);
				$tipoTarjeta = ucfirst($tarjeta['tipo_tarjeta']);
				$metodo_pago_html .= '<br><span style="font-size:7px;color:#555;">'.$tipoTarjeta.' '.$tarjeta['banco'].'</span>';
				$metodo_pago_html .= '<br><span style="font-size:7px;color:#666;">****'.$tarjeta['ultimos_digitos'].'</span>';
			}
		}
		
		return $metodo_pago_html;
	}
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
			border-radius: 5px;
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
		
		.resumen-titulo {
			font-size: 12px;
			font-weight: bold;
			color: #2c3e50;
			margin-bottom: 10px;
			border-bottom: 2px solid #3498db;
			padding-bottom: 5px;
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
			// Inicializar contadores globalmente para evitar errores si no hay resultados
			$ventas_totales = 0;
			$total_efectivo = 0;
			$total_transferencia = 0;
			$total_qr = 0;
			$total_tarjeta = 0;
			$total_credito = 0;
			$contador_ventas = 0;
			$contador_efectivo = 0;
			$contador_transferencia = 0;
			$contador_qr = 0;
			$contador_tarjeta = 0;
			$contador_credito = 0;

			if ($result > 0) {
				while ($data = mysqli_fetch_array($query)) {
					$contador_ventas++;
					
					// Determinar estado
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
					} elseif ($data['status'] == 3) {
						$estatus_icon = 'CR';
						$estatus_color = '#e67e22';
					} else {
						$estatus_icon = '-';
					}
					
					// Calcular total
					$totalfactura = $moned.' '.formatCant($data["totalventa"]);
					if ($data["totalventa"] == 0) {
						$totalfactura = $moned.' '.formatCant($data["abono"]);
					}
					
					$nofactura = $data["noventa"];
					$fecha = $data["fecha"];
					$cliente = $data["cliente"];
					$vendedor = $data["vendedor"];
					$abono = $data["abono"];
					$totalfact = $data["totalventa"];
					$tipo_pago = $data["tipo_pago_detalle"];
					
					$ventas_totales += $totalfact + $abono;
					
					// Obtener método de pago con detalles
					$metodo_pago_html = obtenerTipoPagoHTML($conection, $tipo_pago, $nofactura);
					
					// Acumular por tipo de pago
					$monto_operacion = $totalfact + $abono;
					
					switch($tipo_pago) {
						case 1: // Efectivo
							$total_efectivo += $monto_operacion;
							$contador_efectivo++;
							break;
						case 2: // Transferencia
							$total_transferencia += $monto_operacion;
							$contador_transferencia++;
							break;
						case 3: // Crédito
							$total_credito += $monto_operacion;
							$contador_credito++;
							break;
						case 4: // QR
							$total_qr += $monto_operacion;
							$contador_qr++;
							break;
						case 5: // Tarjeta
							$total_tarjeta += $monto_operacion;
							$contador_tarjeta++;
							break;
						default: // Por defecto es efectivo
							$total_efectivo += $monto_operacion;
							$contador_efectivo++;
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
			<!-- DETALLE DE PRODUCTOS -->
			<tr>
				<td colspan="2"></td>
				<td colspan="5" style="padding: 0 5px 10px 5px;">
					<table style="width: 100%; border: 1px solid #eee; background: #fff;">
						<tr style="background: #f9f9f9;">
							<td style="width: 60%; font-weight: bold; border: none; padding: 2px 5px;">Producto</td>
							<td style="width: 15%; text-align: center; font-weight: bold; border: none; padding: 2px 5px;">Cant.</td>
							<td style="width: 25%; text-align: right; font-weight: bold; border: none; padding: 2px 5px;">Precio</td>
						</tr>
						<?php
						$query_det = mysqli_query($conection, "SELECT p.descripcion, dt.cantidad, dt.precio_venta 
																FROM detalleventa dt 
																INNER JOIN producto p ON dt.codproducto = p.codproducto 
																WHERE dt.noventa = $nofactura");
						while ($det = mysqli_fetch_assoc($query_det)) {
						?>
						<tr>
							<td style="border: none; border-bottom: 1px solid #f1f1f1; padding: 2px 5px;"><?php echo $det['descripcion']; ?></td>
							<td style="text-align: center; border: none; border-bottom: 1px solid #f1f1f1; padding: 2px 5px;"><?php echo $det['cantidad']; ?></td>
							<td style="text-align: right; border: none; border-bottom: 1px solid #f1f1f1; padding: 2px 5px;"><?php echo $moned.' '.formatCant($det['precio_venta']); ?></td>
						</tr>
						<?php } ?>
					</table>
				</td>
			</tr>
			<?php
				}
			} else {
				// Mostrar mensaje si no hay resultados
			?>
				<tr>
					<td colspan="7" style="text-align: center; padding: 20px; color: #7f8c8d; font-size: 12px;">
						No se encontraron ventas registradas para este periodo.
					</td>
				</tr>
			<?php
			}
			?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="6" style="text-align: right;">TOTAL VENTAS</td>
				<td style="text-align:right;"><?php echo $moned.' '.formatCant($ventas_totales); ?></td>
			</tr>
		</tfoot>
	</table>
	
	<!-- RESUMEN DETALLADO DE PAGOS -->
	<?php if (isset($total_efectivo) || isset($total_transferencia) || isset($total_qr) || isset($total_tarjeta) || isset($total_credito)): ?>
	<div class="resumen">
		<table style="width: 100%;">
			<thead>
				<tr>
					<th colspan="3" class="resumen-titulo" style="text-align: left; border-bottom: 2px solid #3498db; padding-bottom: 8px; margin-bottom: 15px; display: table-cell;">
						RESUMEN DE MÉTODOS DE PAGO
					</th>
				</tr>
			</thead>
			<tbody>
				<tr style="height: 10px;"><td colspan="3"></td></tr> <!-- Espaciador manual -->
				<tr>
					<td style="width: 50%; padding: 6px;"><strong>Total de ventas:</strong></td>
					<td class="resumen-valor" style="width: 25%; padding: 6px;"><?php echo $contador_ventas; ?> ventas</td>
					<td class="resumen-valor" style="width: 25%; padding: 6px;"><?php echo $moned.' '.formatCant($ventas_totales); ?></td>
				</tr>
				
				<?php if ($contador_efectivo > 0): ?>
				<tr>
					<td style="padding: 6px;"><strong style="color:#27ae60;">&bull; Efectivo:</strong></td>
					<td class="resumen-valor" style="padding: 6px;"><?php echo $contador_efectivo; ?> ventas</td>
					<td class="resumen-valor" style="padding: 6px;"><?php echo $moned.' '.formatCant($total_efectivo); ?></td>
				</tr>
				<?php endif; ?>
				
				<?php if ($contador_transferencia > 0): ?>
				<tr>
					<td style="padding: 6px;"><strong style="color:#3498db;">&bull; Transferencias:</strong></td>
					<td class="resumen-valor" style="padding: 6px;"><?php echo $contador_transferencia; ?> ventas</td>
					<td class="resumen-valor" style="padding: 6px;"><?php echo $moned.' '.formatCant($total_transferencia); ?></td>
				</tr>
				<?php endif; ?>
				
				<?php if ($contador_qr > 0): ?>
				<tr>
					<td style="padding: 6px;"><strong style="color:#9b59b6;">&bull; Pagos QR:</strong></td>
					<td class="resumen-valor" style="padding: 6px;"><?php echo $contador_qr; ?> ventas</td>
					<td class="resumen-valor" style="padding: 6px;"><?php echo $moned.' '.formatCant($total_qr); ?></td>
				</tr>
				<?php endif; ?>
				
				<?php if ($contador_tarjeta > 0): ?>
				<tr>
					<td style="padding: 6px;"><strong style="color:#e74c3c;">&bull; Tarjetas:</strong></td>
					<td class="resumen-valor" style="padding: 6px;"><?php echo $contador_tarjeta; ?> ventas</td>
					<td class="resumen-valor" style="padding: 6px;"><?php echo $moned.' '.formatCant($total_tarjeta); ?></td>
				</tr>
				<?php endif; ?>
				
				<?php if ($contador_credito > 0): ?>
				<tr>
					<td style="padding: 6px;"><strong style="color:#e67e22;">&bull; Créditos:</strong></td>
					<td class="resumen-valor" style="padding: 6px;"><?php echo $contador_credito; ?> ventas</td>
					<td class="resumen-valor" style="padding: 6px;"><?php echo $moned.' '.formatCant($total_credito); ?></td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

</body>
</html>