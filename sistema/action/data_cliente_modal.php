<?php 
	include "../../conexion.php";
	session_start();

	$por_pagina = isset($_POST['cantidad']) ? $_POST['cantidad'] : 5;
	$busqueda = isset($_POST['busqueda']) ? mysqli_escape_string($conection, $_POST['busqueda']) : '';
	$pagina = isset($_POST['pagina']) ? $_POST['pagina'] : 1;

	$sql_registe = mysqli_query($conection, "SELECT COUNT(*) as total_registro FROM cliente 
												WHERE (nit LIKE '%$busqueda%' OR nombre LIKE '%$busqueda%') 
												AND status = 1 ");
	$result_register = mysqli_fetch_array($sql_registe);
	$total_registro = $result_register['total_registro'];

	$desde = ($pagina - 1) * $por_pagina;
	$total_pagina = ceil($total_registro / $por_pagina);

	$query = mysqli_query($conection, "SELECT * FROM cliente WHERE
										(nit LIKE '%$busqueda%' OR nombre LIKE '%$busqueda%') 
										AND status = 1 
										ORDER BY nombre ASC 
										LIMIT $desde, $por_pagina");

	$result = mysqli_num_rows($query);
	$detalleTabla = '<table class="modal-results-table">
						<tr>
							<th>Ced./RUC</th>
							<th>Nombre</th>
							<th>Accion</th>
						</tr>';

	if ($result > 0) {
		while ($data = mysqli_fetch_assoc($query)) {
			$detalleTabla .= '<tr>
								<td>' . $data['nit'] . '</td>
								<td>' . $data['nombre'] . '</td>
								<td>
									<button type="button" class="btn-seleccionar-modal" onclick="seleccionarCliente(' . 
										$data['idcliente'] . ', \'' . 
										addslashes($data['nombre']) . '\', \'' . 
										$data['nit'] . '\', \'' . 
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
		$lista .= '<li><a href="1" onclick="event.preventDefault(); listaClienteModal(\'' . $busqueda . '\', 1);"><i class="fas fa-step-backward"></i></a></li>';
		$lista .= '<li><a href="' . ($pagina - 1) . '" onclick="event.preventDefault(); listaClienteModal(\'' . $busqueda . '\', ' . ($pagina - 1) . ');"><i class="fas fa-caret-left"></i></a></li>';
	}

	$cant = 2;
	$pagInicio = ($pagina > $cant) ? ($pagina - $cant) : 1;
	$pagRestantes = $total_pagina - $pagina;
	$pagFin = ($pagRestantes > $cant) ? ($pagina + $cant) : $total_pagina;

	for ($i = $pagInicio; $i <= $pagFin; $i++) {
		if ($i == $pagina) {
			$lista .= '<li class="pageSelected">' . $i . '</li>';
		} else {
			$lista .= '<li><a href="' . $i . '" onclick="event.preventDefault(); listaClienteModal(\'' . $busqueda . '\', ' . $i . ');">' . $i . '</a></li>';
		}
	}

	if ($pagina < $total_pagina) {
		$lista .= '<li><a href="' . ($pagina + 1) . '" onclick="event.preventDefault(); listaClienteModal(\'' . $busqueda . '\', ' . ($pagina + 1) . ');"><i class="fas fa-caret-right"></i></a></li>';
		$lista .= '<li><a href="' . ($total_pagina) . '" onclick="event.preventDefault(); listaClienteModal(\'' . $busqueda . '\', ' . $total_pagina . ');"><i class="fas fa-step-forward"></i></a></li>';
	}
	$lista .= '</ul>';

	$arrayData['detalle'] = $detalleTabla;
	$arrayData['totales'] = $lista;

	echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
	mysqli_close($conection);
?>
