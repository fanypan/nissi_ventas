<?php
$subtotal 	= 0;
$iva 	 	= 0;
$impuesto 	= 0;
$tl_sniva   = 0;
$total 		= 0;
?>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Venta</title>
	<style>
		@import url('fonts/BrixSansRegular.css');
		@import url('fonts/BrixSansBlack.css');

		/* Reset básico */
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		/* Estilos base */
		body {
			font-family: Arial, sans-serif;
			font-size: 8pt;
			line-height: 1.3;
			background: white;
			color: #333;
		}

		p,
		span,
		td,
		th {
			font-family: Arial, sans-serif;
			font-size: 8pt;
			line-height: 1.3;
			margin: 0;
			padding: 0;
		}

		.h2 {
			font-size: 12pt;
			font-weight: bold;
			margin: 5pt 0 8pt 0;
			text-align: center;
			color: #000;
		}

		/* Contenedor principal */
		#page_pdf {
			width: 200pt;
			margin: 0 auto;
			background: white;
			padding: 8pt;
		}

		/* Tablas */
		table {
			width: 100%;
			border-collapse: collapse;
		}

		#factura_head {
			margin-bottom: 12pt;
		}

		/* Logo */
		.logo_factura {
			text-align: center;
			padding: 5pt 0 8pt 0;
		}

		.logo_factura img {
			max-width: 50pt;
			height: auto;
		}

		/* Información empresa */
		.info_empresa {
			text-align: center;
			padding: 4pt 0 6pt 0;
			border-bottom: 1pt solid #333;
			margin-bottom: 10pt;
			line-height: 1.4;
		}

		.info_empresa p {
			margin: 3pt 0;
			font-size: 7.5pt;
			color: #000;
		}


		/* Caja de información */
		.round {
			border: 1pt solid #333;
			padding: 6pt;
			margin: 5pt 0 10pt 0;
			background: #f9f9f9;
		}

		.round p {
			margin: 2pt 0;
			font-size: 7pt;
			color: #444;
		}

		.round strong {
			font-size: 8pt;
			font-weight: bold;
			display: block;
			margin: 3pt 0;
			color: #000;
		}

		/* Tabla de productos */
		#factura_detalle {
			margin: 8pt 0;
		}

		#factura_detalle thead th {
			background: #e8e8e8;
			padding: 4pt 2pt;
			font-size: 7pt;
			font-weight: bold;
			border-top: 1.5pt solid #333;
			border-bottom: 1.5pt solid #333;
		}

		#factura_detalle tbody td {
			padding: 3pt 2pt;
			font-size: 7pt;
			border-bottom: 0.5pt dotted #ccc;
			vertical-align: top;
		}

		.producto-descripcion {
			font-weight: bold;
			background: #f5f5f5;
			border-top: 1pt solid #ddd;
			padding: 4pt 2pt 2pt 2pt;
		}

		/* Totales */
		#detalle_totales {
			border-top: 2pt solid #333;
			background: #f8f8f8;
			margin-top: 8pt;
		}

		#detalle_totales td {
			padding: 3pt 2pt;
			font-size: 7pt;
		}

		#detalle_totales tr:last-child td {
			font-size: 9pt;
			font-weight: bold;
			border-top: 1.5pt solid #333;
			padding-top: 4pt;
			background: #e0e0e0;
		}

		#detalle_totales tr td {
			padding: 4pt 2pt;
			font-size: 8pt;
		}

		#detalle_totales tr:last-child td {
			font-size: 9pt;
			font-weight: bold;
			border-top: 1pt solid #000;
			background: #f1f1f1;
		}

		/* Mensaje final */
		.mensaje-final {
			margin-top: 15pt;
			text-align: center;
			border-top: 1pt dashed #666;
			padding: 8pt 4pt;
			background: #f9f9f9;
		}

		.label_gracias {
			font-weight: bold;
			text-align: center;
			margin: 3pt 0;
		}

		.label_gracias:first-child {
			font-size: 10pt;
		}

		.label_gracias:last-child {
			font-size: 7pt;
			color: #666;
			font-weight: normal;
		}

		/* Anulada */
		.anulada {
			position: absolute;
			left: 50%;
			top: 50%;
			transform: translateX(-50%) translateY(-50%);
			font-size: 20pt;
			color: red;
			font-weight: bold;
			z-index: 1000;
		}

		/* Alineaciones */
		.textright {
			text-align: right;
		}

		.textleft {
			text-align: left;
		}

		.textcenter {
			text-align: center;
		}
	</style>
</head>

