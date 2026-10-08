<?php 

	include "../../conexion.php";
	session_start();

	 //Extraer datos del detalle_temp
		$query_conf = mysqli_query($conection,"SELECT moneda FROM configuracion");
		$result_conf = mysqli_num_rows($query_conf);

				if ($result_conf > 0) {
					$info_conf = mysqli_fetch_assoc($query_conf);
					$moned = $info_conf['moneda'];
				}

				$por_pagina = 9;
			//Buscador en tiempo real
			if (isset($_POST['busquedaProd'])) {

					$busqueda = mysqli_escape_string($conection,$_POST['busquedaProd']);

					$sql_registe = mysqli_query($conection,"SELECT COUNT(*) as total_registro FROM producto p
																LEFT JOIN categoria c ON p.categoria_id = c.idcategoria
																LEFT JOIN marca m ON p.marca_id = m.idmarca
																LEFT JOIN tamano t ON p.tamano_id = t.idtamano
																WHERE (
																		p.codigo LIKE '%$busqueda%' OR 
																		p.descripcion LIKE '%$busqueda%' OR
																		c.nombre LIKE '%$busqueda%' OR
																		m.nombre LIKE '%$busqueda%' OR
																		t.nombre LIKE '%$busqueda%') 
																AND p.status = 1 ");
			$result_register = mysqli_fetch_array($sql_registe);
			$total_registro = $result_register['total_registro'];

			if(empty($_POST['pagina']))
			{
				$pagina = 1;
			}else{
				$pagina = $_POST['pagina'];
			}

			$desde = ($pagina-1) * $por_pagina;
			$total_pagina = ceil($total_registro / $por_pagina);

				$query = mysqli_query($conection,"SELECT p.* FROM producto p
										LEFT JOIN categoria c ON p.categoria_id = c.idcategoria
										LEFT JOIN marca m ON p.marca_id = m.idmarca
										LEFT JOIN tamano t ON p.tamano_id = t.idtamano
										WHERE
										(
											p.codigo LIKE '%$busqueda%' OR 
											p.descripcion LIKE '%$busqueda%' OR
											c.nombre LIKE '%$busqueda%' OR
											m.nombre LIKE '%$busqueda%' OR
											t.nombre LIKE '%$busqueda%'
											 ) 
											AND
										p.status = 1 ORDER BY p.descripcion ASC LIMIT $desde,$por_pagina ");


				}
				
				$result = mysqli_num_rows($query);
				$lista = '';
				$tabla = '';
				$arrayData    = array();

				if ($result > 0) {
				  $tabla .= '<table class="tbl_venta_productos" style="width:100%; border-collapse:collapse; margin-bottom:10px;">
				  				<thead>
				  					<tr style="background:#3d7ba8; color:#fff;">
				  						<th style="padding:8px; text-align:left;">Código</th>
				  						<th style="padding:8px; text-align:left;">Cód. Int.</th>
				  						<th style="padding:8px; text-align:left;">Descripción</th>
				  						<th style="padding:8px; text-align:left;">Categoría</th>
				  						<th style="padding:8px; text-align:left;">Marca</th>
				  						<th style="padding:8px; text-align:left;">Tamaño</th>
				  						<th style="padding:8px; text-align:center;">Stock</th>
				  						<th style="padding:8px; text-align:right;">Costo</th>
				  						<th style="padding:8px; text-align:center;">Foto</th>
				  						<th style="padding:8px; text-align:center;">Acción</th>
				  					</tr>
				  				</thead>
				  				<tbody>';
				while ($data = mysqli_fetch_assoc($query)){
					if ($data['foto'] != 'img_producto.png') {
						$foto = './img/uploads/'.$data['foto'];
					}else{
						$foto = './img/'.$data['foto'];
					}
					$costo = formatCant($data['costo']);
					$cat_v = !empty($data['categoria']) ? htmlspecialchars($data['categoria']) : '<span style="color:#aaa;">Sin asignar</span>';
					$marca_v = !empty($data['marca']) ? htmlspecialchars($data['marca']) : '<span style="color:#aaa;">Sin asignar</span>';
					$tam_v = !empty($data['tamano']) ? htmlspecialchars($data['tamano']) : '<span style="color:#aaa;">Sin asignar</span>';
					$cod_int_v = !empty($data['codigo_interno']) ? htmlspecialchars($data['codigo_interno']) : '<span style="color:#aaa;">-</span>';

					$tabla .= '<tr style="border-bottom:1px solid #ddd; cursor:pointer;" onclick="infoProductAgregarCompra('.$data['codproducto'].');">
										<td style="padding:6px 8px;">'.htmlspecialchars($data['codigo']).'</td>
										<td style="padding:6px 8px;">'.$cod_int_v.'</td>
										<td style="padding:6px 8px; font-weight:bold;">'.htmlspecialchars($data['descripcion']).'</td>
										<td style="padding:6px 8px;">'.$cat_v.'</td>
										<td style="padding:6px 8px;">'.$marca_v.'</td>
										<td style="padding:6px 8px;">'.$tam_v.'</td>
										<td style="padding:6px 8px; text-align:center; font-weight:bold;" id="existencia_2'.$data['codproducto'].'">'.$data['existencia'].'</td>
										<td style="padding:6px 8px; text-align:right; font-weight:bold; color:#c0392b;" id="txt_precio_2'.$data['codproducto'].'">'.$moned.' '.$costo.'</td>
										<td style="padding:4px; text-align:center;"><img src="'.$foto.'" style="width:35px; height:35px; object-fit:cover; border-radius:3px;" alt="Foto"></td>
										<td style="padding:4px; text-align:center;">
											<a href="#" class="btn_new" style="margin:0; padding:3px 8px; font-size:11px; background:#27ae60; border-color:#27ae60;" onclick="event.stopPropagation(); event.preventDefault(); infoProductAgregarCompra('.$data['codproducto'].');"><i class="fas fa-plus"></i> Cargar Stock</a>
										</td>
									</tr>';
				}

				$tabla .= '</tbody></table>';
				$lista.='<ul>';

				if ($pagina > 1) {
					$lista.= '<li><a href="1"><i class="fas fa-step-backward"></i></a></li>
				<li><a href="'.($pagina-1).'"><i class="fas fa-caret-left"></i></a></li>';
				}

			//muestro de los enlaces 
			//cantidad de link hacia atras y adelante
 			$cant = 2;
 			//inicio de donde se va a mostrar los links
			$pagInicio = ($pagina > $cant) ? ($pagina - $cant) : 1;
			//condicion en la cual establecemos el fin de los links
			if ($total_pagina > $cant)
			{
				//conocer los links que hay entre el seleccionado y el final
				$pagRestantes = $total_pagina - $pagina;
				//defino el fin de los links
				$pagFin = ($pagRestantes > $cant) ? ($pagina + $cant) :$total_pagina;
			}
			else 
			{
				$pagFin = $total_pagina;
			}

				for ($i=$pagInicio; $i <= $pagFin; $i++) 
				{ 

						if ($i == $pagina) 
						{
							$lista.= '<li class="pageSelected">'.$i.'</a></li>';	
						}else{
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
			}else{
				echo 'error';
			}
			mysqli_close($conection);
		
		exit;

	?>