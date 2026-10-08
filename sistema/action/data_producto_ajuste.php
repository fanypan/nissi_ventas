<?php 
	// Buscador simplificado para Ajuste de Stock
	include "../../conexion.php";
	session_start();

	$por_pagina = 5;
	if (isset($_POST['busquedaProd'])) {
		$busqueda = mysqli_escape_string($conection,$_POST['busquedaProd']);
		$sql_registe = mysqli_query($conection,"SELECT COUNT(*) as total_registro FROM producto 
													WHERE (codigo LIKE '%$busqueda%' OR descripcion LIKE '%$busqueda%') 
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

		$query = mysqli_query($conection,"SELECT codproducto, codigo, descripcion, existencia FROM producto 
								WHERE (codigo LIKE '%$busqueda%' OR descripcion LIKE '%$busqueda%') 
								AND status = 1 ORDER BY descripcion ASC LIMIT $desde,$por_pagina ");
	}
	
	$result = mysqli_num_rows($query);
	$lista = '';
	$tabla = '';
	$arrayData = array();

	if ($result > 0) {
	  $tabla .= '<table class="tbl_venta" style="width: 100%; border: 1px solid #ccc; margin-top: 5px;">
	  				<thead>
						<tr>
							<th>Código</th>
							<th>Descripción</th>
							<th>Stock</th>
							<th style="width: 50px;">Sel.</th>
						</tr>
					</thead>
					<tbody>';
	while ($data = mysqli_fetch_assoc($query)){
		$tabla .= '<tr>
						<td>'.$data['codigo'].'</td>
						<td>'.$data['descripcion'].'</td>
						<td class="textcenter">'.floatval($data['existencia']).'</td>
						<td class="textcenter">
							<a href="#" class="link_edit" onclick="event.preventDefault(); seleccionarProductoAjuste('.$data['codproducto'].');"><i class="fas fa-check-circle"></i></a>
						</td>
					</tr>';
	}
	$tabla .= '</tbody></table>';

	$lista.='<ul>';

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
