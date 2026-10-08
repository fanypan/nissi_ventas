<?php

include "../../conexion.php";
session_start();

// ============================================
// FUNCIÓN PARA OBTENER TIPO DE PAGO EN LISTA
// ============================================
function obtenerTipoPagoLista($conection, $tipo_pago_detalle, $noventa) {
	$tipos = array(
		1 => array('nombre' => 'Efectivo', 'icono' => 'fa-money-bill-wave', 'color' => '#27ae60'),
		2 => array('nombre' => 'Transferencia', 'icono' => 'fa-exchange-alt', 'color' => '#3498db'),
		3 => array('nombre' => 'Crédito', 'icono' => 'fa-credit-card', 'color' => '#e67e22'),
		4 => array('nombre' => 'QR', 'icono' => 'fa-qrcode', 'color' => '#9b59b6'),
		5 => array('nombre' => 'Tarjeta', 'icono' => 'fa-credit-card', 'color' => '#e74c3c')
	);
	
	$tipoPago = isset($tipos[$tipo_pago_detalle]) ? $tipos[$tipo_pago_detalle] : $tipos[1];
	$html = '<span style="color:'.$tipoPago['color'].'; font-weight:bold;"><i class="fas '.$tipoPago['icono'].'"></i> '.$tipoPago['nombre'].'</span>';
	
	// Detalles adicionales según el tipo de pago
	if ($tipo_pago_detalle == 2) { // Transferencia
		$query_trans = mysqli_query($conection, "SELECT b.nombre as banco, t.numero_referencia 
												  FROM transferencias t 
												  LEFT JOIN bancos b ON t.banco_id = b.id 
												  WHERE t.noventa = $noventa LIMIT 1");
		if ($query_trans && mysqli_num_rows($query_trans) > 0) {
			$trans = mysqli_fetch_assoc($query_trans);
			$html .= '<br><small style="color:#666;">'.($trans['banco'] ?: 'N/A').'</small>';
			if (!empty($trans['numero_referencia'])) {
				$html .= '<br><small style="color:#999;">Ref: '.$trans['numero_referencia'].'</small>';
			}
		}
	} elseif ($tipo_pago_detalle == 4) { // QR
		$query_qr = mysqli_query($conection, "SELECT tipo_qr, numero_referencia FROM pagos_qr WHERE noventa = $noventa LIMIT 1");
		if ($query_qr && mysqli_num_rows($query_qr) > 0) {
			$qr = mysqli_fetch_assoc($query_qr);
			$tipoQR = ucwords(str_replace('_', ' ', $qr['tipo_qr']));
			$html .= '<br><small style="color:#666;">'.$tipoQR.'</small>';
			if (!empty($qr['numero_referencia'])) {
				$html .= '<br><small style="color:#999;">Ref: '.$qr['numero_referencia'].'</small>';
			}
		}
	} elseif ($tipo_pago_detalle == 5) { // Tarjeta
		$query_tarjeta = mysqli_query($conection, "SELECT tipo_tarjeta, banco, ultimos_digitos 
													FROM pagos_tarjeta 
													WHERE noventa = $noventa LIMIT 1");
		if ($query_tarjeta && mysqli_num_rows($query_tarjeta) > 0) {
			$tarjeta = mysqli_fetch_assoc($query_tarjeta);
			$tipoTarjeta = ucfirst($tarjeta['tipo_tarjeta']);
			$html .= '<br><small style="color:#666;">'.$tipoTarjeta.' '.$tarjeta['banco'].'</small>';
			$html .= '<br><small style="color:#999;">****'.$tarjeta['ultimos_digitos'].'</small>';
		}
	}
	
	return $html;
}

//Extraer datos del detalle_temp
$query_conf = mysqli_query($conection, "SELECT moneda FROM configuracion");
$result_conf = mysqli_num_rows($query_conf);
$usuario = $_SESSION['idUser'];


if ($result_conf > 0) {
	$info_conf = mysqli_fetch_assoc($query_conf);
	$moned = $info_conf['moneda'];
}
$por_pagina = $_POST['cantidad'];

if ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2) {

	//$por_pagina = $_POST['cantidad'];

	//Buscador en tiempo real
	// UNIFICACIÓN DE FILTROS (Búsqueda general + Rango fecha + Producto + Pago)
	$busqueda = !empty($_POST['busqueda']) ? mysqli_escape_string($conection, $_POST['busqueda']) : '';
	$fecha_de = !empty($_POST['fecha_de']) ? mysqli_escape_string($conection, $_POST['fecha_de']) : '';
	$fecha_a = !empty($_POST['fecha_a']) ? mysqli_escape_string($conection, $_POST['fecha_a']) : '';
	$filtro_producto = !empty($_POST['filtro_producto']) ? mysqli_escape_string($conection, $_POST['filtro_producto']) : '';
	$filtro_pago = !empty($_POST['filtro_pago']) ? mysqli_escape_string($conection, $_POST['filtro_pago']) : '';

	$cond_extra = " AND f.status != 10 ";

	if (!empty($fecha_de) && !empty($fecha_a)) {
		$f_de = $fecha_de . ' 00:00:00';
		$f_a = $fecha_a . ' 23:59:59';
		$cond_extra .= " AND f.fecha BETWEEN '{$f_de}' AND '{$f_a}' ";
	}
	if (!empty($filtro_producto)) {
		$cond_extra .= " AND f.noventa IN (SELECT DISTINCT noventa FROM detalleventa WHERE codproducto = '$filtro_producto')";
	}
	if (!empty($filtro_pago)) {
		if ($filtro_pago == 'otros') {
			$cond_extra .= " AND f.tipo_pago_detalle IN (2, 4, 5)";
		} else {
			$cond_extra .= " AND f.tipo_pago_detalle = '$filtro_pago'";
		}
	}
	if (!empty($busqueda)) {
		$cond_extra .= " AND (u.nombre LIKE '%$busqueda%' OR cl.nombre LIKE '%$busqueda%' OR f.noventa LIKE '%$busqueda%')";
	}

	$sql_registe = mysqli_query($conection, "SELECT COUNT(*) as total_registro
											FROM venta f
											INNER JOIN usuario u ON f.usuario = u.idusuario
											INNER JOIN cliente cl ON f.codcliente = cl.idcliente
											WHERE 1=1 $cond_extra");
	$result_register = mysqli_fetch_array($sql_registe);
	$total_registro = $result_register['total_registro'];

	if (empty($_POST['pagina'])) {
		$pagina = 1;
	} else {
		$pagina = $_POST['pagina'];
	}

	$desde = ($pagina - 1) * $por_pagina;
	$total_pagina = ceil($total_registro / $por_pagina);

	$query = mysqli_query($conection, "SELECT f.noventa, f.fecha, f.totalventa, f.codcliente, f.status, f.abono, f.tipo_pago_detalle,
												u.nombre as vendedor, cl.nombre as cliente
											FROM venta f
											INNER JOIN usuario u ON f.usuario = u.idusuario
											INNER JOIN cliente cl ON f.codcliente = cl.idcliente
											WHERE 1=1 $cond_extra
											ORDER BY f.fecha DESC, f.noventa DESC LIMIT $desde,$por_pagina");
} else {
	//////////////////////////Ventas de vendedor////////////////////////////////				

	//Buscador en tiempo real
	if (isset($_POST['busqueda'])) {
		$busqueda = mysqli_escape_string($conection, $_POST['busqueda']);

		$sql_registe = mysqli_query($conection, "SELECT COUNT(*) as total_registro FROM venta 
																WHERE usuario = $usuario AND status != 10  ");
		$result_register = mysqli_fetch_array($sql_registe);
		$total_registro = $result_register['total_registro'];

		if (empty($_POST['pagina'])) {
			$pagina = 1;
		} else {
			$pagina = $_POST['pagina'];
		}

		$desde = ($pagina - 1) * $por_pagina;
		$total_pagina = ceil($total_registro / $por_pagina);

		$query = mysqli_query($conection, "SELECT f.noventa,
															f.fecha,
															f.totalventa,
															f.codcliente,
															f.status,
															f.abono,
															f.tipo_pago_detalle,
															u.nombre as vendedor,
															cl.nombre as cliente
														FROM venta f
														INNER JOIN usuario u 
														ON f.usuario = u.idusuario
														INNER JOIN cliente cl 
														ON f.codcliente = cl.idcliente
														 WHERE (
																f.noventa LIKE '%$busqueda%' OR
																cl.nombre LIKE '%$busqueda%' OR
																f.fecha LIKE '%$busqueda%')
														 AND f.usuario = $usuario
														 AND f.status != 10 
														 ORDER BY f.fecha DESC, f.noventa DESC LIMIT $desde,$por_pagina");
	}

	//Busqueda por rango de fecha
	if (isset($_POST['fecha_de']) && isset($_POST['fecha_a'])) {
		
		$fecha_de = mysqli_escape_string($conection, $_POST['fecha_de']);
		$fecha_a = mysqli_escape_string($conection, $_POST['fecha_a']);
		$f_de = $fecha_de . ' 00:00:00';
		$f_a = $fecha_a . ' 23:59:59';

		$sql_registe = mysqli_query($conection, "SELECT COUNT(*) as total_registro FROM venta 
																WHERE fecha BETWEEN '{$f_de}' AND '{$f_a}'
																AND usuario = $usuario
																AND status != 10 ");
		$result_register = mysqli_fetch_array($sql_registe);
		$total_registro = $result_register['total_registro'];

		if (empty($_POST['pagina'])) {
			$pagina = 1;
		} else {
			$pagina = $_POST['pagina'];
		}

		$desde = ($pagina - 1) * $por_pagina;
		$total_pagina = ceil($total_registro / $por_pagina);

		$query = mysqli_query($conection, "SELECT f.noventa,
															f.fecha,
															f.totalventa,
															f.codcliente,
															f.status,
															f.abono,
															f.tipo_pago_detalle,
															u.nombre as vendedor,
															cl.nombre as cliente
														FROM venta f
														INNER JOIN usuario u 
														ON f.usuario = u.idusuario
														INNER JOIN cliente cl 
														ON f.codcliente = cl.idcliente
														 WHERE f.fecha BETWEEN '{$f_de}' AND '{$f_a}'
														 AND f.usuario = $usuario
														 AND f.status != 10
														 ORDER BY f.fecha DESC, f.noventa DESC LIMIT $desde,$por_pagina");
	}
}
//Fin de la busqueda por rango de fecha

$result = mysqli_num_rows($query);
$lista = '';
$detalleTabla = '';
$arrayData    = array();

// Variables para totales
$totalGeneral = 0;
$totalesPorTipoPago = array(
	1 => 0, // Efectivo
	2 => 0, // Transferencia
	3 => 0, // Crédito
	4 => 0, // QR
	5 => 0  // Tarjeta
);

$detalleTabla .= '
								<table>
										<tr>
											<th>No.</th>
											<th>Fecha / Hora</th>
											<th>Cliente</th>
											<th>Vendedor</th>
											<th>Método Pago</th>
											<th>Estado</th>
											<th class="">Total Venta</th>
											<th class="textcenter">Acciones</th>
										</tr>';

if ($result > 0) {
	while ($data = mysqli_fetch_array($query)) {
		if ($data['status'] == 1) {
			$estatus = '<span class="pagada">Pagada</span>';
		} else if ($data['status'] == 2) {
			$estatus = '<span class="anulada">Anulada</span>';
		} else {
			$estatus = '<span class="credito">Crédito</span>';
		}

		$totalventa = $moned . ' ' . formatCant($data["totalventa"]);
		$montoVenta = $data["totalventa"];
		if ($data["totalventa"] == 0) {
			$estatus = '<span class="pagada">Abono</span>';
			$totalventa = $moned . ' ' . formatCant($data["abono"]);
			$montoVenta = $data["abono"];
		}
		
		// Acumular totales (solo si no está anulada)
		if ($data['status'] != 2) {
			$totalGeneral += $montoVenta;
			$tipoPagoId = $data['tipo_pago_detalle'];
			if (isset($totalesPorTipoPago[$tipoPagoId])) {
				$totalesPorTipoPago[$tipoPagoId] += $montoVenta;
			}
		}
		
		// Obtener método de pago con detalles
		$metodo_pago = obtenerTipoPagoLista($conection, $data['tipo_pago_detalle'], $data['noventa']);

		$detalleTabla .= '<tr id="row_' . $data['noventa'] . '">
							<td class="textcenter"><a href="#" class="btn_expand_venta" style="color: #2980b9; font-size: 14pt;" onclick="event.preventDefault(); verDetalleVentaRapido(' . $data['noventa'] . ', this);"><i class="fas fa-plus-circle"></i></a> ' . $data["noventa"] . '</td>
							<td>' . $data["fecha"] . '</td>
							<td>' . $data["cliente"] . '</td>
							<td>' . $data["vendedor"] . '</td>
							<td>' . $metodo_pago . '</td>
							<td class="estado">' . $estatus . '</td>
							<td class="totalventa">' . $totalventa . '</td>
							<td>
								<div class="div_acciones">';
		if ($_SESSION['rol'] == 1) {
			$detalleTabla .= '<div>
										<a href="editar_venta.php?id=' . $data['noventa'] . '" class="btn_view" title="Editar venta" style="background: #2980b9;"><i class="fas fa-edit"></i></a>
									</div>';
		}
		if ($data['totalventa'] != 0) {
			$detalleTabla .= '<div>
										<a href class="btn_view view_ticket" title="Ver ticket" onclick="event.preventDefault(); verTicket(' . $data['codcliente'] . ',' . $data['noventa'] . ');"><i class="fas fa-clipboard-list"></i></a>

										</div>';
			// Botón para ver factura
			$detalleTabla .= '<div>
        <a href="#" class="btn_view view_factura" title="Ver factura" onclick="event.preventDefault(); verFactura(' . $data['codcliente'] . ',' . $data['noventa'] . ');"><i class="fas fa-file-invoice"></i></a>
    </div>';



			if ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2) {
				if ($data["status"] == 1 || $data['status'] == 3 || $data['status'] == 4) {
					$detalleTabla .= '<div class="div_factura">
										<a href="#" class="btn_anular anular_factura" title="Anular venta" onclick="event.preventDefault();
								                  infoAnularFactura(' . $data['noventa'] . ');"><i class="fas fa-ban"></i></a>
								    </div>';
				} else {
					$detalleTabla .= '<div class="div_factura">
											<a href="#" class="btn_anular inactive"><i class="fas fa-ban"></i></a>
										</div></td></tr>';
				}
			}
		} else {
			$detalleTabla .= '<div>
										<a href class="btn_view view_ticket" title="Ver recibo" onclick="event.preventDefault(); verRecibo(' . $data['codcliente'] . ',' . $data['noventa'] . ');"><i class="fas fa-clipboard-list"></i></a>
									</div>';

			if ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2) {
				if ($data["status"] == 1 || $data['status'] == 3 || $data['status'] == 4) {
					$detalleTabla .= '<div class="div_factura">
										<a href="#" class="btn_anular anular_factura" title="Anular recibo" onclick="event.preventDefault();
								                  infoAnularRecibo(' . $data['noventa'] . ');"><i class="fas fa-ban"></i></a>
								    </div>';
				} else {
					$detalleTabla .= '<div class="div_factura">
											<a href="#" class="btn_anular inactive"><i class="fas fa-ban"></i></a>
										</div></td></tr>';
				}
			}
		}
		
		$detalleTabla .= '</div></td></tr>';
	}
	
	// Agregar fila de totales
	$detalleTabla .= '
		<tr style="background-color: #f0f0f0; font-weight: bold; border-top: 2px solid #333;">
			<td colspan="6" style="text-align: right; padding: 10px;">TOTAL GENERAL:</td>
			<td colspan="2" style="padding: 10px; font-size: 16px; color: #27ae60;">'.$moned.' '.formatCant($totalGeneral).'</td>
		</tr>';
	
	// Agregar totales por tipo de pago
	$nombresTipoPago = array(
		1 => array('nombre' => 'Efectivo', 'color' => '#27ae60', 'icono' => 'fa-money-bill-wave'),
		2 => array('nombre' => 'Transferencia', 'color' => '#3498db', 'icono' => 'fa-exchange-alt'),
		3 => array('nombre' => 'Crédito', 'color' => '#e67e22', 'icono' => 'fa-credit-card'),
		4 => array('nombre' => 'QR', 'color' => '#9b59b6', 'icono' => 'fa-qrcode'),
		5 => array('nombre' => 'Tarjeta', 'color' => '#e74c3c', 'icono' => 'fa-credit-card')
	);
	
	$detalleTabla .= '
		<tr style="background-color: #fafafa;">
			<td colspan="8" style="padding: 10px;">
				<strong style="font-size: 14px;">Totales por Tipo de Pago:</strong>
				<div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 15px;">';
	
	foreach ($totalesPorTipoPago as $tipoId => $total) {
		if ($total > 0) {
			$tipoPago = $nombresTipoPago[$tipoId];
			$detalleTabla .= '
				<div style="padding: 5px 10px; border-left: 3px solid '.$tipoPago['color'].';">
					<i class="fas '.$tipoPago['icono'].'" style="color: '.$tipoPago['color'].';"></i>
					<strong>'.$tipoPago['nombre'].':</strong> 
					<span style="color: '.$tipoPago['color'].';">'.$moned.' '.formatCant($total).'</span>
				</div>';
		}
	}
	
	$detalleTabla .= '
				</div>
			</td>
		</tr>';
	
	$detalleTabla .= '</table>';

	$lista .= '<ul>';

	if ($pagina > 1) {
		$lista .= '<li><a href="1"><i class="fas fa-step-backward"></i></a></li>
				<li><a href="' . ($pagina - 1) . '"><i class="fas fa-caret-left"></i></a></li>';
	}

	//muestro de los enlaces 
	//cantidad de link hacia atras y adelante
	$cant = 2;
	//inicio de donde se va a mostrar los links
	$pagInicio = ($pagina > $cant) ? ($pagina - $cant) : 1;
	//condicion en la cual establecemos el fin de los links
	if ($total_pagina > $cant) {
		//conocer los links que hay entre el seleccionado y el final
		$pagRestantes = $total_pagina - $pagina;
		//defino el fin de los links
		$pagFin = ($pagRestantes > $cant) ? ($pagina + $cant) : $total_pagina;
	} else {
		$pagFin = $total_pagina;
	}

	for ($i = $pagInicio; $i <= $pagFin; $i++) {

		if ($i == $pagina) {
			$lista .= '<li class="pageSelected">' . $i . '</a></li>';
		} else {
			$lista .= '<li><a href="' . $i . '">' . $i . '</a></li>';
		}
	}

	if ($pagina < $pagFin) {
		$lista .= '<li><a href="' . ($pagina + 1) . '"><i class="fas fa-caret-right"></i></a></li>
				<li><a href="' . ($total_pagina) . '"><i class="fas fa-step-forward"></i></a></li>';
	}
	$lista .= '</ul>';

	$arrayData['detalle'] = $detalleTabla;
	$arrayData['totales'] = $lista;

	echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
} else {
	echo 'error';
}
mysqli_close($conection);

exit;