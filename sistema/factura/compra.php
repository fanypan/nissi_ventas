<?php
	$subtotal = 0;
	$total = 0;
	$moned = isset($configuracion['moneda']) ? $configuracion['moneda'] : 'Gs.';
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Compra</title>
</head>
<body style="font-family: Arial; font-size: 10pt; margin: 20px;">

<?php echo $anulada; ?>

<!-- ENCABEZADO -->
<table style="width: 100%; margin-bottom: 10px;">
	<tr>
		<td style="width: 20%; vertical-align: top; text-align: center;">
			<?php if(!empty($configuracion['foto'])): ?>
				<img src="img/<?php echo $configuracion['foto']; ?>" style="width: 90px;">
			<?php endif; ?>
		</td>
		<td style="width: 50%; vertical-align: top; text-align: center; padding: 0 10px;">
			<div style="font-size: 16pt; font-weight: bold; color: #058167; margin: 0;">
				<?php echo strtoupper($configuracion['nombre']); ?>
			</div>
		</td>
		<td style="width: 30%; vertical-align: top;">
			<table style="width: 100%; border: 2px solid #058167; background: #f8f8f8;">
				<tr>
					<td style="background: #058167; color: white; text-align: center; padding: 5px; font-size: 12pt; font-weight: bold;">
						COMPRA
					</td>
				</tr>
				<tr>
					<td style="padding: 8px; font-size: 8pt; line-height: 1.4;">
						<strong>No. Compra:</strong> <?php echo str_pad($factura['nocompra'], 10, '0', STR_PAD_LEFT); ?><br>
						<strong>Fecha:</strong> <?php echo $factura['fecha']; ?><br>
						<strong>Hora:</strong> <?php echo $factura['hora']; ?><br>
						<strong>Usuario:</strong> <?php echo $factura['vendedor']; ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<div style="border-bottom: 3px solid #058167; margin-bottom: 15px;"></div>

<!-- PROVEEDOR -->
<div style="background: #0a4661; color: white; padding: 6px; font-weight: bold; margin-top: 15px;">
	DATOS DEL PROVEEDOR
</div>
<table style="width: 100%; border: 1px solid #0a4661; margin-bottom: 15px;">
	<tr>
		<td style="padding: 8px; width: 15%;"><strong>Proveedor:</strong></td>
		<td style="padding: 8px; width: 35%;"><?php echo $factura['proveedor']; ?></td>
		<td style="padding: 8px; width: 15%;"><strong>Teléfono:</strong></td>
		<td style="padding: 8px; width: 35%;"><?php echo $factura['telefono']; ?></td>
	</tr>
	<tr>
		<td style="padding: 8px;"><strong>Contacto:</strong></td>
		<td style="padding: 8px;"><?php echo $factura['contacto']; ?></td>
		<td style="padding: 8px;"><strong>Dirección:</strong></td>
		<td style="padding: 8px;"><?php echo $factura['direccion']; ?></td>
	</tr>
</table>

<!-- PRODUCTOS -->
<table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
	<thead>
		<tr>
			<th style="background: #058167; color: white; padding: 8px; border: 1px solid #058167; width: 10%;">Cant.</th>
			<th style="background: #058167; color: white; padding: 8px; border: 1px solid #058167; text-align: left;">Descripción</th>
			<th style="background: #058167; color: white; padding: 8px; border: 1px solid #058167; width: 20%;">Precio Unit.</th>
			<th style="background: #058167; color: white; padding: 8px; border: 1px solid #058167; width: 20%;">Total</th>
		</tr>
	</thead>
	<tbody>
	<?php
		$contador = 0;
		if($result_detalle > 0){
			while ($row = mysqli_fetch_assoc($query_productos)){
				$precio_unit = formatCant($row['precio']);
				$precio_total = formatCant($row['precio_total']);
				$subtotal += $row['precio_total'];
				$contador++;
				$bg = ($contador % 2 == 0) ? '#f9f9f9' : '#ffffff';
	?>
		<tr>
			<td style="text-align: center; padding: 6px; border: 1px solid #ddd; background: <?php echo $bg; ?>;">
				<?php echo $row['cantidad']; ?>
			</td>
			<td style="padding: 6px; border: 1px solid #ddd; background: <?php echo $bg; ?>;">
				<?php echo $row['descripcion']; ?>
			</td>
			<td style="text-align: right; padding: 6px; border: 1px solid #ddd; background: <?php echo $bg; ?>;">
				<?php echo $moned.' '.$precio_unit; ?>
			</td>
			<td style="text-align: right; padding: 6px; border: 1px solid #ddd; background: <?php echo $bg; ?>;">
				<?php echo $moned.' '.$precio_total; ?>
			</td>
		</tr>
	<?php
			}
		}
		$total = $subtotal;
	?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="3" style="text-align: right; padding: 8px; background: #f0f0f0; font-weight: bold;">
				TOTAL
			</td>
			<td style="text-align: right; padding: 8px; background: #f0f0f0; font-weight: bold; font-size: 12pt; color: #058167;">
				<?php echo $moned.' '.formatCant($total); ?>
			</td>
		</tr>
	</tfoot>
</table>

<!-- PIE -->
<div style="text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ccc;">
	<p style="font-size: 9pt; color: #666;">Procesado por: <strong><?php echo $factura['vendedor']; ?></strong></p>
	<p style="font-weight: bold; margin-top: 10px;">¡Gracias por la confianza!</p>
</div>

</body>
</html>
