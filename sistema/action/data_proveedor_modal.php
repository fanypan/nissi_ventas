<?php 
	include "../../conexion.php";
	session_start();

	$por_pagina = isset($_POST['cantidad']) ? $_POST['cantidad'] : 5;
	$busqueda = isset($_POST['busqueda']) ? mysqli_escape_string($conection, $_POST['busqueda']) : '';
	$pagina = isset($_POST['pagina']) ? $_POST['pagina'] : 1;

	$sql_registe = mysqli_query($conection, "SELECT COUNT(*) as total_registro FROM proveedor 
												WHERE (proveedor LIKE '%$busqueda%' OR contacto LIKE '%$busqueda%') 
												AND status = 1 ");
	$result_register = mysqli_fetch_array($sql_registe);
	$total_registro = $result_register['total_registro'];

	$desde = ($pagina - 1) * $por_pagina;
	$total_pagina = ceil($total_registro / $por_pagina);

	$query = mysqli_query($conection, "SELECT * FROM proveedor WHERE
										(proveedor LIKE '%$busqueda%' OR contacto LIKE '%$busqueda%') 
										AND status = 1 
										ORDER BY proveedor ASC 
										LIMIT $desde, $por_pagina");

	$result = mysqli_num_rows($query);
	$detalleTabla = '<table class="modal-results-table">
						<tr>
							<th>Proveedor</th>
							<th>Contacto</th>
							<th>Acción</th>
						</tr>';

	if ($result > 0) {
		while ($data = mysqli_fetch_assoc($query)) {
			$detalleTabla .= '<tr>
								<td>' . $data['proveedor'] . '</td>
								<td>' . $data['contacto'] . '</td>
								<td>
									<button type="button" class="btn-seleccionar-modal" onclick="seleccionarProveedor(' . 
										$data['codproveedor'] . ', \'' . 
										addslashes($data['proveedor']) . '\', \'' . 
										addslashes($data['contacto']) . '\', \'' . 
										$data['telefono'] . '\', \'' . 
										addslashes($data['direccion']) . '\')"><i class="fas fa-check"></i> Seleccionar</button>
								</td>
							</tr>';
		}
	} else {
		$detalleTabla .= '<tr><td colspan="3" class="textcenter">No se encontraron resultados</td></tr>';
	}
	$detalleTabla .= '</table>';

	$lista = '<ul>';
	if ($pagina > 1) {
		$lista .= '<li><a href="1" onclick="event.preventDefault(); listaProveedorModal(\'' . $busqueda . '\', 1);"><i class="fas fa-step-backward"></i></a></li>';
		$lista .= '<li><a href="' . ($pagina - 1) . '" onclick="event.preventDefault(); listaProveedorModal(\'' . $busqueda . '\', ' . ($pagina - 1) . ');"><i class="fas fa-caret-left"></i></a></li>';
	}

	$cant = 2;
	$pagInicio = ($pagina > $cant) ? ($pagina - $cant) : 1;
	$pagRestantes = $total_pagina - $pagina;
	$pagFin = ($pagRestantes > $cant) ? ($pagina + $cant) : $total_pagina;

	for ($i = $pagInicio; $i <= $pagFin; $i++) {
		if ($i == $pagina) {
			$lista .= '<li class="pageSelected">' . $i . '</li>';
		} else {
			$lista .= '<li><a href="' . $i . '" onclick="event.preventDefault(); listaProveedorModal(\'' . $busqueda . '\', ' . $i . ');">' . $i . '</a></li>';
		}
	}

	if ($pagina < $total_pagina) {
		$lista .= '<li><a href="' . ($pagina + 1) . '" onclick="event.preventDefault(); listaProveedorModal(\'' . $busqueda . '\', ' . ($pagina + 1) . ');"><i class="fas fa-caret-right"></i></a></li>';
		$lista .= '<li><a href="' . ($total_pagina) . '" onclick="event.preventDefault(); listaProveedorModal(\'' . $busqueda . '\', ' . $total_pagina . ');"><i class="fas fa-step-forward"></i></a></li>';
	}
	$lista .= '</ul>';

	$arrayData['detalle'] = $detalleTabla;
	$arrayData['totales'] = $lista;

	echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
	mysqli_close($conection);
?>
