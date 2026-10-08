<?php
/**
 * Acciones AJAX para el módulo de Ajuste de Stock y Motivos
 */

// ============================================
// BUSCAR PRODUCTO PARA AJUSTE (Flexible)
// ============================================
if ($_POST['action'] == 'infoProductoStock') {
    if (!empty($_POST['producto'])) {
        $busqueda = mysqli_real_escape_string($conection, $_POST['producto']);
        
        $query = mysqli_query($conection, "SELECT codproducto, codigo, descripcion, existencia 
                                            FROM producto 
                                            WHERE (codigo = '$busqueda' OR descripcion LIKE '%$busqueda%') 
                                            AND status = 1 LIMIT 1");
        
        if ($query && mysqli_num_rows($query) > 0) {
            $data = mysqli_fetch_assoc($query);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            echo "error";
        }
    } else {
        echo "error";
    }
    mysqli_close($conection);
    exit;
}

// ============================================
// LISTAR HISTORIAL DE AJUSTES
// ============================================
if ($_POST['action'] == 'listaHistorialAjustes') {
    $busqueda = isset($_POST['busqueda']) ? mysqli_real_escape_string($conection, $_POST['busqueda']) : '';
    $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
    $por_pagina = 10;
    
    $where = " WHERE 1=1 ";
    if ($busqueda != '') {
        $where .= " AND (p.descripcion LIKE '%$busqueda%' OR m.descripcion LIKE '%$busqueda%') ";
    }
    
    // Contar total para paginador
    $sql_registe = mysqli_query($conection, "SELECT COUNT(*) as total_registro 
                                            FROM ajuste_stock a
                                            INNER JOIN producto p ON a.codproducto = p.codproducto
                                            INNER JOIN motivos_ajuste m ON a.motivo_id = m.id
                                            $where");
    $result_register = mysqli_fetch_assoc($sql_registe);
    $total_registro = $result_register['total_registro'];
    
    $total_paginas = ceil($total_registro / $por_pagina);
    $desde = ($pagina - 1) * $por_pagina;
    
    $query = mysqli_query($conection, "SELECT a.id, a.fecha, p.codigo, p.descripcion as producto, a.tipo_movimiento, a.cantidad, m.descripcion as motivo, u.nombre as usuario, a.nota
                                        FROM ajuste_stock a
                                        INNER JOIN producto p ON a.codproducto = p.codproducto
                                        INNER JOIN motivos_ajuste m ON a.motivo_id = m.id
                                        INNER JOIN usuario u ON a.usuario_id = u.idusuario
                                        $where
                                        ORDER BY a.fecha DESC LIMIT $desde, $por_pagina");
    
    $lista = '';
    if (mysqli_num_rows($query) > 0) {
        while ($data = mysqli_fetch_assoc($query)) {
            $tipo = ($data['tipo_movimiento'] == 'entrada') ? '<span class="textsuccess">Entrada (+)</span>' : '<span class="texterror">Salida (-)</span>';
            $lista .= '<tr>
                        <td>'.$data['id'].'</td>
                        <td>'.$data['fecha'].'</td>
                        <td>['.$data['codigo'].'] '.$data['producto'].'</td>
                        <td>'.$tipo.'</td>
                        <td>'.$data['cantidad'].'</td>
                        <td>'.$data['motivo'].'</td>
                        <td>'.$data['usuario'].'</td>
                    </tr>';
        }
    } else {
        $lista = '<tr><td colspan="7" class="textcenter">No hay registros</td></tr>';
    }
    
    // Generar paginador
    $paginador = '<ul>';
    if ($pagina != 1) {
        $paginador .= '<li><a href="#" onclick="listaHistorialAjustes(\''.$busqueda.'\', 1)"><i class="fas fa-step-backward"></i></a></li>';
        $paginador .= '<li><a href="#" onclick="listaHistorialAjustes(\''.$busqueda.'\', '.($pagina - 1).')"><i class="fas fa-backward"></i></a></li>';
    }
    for ($i = 1; $i <= $total_paginas; $i++) {
        if ($i == $pagina) {
            $paginador .= '<li class="pageSelected">'.$i.'</li>';
        } else {
            if ($i <= 5 || $i >= $total_paginas - 2 || ($i >= $pagina - 2 && $i <= $pagina + 2)) {
                $paginador .= '<li><a href="#" onclick="listaHistorialAjustes(\''.$busqueda.'\', '.$i.')">'.$i.'</a></li>';
            }
        }
    }
    if ($pagina != $total_paginas && $total_paginas > 0) {
        $paginador .= '<li><a href="#" onclick="listaHistorialAjustes(\''.$busqueda.'\', '.($pagina + 1).')"><i class="fas fa-forward"></i></a></li>';
        $paginador .= '<li><a href="#" onclick="listaHistorialAjustes(\''.$busqueda.'\', '.$total_paginas.')"><i class="fas fa-step-forward"></i></a></li>';
    }
    $paginador .= '</ul>';
    
    echo json_encode(array('tabla' => $lista, 'paginador' => $paginador), JSON_UNESCAPED_UNICODE);
    mysqli_close($conection);
    exit;
}

// ============================================
// PROCESAR AJUSTE DE STOCK
// ============================================
if ($_POST['action'] == 'procesarAjuste') {
    $producto_id = (int)$_POST['producto_id'];
    $cantidad = (float)$_POST['cantidad'];
    $tipo = ($_POST['tipo'] == '1') ? 'entrada' : 'salida';
    $motivo_id = (int)$_POST['motivo_id'];
    $nota = mysqli_real_escape_string($conection, $_POST['nota']);
    $user = $_SESSION['idUser'];
    
    if($cantidad <= 0){
        echo json_encode(array('cod' => '06', 'msg' => 'Error: La cantidad debe ser mayor a cero.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 1. Obtener stock actual
    $query_product = mysqli_query($conection, "SELECT existencia FROM producto WHERE codproducto = $producto_id");
    if ($query_product && mysqli_num_rows($query_product) > 0) {
        $data_product = mysqli_fetch_assoc($query_product);
        $stock_actual = $data_product['existencia'];
    } else {
        echo json_encode(array('cod' => '05', 'msg' => 'Error: Producto no encontrado.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 2. Calcular nuevo stock
    if ($tipo == 'entrada') {
        $nuevo_stock = $stock_actual + $cantidad;
    } else {
        $nuevo_stock = $stock_actual - $cantidad;
    }
    
    if ($nuevo_stock < 0) {
        echo json_encode(array('cod' => '01', 'msg' => 'Error: El stock resultante no puede ser menor a cero.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 3. Insertar registro de ajuste
    $query_insert = mysqli_query($conection, "INSERT INTO ajuste_stock(codproducto, usuario_id, cantidad, tipo_movimiento, motivo_id, nota) 
                                              VALUES($producto_id, $user, $cantidad, '$tipo', $motivo_id, '$nota')");
    
    if ($query_insert) {
        // 4. Actualizar stock en tabla producto
        $query_upgrade = mysqli_query($conection, "UPDATE producto SET existencia = $nuevo_stock WHERE codproducto = $producto_id");
        if ($query_upgrade) {
            echo json_encode(array('cod' => '00', 'msg' => 'Ajuste procesado correctamente. Nuevo stock: ' . $nuevo_stock), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array('cod' => '02', 'msg' => 'Error al actualizar el stock del producto.'), JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(array('cod' => '03', 'msg' => 'Error al registrar el ajuste en el historial: ' . mysqli_error($conection)), JSON_UNESCAPED_UNICODE);
    }
    
    mysqli_close($conection);
    exit;
}

// ============================================
// MANTENIMIENTO DE MOTIVOS
// ============================================

// Listar Motivos
if ($_POST['action'] == 'listaMotivos') {
    $busqueda = isset($_POST['busqueda']) ? mysqli_real_escape_string($conection, $_POST['busqueda']) : '';
    $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
    $por_pagina = 10;
    
    $where = " WHERE status = 1 ";
    if ($busqueda != '') {
        $where .= " AND descripcion LIKE '%$busqueda%' ";
    }
    
    $sql_registe = mysqli_query($conection, "SELECT COUNT(*) as total_registro FROM motivos_ajuste $where");
    $result_register = mysqli_fetch_assoc($sql_registe);
    $total_registro = $result_register['total_registro'];
    
    $total_paginas = ceil($total_registro / $por_pagina);
    $desde = ($pagina - 1) * $por_pagina;
    
    $query = mysqli_query($conection, "SELECT * FROM motivos_ajuste $where ORDER BY id DESC LIMIT $desde, $por_pagina");
    
    $lista = '<table class="tbl_venta">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>';
    
    if (mysqli_num_rows($query) > 0) {
        while ($data = mysqli_fetch_assoc($query)) {
            $lista .= '<tr>
                        <td>'.$data['id'].'</td>
                        <td>'.$data['descripcion'].'</td>
                        <td>'.$data['date_add'].'</td>
                        <td>
                            <a class="link_delete" href="#" onclick="event.preventDefault(); del_motivo('.$data['id'].');"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>';
        }
    } else {
        $lista .= '<tr><td colspan="4" class="textcenter">No hay registros</td></tr>';
    }
    $lista .= '</tbody></table>';
    
    // Generar paginador similar al anterior...
    $paginador = '<ul>';
    if ($pagina != 1) {
        $paginador .= '<li><a href="#" onclick="listaMotivos(\''.$busqueda.'\', 1)"><i class="fas fa-step-backward"></i></a></li>';
    }
    for ($i = 1; $i <= $total_paginas; $i++) {
        if ($i == $pagina) {
            $paginador .= '<li class="pageSelected">'.$i.'</li>';
        } else {
            $paginador .= '<li><a href="#" onclick="listaMotivos(\''.$busqueda.'\', '.$i.')">'.$i.'</a></li>';
        }
    }
    if ($pagina != $total_paginas && $total_paginas > 0) {
        $paginador .= '<li><a href="#" onclick="listaMotivos(\''.$busqueda.'\', '.$total_paginas.')"><i class="fas fa-step-forward"></i></a></li>';
    }
    $paginador .= '</ul>';
    
    echo json_encode(array('tabla' => $lista, 'paginador' => $paginador), JSON_UNESCAPED_UNICODE);
    mysqli_close($conection);
    exit;
}

// Crear Motivo
if ($_POST['action'] == 'crearMotivo') {
    $motivo = mysqli_real_escape_string($conection, $_POST['motivo']);
    if (empty($motivo)) {
        echo json_encode(array('cod' => '01', 'msg' => 'La descripción es obligatoria.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $query_insert = mysqli_query($conection, "INSERT INTO motivos_ajuste(descripcion) VALUES('$motivo')");
    if ($query_insert) {
        echo json_encode(array('cod' => '00', 'msg' => 'Motivo creado correctamente.'), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '02', 'msg' => 'Error al crear el motivo.'), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}

// Eliminar Motivo (borrado lógico)
if ($_POST['action'] == 'delMotivo') {
    $id = (int)$_POST['id'];
    $query_delete = mysqli_query($conection, "UPDATE motivos_ajuste SET status = 0 WHERE id = $id");
    if ($query_delete) {
        echo 'ok';
    } else {
        echo 'error';
    }
    mysqli_close($conection);
    exit;
}

// Obtener todos los motivos para el select del ajuste
if ($_POST['action'] == 'getMotivosSelect') {
    $query = mysqli_query($conection, "SELECT id, descripcion FROM motivos_ajuste WHERE status = 1 ORDER BY descripcion ASC");
    $data = array();
    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    mysqli_close($conection);
    exit;
}
