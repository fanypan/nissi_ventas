<?php 
	include "../../conexion.php";
	session_start();

	$por_pagina = 9;
	if (isset($_POST['busquedaInsumo'])) {
		$busqueda = mysqli_escape_string($conection,$_POST['busquedaInsumo']);
		$sql_registe = mysqli_query($conection,"SELECT COUNT(*) as total_registro FROM insumos 
										WHERE (nombre LIKE '%$busqueda%') 
										AND status = 1 ");
		$result_register = mysqli_fetch_array($sql_registe);
		$total_registro = $result_register['total_registro'];

		if(empty($_POST['pagina'])) {
			$pagina = 1;
		} else {
			$pagina = $_POST['pagina'];
		}

		$desde = ($pagina-1) * $por_pagina;
		$total_pagina = ceil($total_registro / $por_pagina);

		$query = mysqli_query($conection,"SELECT * FROM insumos WHERE
							(nombre LIKE '%$busqueda%') 
							AND status = 1 ORDER BY nombre ASC LIMIT $desde,$por_pagina ");
	}
	
	$result = mysqli_num_rows($query);
	$lista = '';
	$tabla = '';
	$arrayData = array();

	if ($result > 0) {
	  $tabla .= '<div class="divContainer">
					<div class="dashboardventa">';
	while ($data = mysqli_fetch_assoc($query)){
		$nombre_js = addslashes($data['nombre']);
		// Card optimizada con tamaño fijo y flexbox para evitar amontonamiento
		$tabla .= '<div style="display:inline-block; width: 140px; margin: 5px; vertical-align: top; background: #fff; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
						<div style="padding: 10px; text-align: center; height: 100px; display: flex; flex-direction: column; justify-content: space-between; cursor: pointer;" onclick="seleccionarInsumoUso('.$data['id'].');">
							<strong style="display: block; font-size: 13px; color: #333; margin-bottom: 5px; height: 32px; overflow: hidden;">'.$data['nombre'].'</strong>
							<div style="font-size: 12px; color: #666;">
								<span style="display:block;">Stock:</span>
								<span style="font-weight: bold; color: #2c3e50;">'.floatval($data['stock']).' '.$data['unidad_medida'].'</span>
							</div>
						</div>';
		if ($_SESSION['rol'] == 1) {
			$tabla .= '<div style="border-top: 1px solid #eee; padding: 5px; display: flex; justify-content: space-around; background: #fafafa;">
							<a href="#" onclick="event.preventDefault(); event.stopPropagation(); infoEditarInsumo('.$data['id'].');" style="color:#3498db; font-size:11px; text-decoration: none;" title="Editar"><i class="fas fa-edit"></i></a>
							<a href="#" onclick="event.preventDefault(); event.stopPropagation(); confirmarEliminarInsumo('.$data['id'].', \''.$nombre_js.'\');" style="color:#e74c3c; font-size:11px; text-decoration: none;" title="Eliminar"><i class="fas fa-trash-alt"></i></a>
						</div>';
		}
		$tabla .= '</div>';
	}

	$lista.='</div></div><ul>';

	if ($pagina > 1) {
		$lista.= '<li><a href="1"><i class="fas fa-step-backward"></i></a></li>
	<li><a href="'.($pagina-1).'"><i class="fas fa-caret-left"></i></a></li>';
	}

	$cant = 2;
	$pagInicio = ($pagina > $cant) ? ($pagina - $cant) : 1;
	if ($total_pagina > $cant) {
		$pagRestantes = $total_pagina - $pagina;
		$pagFin = ($pagRestantes > $cant) ? ($pagina + $cant) :$total_pagina;
	} else {
		$pagFin = $total_pagina;
	}

	for ($i=$pagInicio; $i <= $pagFin; $i++) { 
		if ($i == $pagina) {
			$lista.= '<li class="pageSelected">'.$i.'</a></li>';	
		} else {
			$lista.= '<li><a href="'.$i.'">'.$i.'</a></li>';
		}
	}

	if ($pagina < $pagFin) {
		$lista.= '<li><a href="'.($pagina+1).'"><i class="fas fa-caret-right"></i></a></li>
	<li><a href="'.($total_pagina).'"><i class="fas fa-step-forward"></i></a></li>';
	}
	$lista.='</ul>';

	$arrayData['detalle'] = $tabla;
	$arrayData['totales'] = $lista;

	echo json_encode($arrayData,JSON_UNESCAPED_UNICODE);	               
	} else {
		echo 'error';
	}
	mysqli_close($conection);
	exit;
?>
