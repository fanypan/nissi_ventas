<?php
	session_start();
	if(empty($_SESSION['active']))
	{
		header('location: ../');
		exit;
	}

	include "../../conexion.php";

	// Configuración de la empresa
	$query_conf = mysqli_query($conection,"SELECT * FROM configuracion");
	$result_conf = mysqli_num_rows($query_conf);
	$moneda = '';
	if($result_conf > 0){
		$configuracion = mysqli_fetch_assoc($query_conf);
		$moneda = $configuracion['moneda'];
	}

	// Obtener parámetros
	$busqueda = isset($_GET['busqueda']) ? mysqli_real_escape_string($conection, $_GET['busqueda']) : '';
	$fecha_de = isset($_GET['fecha_de']) ? mysqli_real_escape_string($conection, $_GET['fecha_de']) : '';
	$fecha_a = isset($_GET['fecha_a']) ? mysqli_real_escape_string($conection, $_GET['fecha_a']) : '';

	// Definir nombre de archivo
	$filename = 'reporte_compras_' . date('YmdHis') . '.xls';

	// Headers para descarga en Excel
	header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
	header("Content-Disposition: attachment; filename=$filename");
	header("Pragma: no-cache");
	header("Expires: 0");

	// Construir consulta SQL
	$sql = "SELECT c.nocompra, c.fecha, c.totalcompra, c.status, 
				   p.proveedor, u.nombre as usuario_nombre,
				   GROUP_CONCAT(CONCAT(e.cantidad, ' x ', pr.descripcion) SEPARATOR ', ') as productos_detalles
			FROM compras c
			INNER JOIN proveedor p ON c.codproveedor = p.codproveedor
			INNER JOIN usuario u ON c.usuario = u.idusuario
			INNER JOIN entradas e ON c.nocompra = e.nocompra
			INNER JOIN producto pr ON e.codproducto = pr.codproducto";

	$condiciones = array("c.status != 2"); // Excluir eliminadas

	if (!empty($fecha_de) && !empty($fecha_a)) {
		$condiciones[] = "DATE(c.fecha) BETWEEN '$fecha_de' AND '$fecha_a'";
	}

	if (!empty($busqueda)) {
		$condiciones[] = "(c.nocompra LIKE '%$busqueda%' OR p.proveedor LIKE '%$busqueda%' OR pr.descripcion LIKE '%$busqueda%')";
	}

	$where = implode(" AND ", $condiciones);
	$sql .= " WHERE $where GROUP BY c.nocompra ORDER BY c.fecha DESC, c.nocompra DESC";

	$query = mysqli_query($conection, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
</head>
<body>
	<h3 style="text-align: center;">REPORTE DE COMPRAS</h3>
	<p><strong>Fecha de generación:</strong> <?php echo date('d/m/Y H:i'); ?></p>
	<?php if (!empty($fecha_de) && !empty($fecha_a)): ?>
		<p><strong>Periodo:</strong> <?php echo $fecha_de; ?> al <?php echo $fecha_a; ?></p>
	<?php endif; ?>
	
	<table border="1">
		<thead>
			<tr style="background-color: #27ae60; color: #FFF;">
				<th>No. Compra</th>
				<th>Fecha</th>
				<th>Proveedor</th>
				<th>Productos (Cant x Desc)</th>
				<th>Total Compra</th>
				<th>Responsable</th>
				<th>Estado</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$total_general = 0;
			if ($query && mysqli_num_rows($query) > 0) {
				while ($data = mysqli_fetch_assoc($query)) {
					$total_general += ($data['status'] == 1) ? $data['totalcompra'] : 0;
					$estado = ($data['status'] == 1) ? 'Activa' : 'Anulada';
					$color_estado = ($data['status'] == 1) ? '#2ecc71' : '#e74c3c';
			?>
				<tr>
					<td><?php echo str_pad($data['nocompra'], 6, '0', STR_PAD_LEFT); ?></td>
					<td><?php echo date('d/m/Y H:i', strtotime($data['fecha'])); ?></td>
					<td><?php echo $data['proveedor']; ?></td>
					<td><?php echo $data['productos_detalles']; ?></td>
					<td style="text-align: right; font-weight: bold;"><?php echo number_format($data['totalcompra'], 2); ?></td>
					<td><?php echo $data['usuario_nombre']; ?></td>
					<td style="color: <?php echo $color_estado; ?>;"><?php echo $estado; ?></td>
				</tr>
			<?php
				}
				?>
				<tr style="background-color: #eee; font-weight: bold;">
					<td colspan="4" style="text-align: right;">TOTAL GENERAL (Compras Activas):</td>
					<td style="text-align: right;"><?php echo number_format($total_general, 2); ?></td>
					<td colspan="2"></td>
				</tr>
				<?php
			} else {
				echo "<tr><td colspan='7' style='text-align:center;'>No se encontraron resultados</td></tr>";
			}
			?>
		</tbody>
	</table>
</body>
</html>
