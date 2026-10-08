<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<style>
		@page { margin: 40px; }
		body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
		table { width: 100%; border-collapse: collapse; margin-bottom: 20px; table-layout: fixed; }
		
		/* Encabezado estable */
		.header-box { width: 100%; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
		.title { font-size: 18px; font-weight: bold; color: #000; display: block; }
		.subtitle { font-size: 12px; color: #444; margin-top: 5px; display: block; }
		.info-text { font-size: 9px; color: #666; text-align: right; }

		/* Resumen de Totales */
		.resumen-table th { background: #f2f2f2; padding: 8px; border: 1px solid #ccc; text-align: center; text-transform: uppercase; font-size: 8px; }
		.resumen-table td { padding: 12px; border: 1px solid #ccc; text-align: center; font-size: 14px; font-weight: bold; }
		.total-final { color: #d32f2f; }

		/* Tabla de Datos */
		.data-table th { background: #2c3e50; color: #fff; padding: 8px; text-align: left; font-size: 9px; border: 1px solid #2c3e50; }
		.data-table td { padding: 6px; border: 1px solid #eee; font-size: 8px; word-wrap: break-word; }
		.data-table tr:nth-child(even) { background: #f9f9f9; }
		.data-table tfoot td { background: #eee; font-size: 10px; font-weight: bold; border-top: 2px solid #2c3e50; }
		
		.text-right { text-align: right; }
		.text-center { text-align: center; }
		.type-label { font-weight: bold; padding: 1px 3px; border-radius: 2px; font-size: 7px; color: #fff; }
		.label-insumo { background: #1976d2; }
		.label-general { background: #616161; }
	</style>
</head>
<body>

	<table class="header-box">
		<tr>
			<td style="width: 60%; text-align: left; vertical-align: top;">
				<span class="title"><?php echo strtoupper($configuracion['nombre']); ?></span>
				<span class="subtitle">REPORTE DE EGRESOS</span>
			</td>
			<td style="width: 40%; text-align: right; vertical-align: top;" class="info-text">
				Generado: <?php echo date('d/m/Y H:i'); ?><br>
				Periodo: <?php echo date('d/m/Y', strtotime($fecha_de)); ?> al <?php echo date('d/m/Y', strtotime($fecha_a)); ?>
			</td>
		</tr>
	</table>

	<table class="resumen-table">
		<thead>
			<tr>
				<th>Gastos Generales</th>
				<th>Insumos</th>
				<th>Total Periodo</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php echo $moned . ' ' . formatCant($resumen['total_general']); ?></td>
				<td><?php echo $moned . ' ' . formatCant($resumen['total_insumos']); ?></td>
				<td class="total-final"><?php echo $moned . ' ' . formatCant($resumen['total_egresos']); ?></td>
			</tr>
		</tbody>
	</table>

	<table class="data-table">
		<thead>
			<tr>
				<th style="width: 14%;">Fecha</th>
				<th style="width: 8%; text-align: center;">Tipo</th>
				<th style="width: 28%;">Descripción</th>
				<th style="width: 15%;">Establecimiento</th>
				<th style="width: 11%; text-align: right;">Unitario</th>
				<th style="width: 8%; text-align: center;">Cant.</th>
				<th style="width: 16%; text-align: right;">Total</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$total_acumulado = 0;
			if ($result > 0) {
				mysqli_data_seek($query, 0); // Reiniciar puntero por si acaso
				while ($row = mysqli_fetch_array($query)) {
					$tipo_class = ($row['tipo_egreso'] == 2) ? 'label-insumo' : 'label-general';
					$tipo_text = ($row['tipo_egreso'] == 2) ? 'INSUMO' : 'GRAL';
					$p_u = ($row['tipo_egreso'] == 2) ? formatCant($row['precio_unitario']) : '-';
					$c_u = ($row['tipo_egreso'] == 2) ? $row['cantidad_unidades'] : '-';
					$total_acumulado += $row['cantidad'];
			?>
				<tr>
					<td><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
					<td class="text-center"><span class="type-label <?php echo $tipo_class; ?>"><?php echo $tipo_text; ?></span></td>
					<td><?php echo $row['descripcion']; ?></td>
					<td><?php echo $row['establecimiento']; ?></td>
					<td class="text-right"><?php echo $p_u; ?></td>
					<td class="text-center"><?php echo $c_u; ?></td>
					<td class="text-right"><?php echo formatCant($row['cantidad']); ?></td>
				</tr>
			<?php
				}
			} else {
				echo "<tr><td colspan='7' class='text-center' style='padding: 20px;'>No hay datos</td></tr>";
			}
			?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="6" class="text-right">TOTAL GENERAL:</td>
				<td class="text-right"><?php echo $moned . ' ' . formatCant($total_acumulado); ?></td>
			</tr>
		</tfoot>
	</table>

	<div style="text-align: center; margin-top: 30px; font-size: 8px; color: #999;">
		Generado por el sistema de gestión de <?php echo $configuracion['nombre']; ?>
	</div>

</body>
</html>
