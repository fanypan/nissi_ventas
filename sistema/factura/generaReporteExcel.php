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
	$filename = 'reporte_ventas_' . date('YmdHis') . '.xls';

	// Headers para descarga en Excel
	header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
	header("Content-Disposition: attachment; filename=$filename");
	header("Pragma: no-cache");
	header("Expires: 0");

	// 1. Filtros Base (Status y Usuario según Rol)
	$cond_extra = " AND f.status != 3 ";
	if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2) {
		$cond_extra .= " AND f.usuario = $usuario";
	}

	// 2. Filtro de Fechas
	if (isset($_REQUEST['fecha_de']) && isset($_REQUEST['fecha_a']) && !empty($_REQUEST['fecha_de']) && !empty($_REQUEST['fecha_a'])) {
		$fecha_de = mysqli_escape_string($conection, $_REQUEST['fecha_de']);
		$fecha_a = mysqli_escape_string($conection, $_REQUEST['fecha_a']);
		$f_de = $fecha_de . ' 00:00:00';
		$f_a = $fecha_a . ' 23:59:59';
		$cond_extra .= " AND f.fecha BETWEEN '{$f_de}' AND '{$f_a}'";
	}

	// 3. Filtro de Búsqueda General
	if (!empty($_REQUEST['busqueda'])) {
		$busqueda = mysqli_escape_string($conection, $_REQUEST['busqueda']);
		$cond_extra .= " AND (u.nombre LIKE '%$busqueda%' OR cl.nombre LIKE '%$busqueda%' OR f.noventa LIKE '%$busqueda%' OR f.fecha LIKE '%$busqueda%')";
	}

	// 4. Filtro de Producto Específico
	if (isset($_REQUEST['producto']) && !empty($_REQUEST['producto'])) {
		$producto_id = mysqli_escape_string($conection, $_REQUEST['producto']);
		$cond_extra .= " AND dv.codproducto = '$producto_id'";
	}

	// 5. Filtro de Tipo de Pago
	if (!empty($_REQUEST['tipo_pago'])) {
		$tp = mysqli_escape_string($conection, $_REQUEST['tipo_pago']);
		if ($tp == '1') {
			$cond_extra .= " AND (f.tipo_pago_detalle IS NULL OR f.tipo_pago_detalle = 1)";
		} else if ($tp == 'otros') {
			$cond_extra .= " AND f.tipo_pago_detalle IN (2,4,5)";
		} else {
			$cond_extra .= " AND f.tipo_pago_detalle = '$tp'";
		}
	}

	// Construir Query Final
	$sql = "SELECT f.noventa, f.fecha, f.totalventa, f.codcliente, f.status, f.abono, f.tipo_pago_detalle,
				   u.nombre as vendedor, cl.nombre as cliente,
				   GROUP_CONCAT(CONCAT(dv.cantidad, ' x ', p.descripcion) SEPARATOR ', ') as productos_detalles
			FROM venta f
			INNER JOIN usuario u ON f.usuario = u.idusuario
			INNER JOIN cliente cl ON f.codcliente = cl.idcliente 
			INNER JOIN detalleventa dv ON f.noventa = dv.noventa
			INNER JOIN producto p ON dv.codproducto = p.codproducto 
			WHERE 1=1 $cond_extra
			GROUP BY f.noventa 
			ORDER BY f.fecha DESC, f.noventa DESC";

	$query = mysqli_query($conection, $sql);
	
	// Función helper para tipo de pago (simplificada para excel)
	function getTipoPago($id) {
		$tipos = array(1=>'Efectivo', 2=>'Transferencia', 3=>'Crédito', 4=>'QR', 5=>'Tarjeta');
		return isset($tipos[$id]) ? $tipos[$id] : 'Efectivo';
	}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
</head>
<body>
	<h3>REPORTE DE VENTAS</h3>
	<p>Fecha de generación: <?php echo date('d/m/Y H:i'); ?></p>
	<table border="1">
		<thead>
			<tr style="background-color: #5dade2; color: #FFF;">
				<th>No. Venta</th>
				<th>Fecha</th>
				<th>Cliente</th>
				<th>Vendedor</th>
				<th>Productos (Cant x Desc)</th>
				<th>Método de Pago</th>
				<th>Estado</th>
				<th>Total Factura</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$total_acumulado = 0;
			if (mysqli_num_rows($query) > 0) {
				while ($data = mysqli_fetch_array($query)) {
					$estado = 'Activo';
					if ($data['status'] == 2) $estado = 'Anulado';
					if ($data['status'] == 3) $estado = 'Cancelado';
					if ($data['status'] == 4) $estado = 'Abono';
					
					$totalVenta = $data['totalventa'];
					if($totalVenta == 0) $totalVenta = $data['abono'];

					// Solo sumar al total general si la venta está activa o es abono
					if ($data['status'] == 1 || $data['status'] == 4) {
						$total_acumulado += $totalVenta;
					}
			?>
				<tr>
					<td><?php echo $data['noventa']; ?></td>
					<td><?php echo date('d/m/Y H:i', strtotime($data['fecha'])); ?></td>
					<td><?php echo $data['cliente']; ?></td>
					<td><?php echo $data['vendedor']; ?></td>
					<td><?php echo $data['productos_detalles']; ?></td>
					<td><?php echo getTipoPago($data['tipo_pago_detalle']); ?></td>
					<td><?php echo $estado; ?></td>
					<td style="font-weight: bold; text-align: right;"><?php echo number_format($totalVenta, 2); ?></td>
				</tr>
			<?php
				}
				?>
				<tr style="background-color: #eee; font-weight: bold;">
					<td colspan="7" style="text-align: right;">TOTAL GENERAL (Ventas Activas):</td>
					<td style="text-align: right;"><?php echo number_format($total_acumulado, 2); ?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</body>
</html>
