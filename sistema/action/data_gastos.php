<?php 

	include "../../conexion.php";
	session_start();

//print_r($_POST);exit;
	 //Extraer datos del detalle_temp

	$query_conf = mysqli_query($conection,"SELECT moneda FROM configuracion");
		$result_conf = mysqli_num_rows($query_conf);
		$usuario = $_SESSION['idUser'];


			if ($result_conf > 0) {
				$info_conf = mysqli_fetch_assoc($query_conf);
				$moned = $info_conf['moneda'];
			}

			$por_pagina = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 10;
            if ($por_pagina <= 0) $por_pagina = 10;
            
            // Filtros de fecha y búsqueda
            $busqueda = isset($_POST['busqueda']) ? mysqli_real_escape_string($conection, $_POST['busqueda']) : '';
            $f_de = !empty($_POST['f_de']) ? mysqli_real_escape_string($conection, $_POST['f_de']) : date('Y-m-d', strtotime('-30 days'));
            $f_a = !empty($_POST['f_a']) ? mysqli_real_escape_string($conection, $_POST['f_a']) : date('Y-m-d');

            $where = " DATE(e.fecha) BETWEEN '$f_de' AND '$f_a' ";
            if ($busqueda != '') {
                $where .= " AND e.descripcion LIKE '%$busqueda%' ";
            }

            // Contar registros con filtro
            $sql_registe = mysqli_query($conection, "SELECT COUNT(*) as total_registro FROM egresos e WHERE $where");
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

            // CONSULTA MEJORADA: Incluir tipo de egreso e información de insumos
            $query = mysqli_query($conection,"SELECT e.*, 
                                    CASE e.tipo_egreso 
                                        WHEN 1 THEN 'Gasto General'
                                        WHEN 2 THEN 'Insumo'
                                        ELSE 'Gasto General'
                                    END as tipo_egreso_texto,
                                    i.nombre as nombre_insumo,
                                    i.unidad_medida as unidad_insumo
                                FROM egresos e
                                LEFT JOIN insumos i ON e.id_insumo = i.id
                                WHERE $where 
                                ORDER BY e.fecha DESC 
                                LIMIT $desde,$por_pagina ");

			$result = mysqli_num_rows($query);
			$lista = '';
			$detalleTabla = '';
			$arrayData    = array();

			// TABLA MEJORADA: Incluir columna de tipo
			$detalleTabla.='
				<table>
					<tr>
						<th>Fecha</th>
						<th>Tipo</th>
						<th>Descripción</th>
						<th>Local</th>
						<th>Cantidad</th>
						<th>Usuario</th>
						<th style="width: 150px;">Acciones</th>
					</tr>';

			if ($result > 0) {
			  
			while ($data = mysqli_fetch_assoc($query)){
				
				// FILA MEJORADA: Mostrar tipo de egreso con color
				$tipo_badge_color = ($data['tipo_egreso'] == 2) ? '#3498db' : '#27ae60';
				$tipo_texto = isset($data['tipo_egreso_texto']) ? $data['tipo_egreso_texto'] : 'Gasto General';
				
				$detalleTabla .= '<tr>
				                <td>'.date('d/m/Y H:i', strtotime($data['fecha'])).'</td>
				                <td><span style="padding: 3px 8px; border-radius: 3px; font-size: 11px; background: '.$tipo_badge_color.'; color: white;">'.$tipo_texto.'</span></td>
				                <td>'.$data['descripcion'].'</td>
				                <td>'.$data['establecimiento'].'</td>
				                <td class="">'.$moned.' '.formatCant($data['cantidad']).'</td>
				                <td class="">'.$data['usuario'].'</td>
				                <td class="">';

				             if ($_SESSION['rol'] == 1) {
                                // Solo el administrador puede editar y eliminar
				                $detalleTabla.='<a class="link_edit" style="color:#3498db;" href="javascript:infoEditarEgreso('.$data['id'].');"><i class="fas fa-edit"></i> </a> | 
                                                <a class="link_delete" style="color:#e74c3c;" href="javascript:infoAnularEgreso('.$data['id'].');"><i class="fas fa-trash-alt"></i> </a>';
				             } else {
                                $detalleTabla.='<span style="color: #ccc;">No permitido</span>';
                             }
                             $detalleTabla.='</td></tr>';
			}
			$detalleTabla.='</table>';

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

			$arrayData['detalle'] = $detalleTabla;
			$arrayData['totales'] = $lista;

			echo json_encode($arrayData,JSON_UNESCAPED_UNICODE);	               
		}else{
			echo 'error';
		}
		mysqli_close($conection);
		exit;
	?>
