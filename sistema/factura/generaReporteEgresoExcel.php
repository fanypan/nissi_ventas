<?php
	session_start();
	if(empty($_SESSION['active']))
	{
		header('location: ../');
	}

	include "../../conexion.php";

	$usuario = $_SESSION['idUser'];
	$query_conf = mysqli_query($conection,"SELECT * FROM configuracion");
	$result_conf = mysqli_num_rows($query_conf);
	if($result_conf > 0){
		$configuracion = mysqli_fetch_assoc($query_conf);
		$moned = $configuracion['moneda'];
	}

	// Definir nombre de archivo
	$filename = 'reporte_egresos_' . date('YmdHis') . '.xls';

	// Headers para descarga en Excel
	header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
	header("Content-Disposition: attachment; filename=$filename");
	header("Pragma: no-cache");
	header("Expires: 0");

	// Filtros
	$fecha_de = isset($_GET['f_de']) ? mysqli_real_escape_string($conection, $_GET['f_de']) : date('Y-m-d');
	$fecha_a = isset($_GET['f_a']) ? mysqli_real_escape_string($conection, $_GET['f_a']) : date('Y-m-d');
	$tipo = isset($_GET['t']) ? $_GET['t'] : '';

	$where = "DATE(e.fecha) BETWEEN '$fecha_de' AND '$fecha_a'";
	if ($tipo != '') {
		$where .= " AND e.tipo_egreso = " . (int)$tipo;
	}

	$sql = "SELECT e.*, u.nombre as usuario_nombre
			FROM egresos e
			INNER JOIN usuario u ON e.usuario = u.idusuario
			WHERE $where
			ORDER BY e.fecha DESC";

	$query = mysqli_query($conection, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
</head>
<body>
	<h3 style="text-align: center;">REPORTE DE EGRESOS</h3>
	<p><strong>Periodo:</strong> <?php echo $fecha_de; ?> al <?php echo $fecha_a; ?></p>
	<p><strong>Fecha de generación:</strong> <?php echo date('d/m/Y H:i'); ?></p>
	
	<table border="1">
		<thead>
			<tr style="background-color: #2c3e50; color: #FFF;">
				<th>Fecha</th>
				<th>Tipo</th>
				<th>Descripción</th>
				<th>Establecimiento</th>
				<th>Precio Unit.</th>
				<th>Cantidad</th>
				<th>Total (<?php echo $moned; ?>)</th>
				<th>Usuario</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$total_final = 0;
			if ($query && mysqli_num_rows($query) > 0) {
				while ($data = mysqli_fetch_array($query)) {
					$total_final += $data['cantidad'];
					$tipo_t = ($data['tipo_egreso'] == 2) ? 'Insumo' : 'Gasto General';
					$p_u = ($data['tipo_egreso'] == 2) ? $data['precio_unitario'] : '-';
					$c_u = ($data['tipo_egreso'] == 2) ? $data['cantidad_unidades'] : '-';
			?>
				<tr>
					<td><?php echo date('d/m/Y H:i', strtotime($data['fecha'])); ?></td>
					<td><?php echo $tipo_t; ?></td>
					<td><?php echo $data['descripcion']; ?></td>
					<td><?php echo $data['establecimiento']; ?></td>
					<td style="text-align: right;"><?php echo $p_u; ?></td>
					<td style="text-align: center;"><?php echo $c_u; ?></td>
					<td style="text-align: right; font-weight: bold;"><?php echo number_format($data['cantidad'], 2); ?></td>
					<td><?php echo $data['usuario_nombre']; ?></td>
				</tr>
			<?php
				}
				?>
				<tr style="background-color: #eee; font-weight: bold;">
					<td colspan="6" style="text-align: right;">TOTAL GENERAL:</td>
					<td style="text-align: right;"><?php echo number_format($total_final, 2); ?></td>
					<td></td>
				</tr>
				<?php
			} else {
				echo "<tr><td colspan='8' style='text-align:center;'>No hay datos en este periodo</td></tr>";
			}
			?>
		</tbody>
	</table>
</body>
</html>
