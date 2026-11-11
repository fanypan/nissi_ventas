<?php 

	include "../../conexion.php";
	ensure_session();
	//print_r($_POST);exit;

	 //Extraer datos del detalle_temp
		$query_conf = mysqli_query($conection,"SELECT moneda FROM configuracion");
		$result_conf = mysqli_num_rows($query_conf);

				if ($result_conf > 0) {
					$info_conf = mysqli_fetch_assoc($query_conf);
					$moned = $info_conf['moneda'];
				}

		$por_pagina = $_POST['cantidad'];

			//Buscador en tiempo real
			if (isset($_POST['busqueda'])) {

					$busqueda = mysqli_escape_string($conection,$_POST['busqueda']);

					$sql_registe = mysqli_query($conection,"SELECT COUNT(*) as total_registro FROM producto p
																LEFT JOIN proveedor pr ON p.proveedor = pr.codproveedor
																LEFT JOIN categoria c ON p.categoria_id = c.idcategoria
																LEFT JOIN marca m ON p.marca_id = m.idmarca
																LEFT JOIN tamano t ON p.tamano_id = t.idtamano
																WHERE (
																		p.codigo LIKE '%$busqueda%' OR
																		p.descripcion LIKE '%$busqueda%' OR 
																		pr.proveedor LIKE '%$busqueda%' OR
																		c.nombre LIKE '%$busqueda%' OR
																		m.nombre LIKE '%$busqueda%' OR
																		t.nombre LIKE '%$busqueda%'
											 						   ) 
																AND p.status != 10 ");
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

				$query = mysqli_query($conection,"SELECT p.codproducto, p.codigo, p.codigo_interno, p.descripcion, p.costo, p.precio, p.existencia, pr.proveedor, p.foto, p.status, c.nombre as categoria, m.nombre as marca, t.nombre as tamano 
						FROM producto p
						INNER JOIN proveedor pr ON p.proveedor = pr.codproveedor
						LEFT JOIN categoria c ON p.categoria_id = c.idcategoria
						LEFT JOIN marca m ON p.marca_id = m.idmarca
						LEFT JOIN tamano t ON p.tamano_id = t.idtamano
						WHERE (p.codigo LIKE '%$busqueda%' OR
						p.codigo_interno LIKE '%$busqueda%' OR
						p.descripcion LIKE '%$busqueda%' OR 
						pr.proveedor LIKE '%$busqueda%' OR
						c.nombre LIKE '%$busqueda%' OR
						m.nombre LIKE '%$busqueda%' OR
						t.nombre LIKE '%$busqueda%')
						AND p.status != 10 ORDER BY p.codproducto DESC LIMIT $desde,$por_pagina ");
				}
				
				$result = mysqli_num_rows($query);
				$lista = '';
				$tabla = '';
				$arrayData    = array();

				$tabla.='<table>
						<tr>
							<th style="display:none;">ID</th>
							<th>Cód. Barras</th>
							<th>Cód. Interno</th>
							<th>Descripción</th>
							<th>Categoría</th>
							<th>Marca</th>
							<th>Tamaño</th>
							<th>Stock</th>
							<th>Costo</th>
							<th>Precio</th>
							<th>Proveedor</th>
							<th>Foto</th>';
				if ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2 ) {
							   $tabla.='<th>Acciones</th>';
									}

				if ($result > 0) {
				  
				while ($data = mysqli_fetch_assoc($query)){
					if ($data['foto'] != 'img_producto.png') {
							$foto = './img/uploads/'.$data['foto'];
						}else{
							$foto = './img/'.$data['foto'];
						}
						$costo = formatCant($data['costo']);
						$precio = formatCant($data['precio']);
						$categoria_txt = !empty($data['categoria']) ? $data['categoria'] : '<span style="color:grey;">Sin asignar</span>';
						$marca_txt = !empty($data['marca']) ? $data['marca'] : '<span style="color:grey;">Sin asignar</span>';
						$tamano_txt = !empty($data['tamano']) ? $data['tamano'] : '<span style="color:grey;">Sin asignar</span>';
						$cod_int_txt = !empty($data['codigo_interno']) ? $data['codigo_interno'] : '<span style="color:#bbb;">-</span>';

					if ($data['status'] != 0) {
						$tabla .= '</tr><tr>
						                <td style="display:none;">'.$data['codproducto'].'</td>
						                <td>'.$data['codigo'].'</td>
						                <td>'.$cod_int_txt.'</td>
						                <td>'.$data['descripcion'].'</td>
						                <td>'.$categoria_txt.'</td>
						                <td>'.$marca_txt.'</td>
						                <td>'.$tamano_txt.'</td>
						                <td class="">'.$data['existencia'].'</td>
						                <td class="">'.$moned.' '.$costo.'</td>
						                <td class="">'.$moned.' '.$precio.'</td>
						                <td class="">'.$data['proveedor'].'</td>
						                <td class="img_producto"><img src="'.$foto.'" alt="'.$data['descripcion'].'"></td>';
						if ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2 ) {					
					$tabla.='<td class="">
										<a class="link_edit" style="color:#27ae60; margin-right:4px;" href="#" title="Imprimir Código de Barras" onclick="event.preventDefault(); modalImprimirBarcode(\''.addslashes($data['codproducto']).'\', \''.addslashes($data['codigo']).'\', \''.addslashes($data['descripcion']).'\', \''.addslashes($data['precio']).'\', \''.addslashes($data['codigo_interno'] ?? '').'\');"><i class="fas fa-barcode"></i> Barcode</a>
										|
										<a class="link_edit" href="#" onclick="event.preventDefault(); editarProducto('.$data['codproducto'].');"><i class="fas fa-edit"></i> Editar</a>
										|	
										<a class="link_delete del_product"  href="#" onclick="event.preventDefault(); infoEliminarProducto('.$data['codproducto'].');"><i class="fa fa-ban fa-w-16"></i> Desactivar</a>';	                
					}

					}else{
						$tabla .= '<tr style="color:grey;">
						                <td style="display:none;">'.$data['codproducto'].'</td>
						                <td>'.$data['codigo'].'</td>
						                <td>'.$cod_int_txt.'</td>
						                <td>'.$data['descripcion'].'</td>
						                <td>'.$categoria_txt.'</td>
						                <td>'.$marca_txt.'</td>
						                <td>'.$tamano_txt.'</td>
						                <td class="">'.$data['existencia'].'</td>
						                <td class="">'.$moned.' '.$costo.'</td>
						                <td class="">'.$moned.' '.$precio.'</td>
						                <td class="">'.$data['proveedor'].'</td>
						                <td class="img_producto"><img src="'.$foto.'" alt="'.$data['descripcion'].'"></td>';
					if ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2 ){
						$tabla .= '<td class=""><a class="link_delete del_product" style="color:grey;"  ><i class="fa fa-ban fa-w-16"></i> Desactivado</a>
						                |
						                <a class="link_add del_product"  href="#" onclick="event.preventDefault(); infoActivarProducto('.$data['codproducto'].');"><i class="fas fa-plus"></i> Activar</a></td></tr>';}	                
						     
					}
						
				}

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