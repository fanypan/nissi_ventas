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

<center><strong style="font-size: 13px;"><?php echo strtoupper($configuracion['nombre']); ?></strong></center>
<br>
<center><?php echo $configuracion['razon_social']; ?></center>
<br>
<center><?php echo $configuracion['direccion']; ?></center>
<br>
<center>RUC: <?php echo $configuracion['nit']; ?></center>
<br>
<center>Tel: <?php echo $configuracion['telefono']; ?></center>
<br>
<?php if(isset($configuracion['email']) && !empty($configuracion['email'])): ?>
<center><?php echo $configuracion['email']; ?></center>
<br>
<?php endif; ?>

<br>
--------------------------------------
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
--------------------------------------
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
--------------------------------------
<br><br>

<strong>Saldo anterior:</strong>
<br>
<?php echo $moned . ' ' . number_format($data_recibo['saldo_anterior'], 2); ?>
<br>

<br>
<strong>Abono:</strong>
<br>
<?php echo $moned . ' ' . number_format($data_recibo['cantidad'], 2); ?>
<br>

<br>
--------------------------------------
<br><br>

<strong style="font-size: 11px;">SALDO ACTUAL:</strong>
<br>
<strong style="font-size: 14px;"><?php echo $moned . ' ' . number_format($data_recibo['saldo_actual'], 2); ?></strong>
<br>

<br>
--------------------------------------
<br><br><br>

<center><strong>¡Gracias por su visita!</strong></center>
<br><br>
<?php if(isset($configuracion['email']) && !empty($configuracion['email'])): ?>
<center><?php echo $configuracion['email']; ?></center>
<?php endif; ?>

</body>
</html>