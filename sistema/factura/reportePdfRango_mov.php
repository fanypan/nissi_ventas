<?php
	$subtotal 	= 0;
	$iva 	 	= 0;
	$impuesto 	= 0;
	$tl_sniva   = 0;
	$total 		= 0;
    $moned      = (isset($configuracion['moneda']) ? $configuracion['moneda'] : '');
 //print_r($configuracion); ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Reporte de Movimientos (Rango)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div id="page_pdf">
	<table id="factura_head">
		<tr>
			<td class="logo_factura">
				<div>
					<img src="img/<?php echo $configuracion['foto']; ?>">
				</div>
			</td>
			<td class="info_empresa">
				<div>
					<span class="h2"><?php echo strtoupper($configuracion['nombre']); ?></span>
					<p><?php echo $configuracion['razon_social']; ?></p>
					<p><?php echo $configuracion['direccion']; ?></p>
					<p>RUC: <?php echo $configuracion['nit']; ?></p>
					<p>Teléfono: <?php echo $configuracion['telefono']; ?></p>
					<p>Email: <?php echo $configuracion['email']; ?></p>
				</div>
			</td>
			<td class="info_factura">
				<div class="round">
					<span class="h3">DATOS DEL CLIENTE</span>
					<div style="padding: 0 10px;">
						<p>Nombre: <strong><?php echo $query_cliente['nombre']; ?></strong></p>
						<p>Teléfono: <strong><?php echo $query_cliente['telefono']; ?></strong></p>
					</div>
				</div>
			</td>
		</tr>
	</table>

	<table id="factura_detalle" style="width: 100%; border-collapse: collapse;">
			<thead>
				<tr>
					<th style="width: 8%; background: #3498db; color: #FFF; padding: 8px; font-size: 9pt; text-align: left;">No.</th>
					<th style="width: 12%; background: #3498db; color: #FFF; padding: 8px; font-size: 9pt; text-align: left;">Fecha</th>
					<th style="width: 20%; background: #3498db; color: #FFF; padding: 8px; font-size: 9pt; text-align: left;">Vendedor</th>
					<th style="width: 12%; background: #3498db; color: #FFF; padding: 8px; font-size: 9pt; text-align: left;">Vence</th>
					<th style="width: 13%; background: #3498db; color: #FFF; padding: 8px; font-size: 9pt; text-align: right;">Factura</th>
					<th style="width: 13%; background: #3498db; color: #FFF; padding: 8px; font-size: 9pt; text-align: right;">Abono</th>
					<th style="width: 17%; background: #3498db; color: #FFF; padding: 8px; font-size: 9pt; text-align: right;">Saldo</th>
				</tr>
			</thead>
			<tbody>
				<!-- SALDO ANTERIOR -->
				<tr style="background: #eef7ff; font-style: italic;">
					<td colspan="4" style="padding: 8px; border-bottom: 1px solid #eee; font-size: 8pt; text-align: right; font-weight: bold;">SALDO ANTERIOR</td>
					<td style="padding: 8px; border-bottom: 1px solid #eee; font-size: 8pt; text-align: right;">-</td>
					<td style="padding: 8px; border-bottom: 1px solid #eee; font-size: 8pt; text-align: right;">-</td>
					<td style="padding: 8px; border-bottom: 1px solid #eee; font-size: 8pt; text-align: right; font-weight: bold;"><?php echo $moned.' '.formatCant($saldo_anterior); ?></td>
				</tr>

				<?php
				if ($result > 0) {
					$ventas_totales = $saldo_anterior;
					$i = 0;
					while ($data = mysqli_fetch_array($query)) {
						date_default_timezone_set("America/Asuncion");
						$fecha = date('d-m-Y',strtotime($data["fecha"]));
						$fecha_a_vencer = date('d-m-Y',strtotime($fecha. '+ 30 days'));
						$totalfact = $data["totalventa"];
						$abono = $data['abono'];
						$ventas_totales = $ventas_totales + $totalfact - $abono;

                        $bg = ($i % 2 == 0) ? '#FFFFFF' : '#f9f9f9';
						$noventa = $data['noventa'];

						// Buscar productos si es venta
						$productos_str = "";
						if ($data['status'] == 3) {
							$query_prod = mysqli_query($conection, "SELECT p.descripcion, d.cantidad 
																	FROM detalleventa d 
																	INNER JOIN producto p ON d.codproducto = p.codproducto 
																	WHERE d.noventa = $noventa");
							if ($query_prod && mysqli_num_rows($query_prod) > 0) {
								$productos_str = " (";
								$prods = [];
								while ($p = mysqli_fetch_assoc($query_prod)) {
									$prods[] = $p['cantidad'] . " " . $p['descripcion'];
								}
								$productos_str .= implode(", ", $prods) . ")";
							}
						}
						?>
						<tr style="background: <?php echo $bg; ?>;">
							<td style="padding: 8px; border-bottom: 1px solid #eee; font-size: 8pt;"><?php echo $data["noventa"]; ?></td>
							<td style="padding: 8px; border-bottom: 1px solid #eee; font-size: 8pt;"><?php echo $fecha; ?></td>
							<td style="padding: 8px; border-bottom: 1px solid #eee; font-size: 8pt;">
								<?php echo $data["vendedor"]; ?>
								<div style="font-size: 7pt; color: #777;"><?php echo $productos_str; ?></div>
							</td>
							<td style="padding: 8px; border-bottom: 1px solid #eee; font-size: 8pt;"><?php echo ($data['status'] == 3 ? $fecha_a_vencer : '-'); ?></td>
							<td style="padding: 8px; border-bottom: 1px solid #eee; font-size: 8pt; text-align: right;"><?php echo ($totalfact > 0 ? $moned.' '.formatCant($totalfact) : ''); ?></td>
							<td style="padding: 8px; border-bottom: 1px solid #eee; font-size: 8pt; text-align: right;"><?php echo ($abono > 0 ? $moned.' '.formatCant($abono) : ''); ?></td>
							<td style="padding: 8px; border-bottom: 1px solid #eee; font-size: 8pt; text-align: right; font-weight: bold;"><?php echo $moned.' '.formatCant($ventas_totales); ?></td>
						</tr>
						<?php
                        $i++;
					}
				}
				?>
			</tbody>
	</table>
</div>

</body>
</html>