<body>
	<?php echo $anulada; ?>
	<div id="page_pdf">
		<!-- Logo y información de empresa -->
		<table id="factura_head">
			<tr>
				<td class="logo_factura">
					<?php if (isset($configuracion['foto']) && !empty($configuracion['foto'])): ?>
						<img src="img/<?php echo $configuracion['foto']; ?>" alt="Logo">
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<br>

				<td class="info_empresa">
					<?php if ($result_config > 0):
						$iva = $configuracion['iva'];
						$moned = $configuracion['moneda'];
					?>
						<table style="width: 100%;">
							<tr>
								<td style="text-align: center; padding-bottom: 5pt;">
									<h2 style="font-size: 12pt; margin: 0;"><?php echo strtoupper($configuracion['nombre']); ?></h2>
								</td>
							</tr>
							<tr>
								<td style="text-align: center; padding-bottom: 2pt;">
									<p style="margin: 0;"><?php echo $configuracion['razon_social']; ?></p>
								</td>
							</tr>
							<tr>
								<td style="text-align: center; padding-bottom: 2pt;">
									<p style="margin: 0;">RUC: <?php echo $configuracion['nit']; ?></p>
								</td>
							</tr>
							<tr>
								<td style="text-align: center; padding-bottom: 5pt;">
									<p style="margin: 0;">Cel: <?php echo $configuracion['telefono']; ?></p>
								</td>
							</tr>
						</table>
					<?php endif; ?>
				</td>
			</tr>

			<!-- Información de la venta -->
			<tr>
				<td>
					<?php
					if ($venta['status'] == 1) {
						$tipo_pago = 'Contado';
					} elseif ($venta['status'] == 3) {
						$tipo_pago = 'Crédito';
					} else {
						$tipo_pago = 'Anulado';
					}

					if ($tipo_pago == 'Crédito') {
						date_default_timezone_set("America/Asuncion");
						$fecha = date('d-m-Y',                       strtotime($venta["fecha"]));

						$fecha_a_vencer = date('d-m-Y', strtotime($fecha . '+ 30 days'));
						$vence = '<p><strong>Vencimiento:</strong> ' . $fecha_a_vencer . '</p>';
					} else {
						$vence = '';
					}
					?>
					<div class="round">
						<br>
						 <br> <br> <br>

						<p><strong>Tipo de venta:</strong> <?php echo $tipo_pago; ?></p>
						<strong>No. Venta: <?php echo str_pad($venta['noventa'], 11, '0', STR_PAD_LEFT); ?></strong>
						<br>
						<p><strong>Fecha:</strong> <?php echo $venta['fechaF']; ?> - <strong>Hora:</strong> <?php echo $venta['horaF']; ?></p>
						<br>
						<p><strong>Vendedor:</strong> <?php echo $venta['vendedor']; ?></p>
						<br>
						<strong>Cliente: <?php echo $venta['nombre']; ?></strong>
						<br>
						<p><strong>RUC:</strong> <?php echo $venta['nit']; ?></p>
						<br>
						<?php echo $vence; ?>
					</div>
				</td>
			</tr>
		</table>

		<!-- Detalle de productos -->
		<table id="factura_detalle">
			<thead>
				<tr>
					<th class="textleft" width="35%">Código</th>
					<th class="textcenter" width="15%">Cant.</th>
					<th class="textright" width="25%">Precio</th>
					<th class="textright" width="25%">Total</th>
				</tr>
			</thead>
			<tbody id="detalle_productos">
				<?php
				if ($result_detalle > 0):
					while ($row = mysqli_fetch_assoc($query_productos)):
						$precio_venta = formatCant($row['precio_venta']);
						$precio_total_formatted = formatCant($row['precio_total']);
				?>
						<tr>
							<td colspan="4" class="producto-descripcion"><?php echo $row['descripcion']; ?></td>
						</tr>
						<tr>
							<td class="textleft"><?php echo $row['codigo']; ?></td>
							<td class="textcenter"><?php echo $row['cantidad']; ?></td>
							<td class="textright"><?php echo $moned . ' ' . $precio_venta; ?></td>
							<td class="textright"><?php echo $moned . ' ' . $precio_total_formatted; ?></td>
						</tr>
				<?php
						$precio_total = $row['precio_total'];
						$subtotal = round($subtotal + $precio_total, 2);
					endwhile;
				endif;

				$impuesto 	= round($subtotal * ($iva / 100), 2);
				$tl_sniva 	= round($subtotal - $impuesto, 2);
				$total 		= $tl_sniva + $impuesto;
				$descuento 	= formatCant($venta['descuento']);
				$total_final = $total - $venta['descuento'];
				?>
			</tbody>

			<tfoot id="detalle_totales">
				<tr>
					<td colspan="2"></td>
					<td class="textright">Subtotal:</td>
					<td class="textright"><?php echo $moned . ' ' . formatCant($total); ?></td>
				</tr>
				<?php if ($venta['descuento'] > 0): ?>
					<tr>
						<td colspan="2"></td>
						<td class="textright">Descuento:</td>
						<td class="textright"><?php echo $moned . ' ' . $descuento; ?></td>
					</tr>
				<?php endif; ?>
				<tr>
					<td colspan="2"></td>
					<td class="textright"><strong>TOTAL:</strong></td>
					<td class="textright"><strong><?php echo $moned . ' ' . formatCant($total_final); ?></strong></td>
				</tr>
			</tfoot>

		</table>

		<!-- Mensaje final -->
		<div class="mensaje-final">
			
			<div class="label_gracias">¡Gracias por su compra!</div>
			<div class="label_gracias">Revise su producto, no hay devoluciones.</div>
		</div>
	</div>

</body>

</html>