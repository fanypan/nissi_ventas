<?php
	$moned = '$';
	$numeros_letras = '';

	if($result_config > 0){
		$moned = $configuracion['moneda'];
	}

	$data_recibo = null;
	if (count($detalles) > 0) {
		$data_recibo = $detalles[0];
		$numeros_letras = $numtoletra->numtoletras($data_recibo['cantidad'],' cordobas');
	} else {
		$data_recibo = [
			'id' => $no_factura,
			'fecha' => $factura['fecha'],
			'hora' => $factura['hora'],
			'saldo_anterior' => 0,
			'cantidad' => floatval($factura['abono']),
			'saldo_actual' => 0,
			'nombre' => $factura['vendedor']
		];
		$numeros_letras = $numtoletra->numtoletras($data_recibo['cantidad'],' cordobas');
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Recibo</title>
</head>
<body style="font-family: Arial; font-size: 10px; width: 200px;">

<?php if(!empty($anulada)): ?>
<center><?php echo $anulada; ?></center>
<br>
<?php endif; ?>

<table style="width: 100%; border-collapse: collapse;">
	<?php 
	$logo_recibo = __DIR__ . '/img/' . $configuracion['foto'];
	if(!empty($configuracion['foto']) && file_exists($logo_recibo)): 
	?>
	<tr>
		<td style="text-align: center;">
			<img src="<?php echo $logo_recibo; ?>" style="max-width: 80px; max-height: 50px;">
		</td>
	</tr>
	<?php endif; ?>
	<tr>
		<td style="text-align: center; padding-top: 5px;">
			<strong style="font-size: 13px; line-height: 1.2;"><?php echo strtoupper($configuracion['nombre']); ?></strong>
		</td>
	</tr>
	<tr>
		<td style="text-align: center; padding-top: 2px; font-size: 9px;">
			<?php echo $configuracion['razon_social']; ?>
		</td>
	</tr>
	<tr>
		<td style="text-align: center; padding-top: 2px; font-size: 9px;">
			<?php echo $configuracion['direccion']; ?>
		</td>
	</tr>
	<tr>
		<td style="text-align: center; padding-top: 2px; font-size: 9px;">
			RUC: <?php echo $configuracion['nit']; ?>
		</td>
	</tr>
	<tr>
		<td style="text-align: center; padding-top: 2px; font-size: 9px;">
			Tel: <?php echo $configuracion['telefono']; ?>
		</td>
	</tr>
	<?php if(isset($configuracion['email']) && !empty($configuracion['email'])): ?>
	<tr>
		<td style="text-align: center; padding-top: 2px; font-size: 9px;">
			<?php echo $configuracion['email']; ?>
		</td>
	</tr>
	<?php endif; ?>
</table>

<br>
<div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>

<br><br>

<strong>No. Recibo:</strong>
<br>
<?php echo str_pad($data_recibo['id'], 11, '0', STR_PAD_LEFT); ?>
<br>
<strong>Fecha:</strong>
<br>
<?php echo $data_recibo['fecha']; ?>
<br>
<strong>Hora:</strong>
<br>
<?php echo $data_recibo['hora']; ?>
<br>
<strong>Usuario:</strong>
<br>
<?php echo $data_recibo['nombre']; ?>
<br>

<br>
<div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>
<br><br>

<strong>Cliente:</strong>
<br>
<?php echo $factura['nombre']; ?>
<br>
<strong>RUC:</strong>
<br>
<?php echo $factura['nit']; ?>
<br>

<br>
<div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>
<br><br>

<?php if(isset($factura['pago_con']) && $factura['pago_con'] > 0): ?>
<span style="font-size: 8px;">
<strong>Efectivo Recibido:</strong> <?php echo $moned . ' ' . formatCant($factura['pago_con']); ?>
<br>
<strong>Vuelto:</strong> <?php echo $moned . ' ' . formatCant($factura['vuelto']); ?>
</span>
<br>
<div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>
<br><br>
<?php endif; ?>

<strong>Saldo anterior:</strong>
<br>
<?php echo $moned . ' ' . formatCant($data_recibo['saldo_anterior']); ?>
<br>

<br>
<strong>Abono:</strong>
<br>
<?php echo $moned . ' ' . formatCant($data_recibo['cantidad']); ?>
<br>

<br>
<div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>
<br><br>

<strong style="font-size: 11px;">SALDO ACTUAL:</strong>
<br>
<strong style="font-size: 14px;"><?php echo $moned . ' ' . formatCant($data_recibo['saldo_actual']); ?></strong>
<br>

<div style="text-align: center; border-bottom: 1px dashed #000; margin: 10px 0;"></div>

<center><strong>¡Gracias por su visita!</strong></center>
<br><br>
<?php if(isset($configuracion['email']) && !empty($configuracion['email'])): ?>
<center><?php echo $configuracion['email']; ?></center>
<?php endif; ?>

</body>
</html>