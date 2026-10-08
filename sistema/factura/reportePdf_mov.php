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
	<title>Reporte de Movimientos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div id="page_pdf">
	<table id="factura_head" style="width: 100%; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px;">
		<tr>
			<td style="width: 20%; vertical-align: middle;">
				<img src="img/<?php echo $configuracion['foto']; ?>" style="max-width: 100px;">
			</td>
			<td style="width: 50%; text-align: center; vertical-align: middle;">
				<table style="width: 100%;">
					<tr>
						<td><strong style="font-size: 20pt; color: #2980b9;"><?php echo strtoupper($configuracion['nombre']); ?></strong></td>
					</tr>
					<tr>
						<td style="font-size: 9pt; color: #555; padding-top: 5px;"><?php echo $configuracion['razon_social']; ?></td>
					</tr>
					<tr>
						<td style="font-size: 9pt; color: #555;"><?php echo $configuracion['direccion']; ?></td>
					</tr>
					<tr>
						<td style="font-size: 9pt; color: #555;">RUC: <?php echo $configuracion['nit']; ?> | Tel: <?php echo $configuracion['telefono']; ?></td>
					</tr>
				</table>
			</td>
			<td style="width: 30%; vertical-align: top;">
				<table style="width: 100%; border: 1px solid #3498db; border-collapse: collapse;">
					<tr>
						<td style="background: #3498db; color: #FFF; padding: 5px; text-align: center; font-weight: bold; font-size: 10pt;">CLIENTE</td>
					</tr>
					<tr>
						<td style="padding: 10px; font-size: 9pt;">
							<table style="width: 100%;">
								<tr>
									<td style="width: 30%;"><strong>Nombre:</strong></td>
									<td><?php echo $query_cliente['nombre']; ?></td>
								</tr>
								<tr>
									<td><strong>Teléfono:</strong></td>
									<td><?php echo $query_cliente['telefono']; ?></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<table id="factura_detalle" style="width: 100%; border-collapse: collapse;">
			<thead>
				<tr>
					<th style="width: 8%; background: #3498db; color: #FFF; padding: 12px 8px; font-size: 10pt; text-align: left;">No.</th>
					<th style="width: 12%; background: #3498db; color: #FFF; padding: 12px 8px; font-size: 10pt; text-align: left;">Fecha</th>
					<th style="width: 20%; background: #3498db; color: #FFF; padding: 12px 8px; font-size: 10pt; text-align: left;">Vendedor</th>
					<th style="width: 12%; background: #3498db; color: #FFF; padding: 12px 8px; font-size: 10pt; text-align: left;">Vence</th>
					<th style="width: 13%; background: #3498db; color: #FFF; padding: 12px 8px; font-size: 10pt; text-align: right;">Factura</th>
					<th style="width: 13%; background: #3498db; color: #FFF; padding: 12px 8px; font-size: 10pt; text-align: right;">Abono</th>
					<th style="width: 17%; background: #3498db; color: #FFF; padding: 12px 8px; font-size: 10pt; text-align: right;">Saldo</th>
				</tr>
			</thead>
			<tbody>
				<!-- SALDO ANTERIOR -->
				<tr style="background: #eef7ff; font-style: italic;">
					<td colspan="4" style="padding: 12px 8px; border-bottom: 1px solid #eee; font-size: 9pt; text-align: right; font-weight: bold;">SALDO ANTERIOR</td>
					<td style="padding: 12px 8px; border-bottom: 1px solid #eee; font-size: 9pt; text-align: right;">-</td>
					<td style="padding: 12px 8px; border-bottom: 1px solid #eee; font-size: 9pt; text-align: right;">-</td>
					<td style="padding: 12px 8px; border-bottom: 1px solid #eee; font-size: 9pt; text-align: right; font-weight: bold;"><?php echo $moned.' '.formatCant($saldo_anterior); ?></td>
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
							<td style="padding: 12px 8px; border-bottom: 1px solid #eee; font-size: 9pt;"><?php echo $data["noventa"]; ?></td>
							<td style="padding: 12px 8px; border-bottom: 1px solid #eee; font-size: 9pt;"><?php echo $fecha; ?></td>
							<td style="padding: 12px 8px; border-bottom: 1px solid #eee; font-size: 9pt;">
								<?php echo $data["vendedor"]; ?>
								<div style="font-size: 7pt; color: #777;"><?php echo $productos_str; ?></div>
							</td>
							<td style="padding: 12px 8px; border-bottom: 1px solid #eee; font-size: 9pt;"><?php echo ($data['status'] == 3 ? $fecha_a_vencer : '-'); ?></td>
							<td style="padding: 12px 8px; border-bottom: 1px solid #eee; font-size: 9pt; text-align: right;"><?php echo ($totalfact > 0 ? $moned.' '.formatCant($totalfact) : ''); ?></td>
							<td style="padding: 12px 8px; border-bottom: 1px solid #eee; font-size: 9pt; text-align: right;"><?php echo ($abono > 0 ? $moned.' '.formatCant($abono) : ''); ?></td>
							<td style="padding: 12px 8px; border-bottom: 1px solid #eee; font-size: 9pt; text-align: right; font-weight: bold;"><?php echo $moned.' '.formatCant($ventas_totales); ?></td>
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