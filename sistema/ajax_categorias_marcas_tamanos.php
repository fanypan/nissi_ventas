<?php
// ============================================
// MODULO DE CATEGORIAS, MARCAS Y TAMAÑOS
// ============================================

// Asegurar la conexión si el archivo es llamado directamente
if (!isset($conection) || !$conection) {
    include_once __DIR__ . '/../conexion.php';
}

// --- CATEGORIAS ---
if ($_POST['action'] == 'listaCategorias') {
    $busqueda = isset($_POST['busqueda']) ? mysqli_real_escape_string($conection, $_POST['busqueda']) : '';
    $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
    $por_pagina = 10;
    
    $where = " WHERE status = 1 ";
    if ($busqueda != '') {
        $where .= " AND nombre LIKE '%$busqueda%' ";
    }
    
    $sql_registe = mysqli_query($conection, "SELECT COUNT(*) as total_registro FROM categoria $where");
    $result_register = mysqli_fetch_assoc($sql_registe);
    $total_registro = $result_register['total_registro'];
    
    $total_paginas = ceil($total_registro / $por_pagina);
    $desde = ($pagina - 1) * $por_pagina;
    
    $query = mysqli_query($conection, "SELECT * FROM categoria $where ORDER BY idcategoria DESC LIMIT $desde, $por_pagina");
    
    $lista = '<table class="tbl_venta">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>';
    
    if (mysqli_num_rows($query) > 0) {
        while ($data = mysqli_fetch_assoc($query)) {
            $lista .= '<tr>
                        <td>'.$data['idcategoria'].'</td>
                        <td>'.$data['nombre'].'</td>
                        <td>
                            <a class="link_edit" href="#" onclick="event.preventDefault(); editar_categoria('.$data['idcategoria'].');"><i class="fas fa-edit"></i></a>
                            |
                            <a class="link_delete" href="#" onclick="event.preventDefault(); del_categoria('.$data['idcategoria'].');"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>';
        }
    } else {
        $lista .= '<tr><td colspan="3" class="textcenter">No hay registros</td></tr>';
    }
    $lista .= '</tbody></table>';
    
    $paginador = '<ul>';
    if ($pagina != 1) {
        $paginador .= '<li><a href="#" onclick="listaCategorias(\''.$busqueda.'\', 1)"><i class="fas fa-step-backward"></i></a></li>';
    }
    for ($i = 1; $i <= $total_paginas; $i++) {
        if ($i == $pagina) {
            $paginador .= '<li class="pageSelected">'.$i.'</li>';
        } else {
            $paginador .= '<li><a href="#" onclick="listaCategorias(\''.$busqueda.'\', '.$i.')">'.$i.'</a></li>';
        }
    }
    if ($pagina != $total_paginas && $total_paginas > 0) {
        $paginador .= '<li><a href="#" onclick="listaCategorias(\''.$busqueda.'\', '.$total_paginas.')"><i class="fas fa-step-forward"></i></a></li>';
    }
    $paginador .= '</ul>';
    
    echo json_encode(array('tabla' => $lista, 'paginador' => $paginador), JSON_UNESCAPED_UNICODE);
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'crearCategoria') {
    $nombre = mysqli_real_escape_string($conection, $_POST['nombre']);
    if (empty($nombre)) {
        echo json_encode(array('cod' => '01', 'msg' => 'El nombre es obligatorio.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $query_insert = mysqli_query($conection, "INSERT INTO categoria(nombre) VALUES('$nombre')");
    if ($query_insert) {
        $categoria_id = mysqli_insert_id($conection);
        
        // Registrar marcas asociadas
        if (!empty($_POST['marcas']) && is_array($_POST['marcas'])) {
            foreach ($_POST['marcas'] as $marca_id) {
                $marca_id = (int)$marca_id;
                mysqli_query($conection, "INSERT INTO categoria_marca (categoria_id, marca_id) VALUES ($categoria_id, $marca_id)");
            }
        }
        
        // Registrar tamaños asociados
        if (!empty($_POST['tamanos']) && is_array($_POST['tamanos'])) {
            foreach ($_POST['tamanos'] as $tam_id) {
                $tam_id = (int)$tam_id;
                mysqli_query($conection, "INSERT INTO categoria_tamano (categoria_id, tamano_id) VALUES ($categoria_id, $tam_id)");
            }
        }
        echo json_encode(array('cod' => '00', 'msg' => 'Categoría creada correctamente.'), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '02', 'msg' => 'Error al crear la categoría.'), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'editarCategoria') {
    $id = (int)$_POST['id'];
    $query = mysqli_query($conection, "SELECT * FROM categoria WHERE idcategoria = $id");
    $data = mysqli_fetch_assoc($query);
    
    // Marcas asociadas
    $query_marcas = mysqli_query($conection, "SELECT marca_id FROM categoria_marca WHERE categoria_id = $id");
    $marcas = [];
    while ($row = mysqli_fetch_assoc($query_marcas)) {
        $marcas[] = (int)$row['marca_id'];
    }

    // Todas las marcas disponibles
    $query_all_m = mysqli_query($conection, "SELECT idmarca, nombre FROM marca WHERE status = 1 ORDER BY nombre ASC");
    $todas_marcas = [];
    while ($row = mysqli_fetch_assoc($query_all_m)) {
        $todas_marcas[] = $row;
    }
    
    // Tamaños asociados
    $query_tamanos = mysqli_query($conection, "SELECT tamano_id FROM categoria_tamano WHERE categoria_id = $id");
    $tamanos = [];
    while ($row = mysqli_fetch_assoc($query_tamanos)) {
        $tamanos[] = (int)$row['tamano_id'];
    }
    
    // Todos los tamaños disponibles (si id>0 traer solo tamaños asociados a la categoría; si id==0 traer todos)
    $todos_tamanos = [];
    if ($id > 0) {
        $query_all_t = mysqli_query($conection, "SELECT t.idtamano, t.nombre FROM tamano t INNER JOIN categoria_tamano ct ON t.idtamano = ct.tamano_id WHERE ct.categoria_id = $id AND t.status = 1 ORDER BY t.nombre ASC");
    } else {
        $query_all_t = mysqli_query($conection, "SELECT idtamano, nombre FROM tamano WHERE status=1 ORDER BY nombre ASC");
    }
    while ($row = mysqli_fetch_assoc($query_all_t)) {
        $todos_tamanos[] = $row;
    }
    
    echo json_encode(array('categoria' => $data, 'marcas' => $marcas, 'tamanos' => $tamanos, 'todas_marcas' => $todas_marcas, 'todos_tamanos' => $todos_tamanos), JSON_UNESCAPED_UNICODE);
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'actualizarCategoria') {
    $id = (int)$_POST['id'];
    $nombre = mysqli_real_escape_string($conection, $_POST['nombre']);
    if (empty($nombre)) {
        echo json_encode(array('cod' => '01', 'msg' => 'El nombre es obligatorio.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $query_update = mysqli_query($conection, "UPDATE categoria SET nombre = '$nombre' WHERE idcategoria = $id");
    if ($query_update) {
        // Eliminar asociaciones previas
        mysqli_query($conection, "DELETE FROM categoria_marca WHERE categoria_id = $id");
        mysqli_query($conection, "DELETE FROM categoria_tamano WHERE categoria_id = $id");
        
        // Registrar marcas asociadas
        if (!empty($_POST['marcas']) && is_array($_POST['marcas'])) {
            foreach ($_POST['marcas'] as $marca_id) {
                $marca_id = (int)$marca_id;
                mysqli_query($conection, "INSERT INTO categoria_marca (categoria_id, marca_id) VALUES ($id, $marca_id)");
            }
        }
        
        // Registrar tamaños asociados
        if (!empty($_POST['tamanos']) && is_array($_POST['tamanos'])) {
            foreach ($_POST['tamanos'] as $tam_id) {
                $tam_id = (int)$tam_id;
                mysqli_query($conection, "INSERT INTO categoria_tamano (categoria_id, tamano_id) VALUES ($id, $tam_id)");
            }
        }
        
        echo json_encode(array('cod' => '00', 'msg' => 'Categoría actualizada correctamente.'), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '02', 'msg' => 'Error al actualizar la categoría.'), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'getMarcasYTamanosCheckboxes') {
    // Obtener marcas
    $query_m = mysqli_query($conection, "SELECT idmarca, nombre FROM marca WHERE status = 1 ORDER BY nombre ASC");
    $marcas = [];
    while ($row = mysqli_fetch_assoc($query_m)) {
        $marcas[] = $row;
    }
    
    // Obtener tamaños
    $query_t = mysqli_query($conection, "SELECT idtamano, nombre FROM tamano WHERE status = 1 ORDER BY nombre ASC");
    $tamanos = [];
    while ($row = mysqli_fetch_assoc($query_t)) {
        $tamanos[] = $row;
    }
    
    echo json_encode(array('marcas' => $marcas, 'tamanos' => $tamanos), JSON_UNESCAPED_UNICODE);
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'getMarcasYTamanosPorCategoria') {
    $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
    
    $marca_options = '<option value="">Seleccionar Marca</option>';
    $tamano_options = '<option value="">Seleccionar Tamaño</option>';
    
    if ($categoria_id > 0) {
        // Mostrar todas las marcas activas, sin depender de la categoría seleccionada
        $query_m_all = mysqli_query($conection, "SELECT idmarca, nombre FROM marca WHERE status = 1 ORDER BY nombre ASC");
        while ($data_m = mysqli_fetch_assoc($query_m_all)) {
            $marca_options .= '<option value="' . $data_m['idmarca'] . '">' . $data_m['nombre'] . '</option>';
        }

        // Tamaños asociados a la categoria
        $query_t = mysqli_query($conection, "SELECT t.idtamano, t.nombre FROM tamano t
                                             INNER JOIN categoria_tamano ct ON t.idtamano = ct.tamano_id
                                             WHERE ct.categoria_id = $categoria_id AND t.status = 1 ORDER BY t.nombre ASC");
        if (mysqli_num_rows($query_t) > 0) {
            while ($data_t = mysqli_fetch_assoc($query_t)) {
                $tamano_options .= '<option value="' . $data_t['idtamano'] . '">' . $data_t['nombre'] . '</option>';
            }
        } else {
            // Si no hay especificos, cargar todos los tamaños
            $query_t_all = mysqli_query($conection, "SELECT idtamano, nombre FROM tamano WHERE status = 1 ORDER BY nombre ASC");
            while ($data_t = mysqli_fetch_assoc($query_t_all)) {
                $tamano_options .= '<option value="' . $data_t['idtamano'] . '">' . $data_t['nombre'] . '</option>';
            }
        }
    }
    
    echo json_encode(array('marcas' => $marca_options, 'tamanos' => $tamano_options), JSON_UNESCAPED_UNICODE);
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'getTamanosPorMarca') {
    $marca_id = !empty($_POST['marca_id']) ? (int)$_POST['marca_id'] : 0;
    $tamano_options = '<option value="">Seleccionar Tamaño</option>';
    
    if ($marca_id > 0) {
        // Tamaños asociados a la marca
        $query_t = mysqli_query($conection, "SELECT t.idtamano, t.nombre FROM tamano t
                                             INNER JOIN marca_tamano mt ON t.idtamano = mt.tamano_id
                                             WHERE mt.marca_id = $marca_id AND t.status = 1 ORDER BY t.nombre ASC");
        while ($data_t = mysqli_fetch_assoc($query_t)) {
            $tamano_options .= '<option value="' . $data_t['idtamano'] . '">' . $data_t['nombre'] . '</option>';
        }
    }
    
    echo json_encode(array('tamanos' => $tamano_options), JSON_UNESCAPED_UNICODE);
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'crearTamanoRapido') {
    $nombre = mysqli_real_escape_string($conection, trim($_POST['nombre']));
    if (empty($nombre)) {
        echo json_encode(array('cod' => '01', 'msg' => 'El nombre del tamaño es obligatorio.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Verificar si ya existe
    $check = mysqli_query($conection, "SELECT idtamano, nombre FROM tamano WHERE LOWER(nombre) = LOWER('$nombre') AND status = 1");
    if (mysqli_num_rows($check) > 0) {
        $data = mysqli_fetch_assoc($check);
        echo json_encode(array('cod' => '00', 'idtamano' => $data['idtamano'], 'nombre' => $data['nombre'], 'msg' => 'Tamaño existente seleccionado.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $query = mysqli_query($conection, "INSERT INTO tamano (nombre) VALUES ('$nombre')");
    if ($query) {
        $id = mysqli_insert_id($conection);
        echo json_encode(array('cod' => '00', 'idtamano' => $id, 'nombre' => $nombre, 'msg' => 'Tamaño creado correctamente.'), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '02', 'msg' => 'Error al crear tamaño.'), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'delCategoria') {
    $id = (int)$_POST['id'];
    $query_delete = mysqli_query($conection, "UPDATE categoria SET status = 0 WHERE idcategoria = $id");
    if ($query_delete) {
        echo 'ok';
    } else {
        echo 'error';
    }
    mysqli_close($conection);
    exit;
}


// --- MARCAS ---
if ($_POST['action'] == 'listaMarcas') {
    $busqueda = isset($_POST['busqueda']) ? mysqli_real_escape_string($conection, $_POST['busqueda']) : '';
    $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
    $por_pagina = 10;
    
    $where = " WHERE status = 1 ";
    if ($busqueda != '') {
        $where .= " AND nombre LIKE '%$busqueda%' ";
    }
    
    $sql_registe = mysqli_query($conection, "SELECT COUNT(*) as total_registro FROM marca $where");
    $result_register = mysqli_fetch_assoc($sql_registe);
    $total_registro = $result_register['total_registro'];
    
    $total_paginas = ceil($total_registro / $por_pagina);
    $desde = ($pagina - 1) * $por_pagina;
    
    $query = mysqli_query($conection, "SELECT * FROM marca $where ORDER BY idmarca DESC LIMIT $desde, $por_pagina");
    
    $lista = '<table class="tbl_venta">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>';
    
    if (mysqli_num_rows($query) > 0) {
        while ($data = mysqli_fetch_assoc($query)) {
            $lista .= '<tr>
                        <td>'.$data['idmarca'].'</td>
                        <td>'.$data['nombre'].'</td>
                        <td>
                            <a class="link_edit" href="#" onclick="event.preventDefault(); editar_marca('.$data['idmarca'].');"><i class="fas fa-edit"></i></a>
                            |
                            <a class="link_delete" href="#" onclick="event.preventDefault(); del_marca('.$data['idmarca'].');"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>';
        }
    } else {
        $lista .= '<tr><td colspan="3" class="textcenter">No hay registros</td></tr>';
    }
    $lista .= '</tbody></table>';
    
    $paginador = '<ul>';
    if ($pagina != 1) {
        $paginador .= '<li><a href="#" onclick="listaMarcas(\''.$busqueda.'\', 1)"><i class="fas fa-step-backward"></i></a></li>';
    }
    for ($i = 1; $i <= $total_paginas; $i++) {
        if ($i == $pagina) {
            $paginador .= '<li class="pageSelected">'.$i.'</li>';
        } else {
            $paginador .= '<li><a href="#" onclick="listaMarcas(\''.$busqueda.'\', '.$i.')">'.$i.'</a></li>';
        }
    }
    if ($pagina != $total_paginas && $total_paginas > 0) {
        $paginador .= '<li><a href="#" onclick="listaMarcas(\''.$busqueda.'\', '.$total_paginas.')"><i class="fas fa-step-forward"></i></a></li>';
    }
    $paginador .= '</ul>';
    
    echo json_encode(array('tabla' => $lista, 'paginador' => $paginador), JSON_UNESCAPED_UNICODE);
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'crearMarca') {
    $nombre = mysqli_real_escape_string($conection, $_POST['nombre']);
    if (empty($nombre)) {
        echo json_encode(array('cod' => '01', 'msg' => 'El nombre es obligatorio.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    $query_insert = mysqli_query($conection, "INSERT INTO marca(nombre) VALUES('$nombre')");
    if ($query_insert) {
        $marca_id = mysqli_insert_id($conection);
        // Asociar categorias seleccionadas
        if (!empty($_POST['categorias']) && is_array($_POST['categorias'])) {
            foreach ($_POST['categorias'] as $cat_id) {
                $cat_id = (int)$cat_id;
                $check = mysqli_query($conection, "SELECT * FROM categoria_marca WHERE categoria_id=$cat_id AND marca_id=$marca_id");
                if (mysqli_num_rows($check) == 0) {
                    mysqli_query($conection, "INSERT INTO categoria_marca (categoria_id, marca_id) VALUES ($cat_id, $marca_id)");
                }
            }
        }
        // Asociar tamaños seleccionados
        if (!empty($_POST['tamanos']) && is_array($_POST['tamanos'])) {
            foreach ($_POST['tamanos'] as $tam_id) {
                $tam_id = (int)$tam_id;
                mysqli_query($conection, "INSERT INTO marca_tamano (marca_id, tamano_id) VALUES ($marca_id, $tam_id)");
            }
        }
        echo json_encode(array('cod' => '00', 'msg' => 'Marca creada correctamente.'), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '02', 'msg' => 'Error al crear la marca.'), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'editarMarca') {
    $id = (int)$_POST['id'];
    // Datos de la marca (null si id=0, para soporte de nueva marca)
    $data = null;
    if ($id > 0) {
        $query = mysqli_query($conection, "SELECT * FROM marca WHERE idmarca = $id");
        $data = mysqli_fetch_assoc($query);
    }
    // Categorias ya asociadas
    $cats = [];
    if ($id > 0) {
        $query_cats = mysqli_query($conection, "SELECT categoria_id FROM categoria_marca WHERE marca_id = $id");
        while ($row = mysqli_fetch_assoc($query_cats)) { $cats[] = (int)$row['categoria_id']; }
    }
    // Todas las categorias disponibles
    $query_all = mysqli_query($conection, "SELECT idcategoria, nombre FROM categoria WHERE status=1 ORDER BY nombre ASC");
    $all_cats = [];
    while ($row = mysqli_fetch_assoc($query_all)) { $all_cats[] = $row; }
    // Tamaños ya asociados
    $tams = [];
    if ($id > 0) {
        $query_tams = mysqli_query($conection, "SELECT tamano_id FROM marca_tamano WHERE marca_id = $id");
        while ($row = mysqli_fetch_assoc($query_tams)) { $tams[] = (int)$row['tamano_id']; }
    }
    // Todos los tamaños disponibles
    $query_all_t = mysqli_query($conection, "SELECT idtamano, nombre FROM tamano WHERE status=1 ORDER BY nombre ASC");
    $all_tams = [];
    while ($row = mysqli_fetch_assoc($query_all_t)) { $all_tams[] = $row; }
    echo json_encode(array('marca' => $data, 'categorias_asociadas' => $cats, 'todas_categorias' => $all_cats, 'tamanos_asociados' => $tams, 'todos_tamanos' => $all_tams), JSON_UNESCAPED_UNICODE);
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'actualizarMarca') {
    $id = (int)$_POST['id'];
    $nombre = mysqli_real_escape_string($conection, $_POST['nombre']);
    if (empty($nombre)) {
        echo json_encode(array('cod' => '01', 'msg' => 'El nombre es obligatorio.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    $query_update = mysqli_query($conection, "UPDATE marca SET nombre = '$nombre' WHERE idmarca = $id");
    if ($query_update) {
        // Re-asociar categorias: borrar las actuales y reinsertar las seleccionadas
        mysqli_query($conection, "DELETE FROM categoria_marca WHERE marca_id = $id");
        if (!empty($_POST['categorias']) && is_array($_POST['categorias'])) {
            foreach ($_POST['categorias'] as $cat_id) {
                $cat_id = (int)$cat_id;
                mysqli_query($conection, "INSERT INTO categoria_marca (categoria_id, marca_id) VALUES ($cat_id, $id)");
            }
        }
        // Re-asociar tamaños
        mysqli_query($conection, "DELETE FROM marca_tamano WHERE marca_id = $id");
        if (!empty($_POST['tamanos']) && is_array($_POST['tamanos'])) {
            foreach ($_POST['tamanos'] as $tam_id) {
                $tam_id = (int)$tam_id;
                mysqli_query($conection, "INSERT INTO marca_tamano (marca_id, tamano_id) VALUES ($id, $tam_id)");
            }
        }
        echo json_encode(array('cod' => '00', 'msg' => 'Marca actualizada correctamente.'), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '02', 'msg' => 'Error al actualizar la marca.'), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'delMarca') {
    $id = (int)$_POST['id'];
    $query_delete = mysqli_query($conection, "UPDATE marca SET status = 0 WHERE idmarca = $id");
    if ($query_delete) {
        echo 'ok';
    } else {
        echo 'error';
    }
    mysqli_close($conection);
    exit;
}


// --- TAMAÑOS ---
if ($_POST['action'] == 'listaTamanos') {
    $busqueda = isset($_POST['busqueda']) ? mysqli_real_escape_string($conection, $_POST['busqueda']) : '';
    $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
    $por_pagina = 10;
    
    $where = " WHERE status = 1 ";
    if ($busqueda != '') {
        $where .= " AND nombre LIKE '%$busqueda%' ";
    }
    
    $sql_registe = mysqli_query($conection, "SELECT COUNT(*) as total_registro FROM tamano $where");
    $result_register = mysqli_fetch_assoc($sql_registe);
    $total_registro = $result_register['total_registro'];
    
    $total_paginas = ceil($total_registro / $por_pagina);
    $desde = ($pagina - 1) * $por_pagina;
    
    $query = mysqli_query($conection, "SELECT * FROM tamano $where ORDER BY idtamano DESC LIMIT $desde, $por_pagina");
    
    $lista = '<table class="tbl_venta">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>';
    
    if (mysqli_num_rows($query) > 0) {
        while ($data = mysqli_fetch_assoc($query)) {
            $lista .= '<tr>
                        <td>'.$data['idtamano'].'</td>
                        <td>'.$data['nombre'].'</td>
                        <td>
                            <a class="link_edit" href="#" onclick="event.preventDefault(); editar_tamano('.$data['idtamano'].');"><i class="fas fa-edit"></i></a>
                            |
                            <a class="link_delete" href="#" onclick="event.preventDefault(); del_tamano('.$data['idtamano'].');"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>';
        }
    } else {
        $lista .= '<tr><td colspan="3" class="textcenter">No hay registros</td></tr>';
    }
    $lista .= '</tbody></table>';
    
    $paginador = '<ul>';
    if ($pagina != 1) {
        $paginador .= '<li><a href="#" onclick="listaTamanos(\''.$busqueda.'\', 1)"><i class="fas fa-step-backward"></i></a></li>';
    }
    for ($i = 1; $i <= $total_paginas; $i++) {
        if ($i == $pagina) {
            $paginador .= '<li class="pageSelected">'.$i.'</li>';
        } else {
            $paginador .= '<li><a href="#" onclick="listaTamanos(\''.$busqueda.'\', '.$i.')">'.$i.'</a></li>';
        }
    }
    if ($pagina != $total_paginas && $total_paginas > 0) {
        $paginador .= '<li><a href="#" onclick="listaTamanos(\''.$busqueda.'\', '.$total_paginas.')"><i class="fas fa-step-forward"></i></a></li>';
    }
    $paginador .= '</ul>';
    
    echo json_encode(array('tabla' => $lista, 'paginador' => $paginador), JSON_UNESCAPED_UNICODE);
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'crearTamano') {
    $nombre = mysqli_real_escape_string($conection, $_POST['nombre']);
    if (empty($nombre)) {
        echo json_encode(array('cod' => '01', 'msg' => 'El nombre es obligatorio.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    $query_insert = mysqli_query($conection, "INSERT INTO tamano(nombre) VALUES('$nombre')");
    if ($query_insert) {
        $tamano_id = mysqli_insert_id($conection);
        // Asociar marcas seleccionadas
        if (!empty($_POST['marcas']) && is_array($_POST['marcas'])) {
            foreach ($_POST['marcas'] as $marca_id) {
                $marca_id = (int)$marca_id;
                $check = mysqli_query($conection, "SELECT * FROM marca_tamano WHERE marca_id=$marca_id AND tamano_id=$tamano_id");
                if (mysqli_num_rows($check) == 0) {
                    mysqli_query($conection, "INSERT INTO marca_tamano (marca_id, tamano_id) VALUES ($marca_id, $tamano_id)");
                }
            }
        }
        echo json_encode(array('cod' => '00', 'msg' => 'Tamaño creado correctamente.'), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '02', 'msg' => 'Error al crear el tamaño.'), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'editarTamano') {
    $id = (int)$_POST['id'];
    $query = mysqli_query($conection, "SELECT * FROM tamano WHERE idtamano = $id");
    $data = mysqli_fetch_assoc($query);
    // Marcas ya asociadas
    $query_marcas = mysqli_query($conection, "SELECT marca_id FROM marca_tamano WHERE tamano_id = $id");
    $marcas = [];
    while ($row = mysqli_fetch_assoc($query_marcas)) { $marcas[] = (int)$row['marca_id']; }
    // Todas las marcas disponibles
    $query_all = mysqli_query($conection, "SELECT idmarca, nombre FROM marca WHERE status=1 ORDER BY nombre ASC");
    $all_marcas = [];
    while ($row = mysqli_fetch_assoc($query_all)) { $all_marcas[] = $row; }
    echo json_encode(array('tamano' => $data, 'marcas_asociadas' => $marcas, 'todas_marcas' => $all_marcas), JSON_UNESCAPED_UNICODE);
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'actualizarTamano') {
    $id = (int)$_POST['id'];
    $nombre = mysqli_real_escape_string($conection, $_POST['nombre']);
    if (empty($nombre)) {
        echo json_encode(array('cod' => '01', 'msg' => 'El nombre es obligatorio.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    $query_update = mysqli_query($conection, "UPDATE tamano SET nombre = '$nombre' WHERE idtamano = $id");
    if ($query_update) {
        // Re-asociar marcas: borrar las actuales y reinsertar las seleccionadas
        mysqli_query($conection, "DELETE FROM marca_tamano WHERE tamano_id = $id");
        if (!empty($_POST['marcas']) && is_array($_POST['marcas'])) {
            foreach ($_POST['marcas'] as $marca_id) {
                $marca_id = (int)$marca_id;
                mysqli_query($conection, "INSERT INTO marca_tamano (marca_id, tamano_id) VALUES ($marca_id, $id)");
            }
        }
        echo json_encode(array('cod' => '00', 'msg' => 'Tamaño actualizado correctamente.'), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '02', 'msg' => 'Error al actualizar el tamaño.'), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'delTamano') {
    $id = (int)$_POST['id'];
    $query_delete = mysqli_query($conection, "UPDATE tamano SET status = 0 WHERE idtamano = $id");
    if ($query_delete) {
        echo 'ok';
    } else {
        echo 'error';
    }
    mysqli_close($conection);
    exit;
}
if ($_POST['action'] == 'crearCategoriaRapido') {
    $nombre = mysqli_real_escape_string($conection, trim($_POST['nombre']));
    if (empty($nombre)) {
        echo json_encode(array('cod' => '01', 'msg' => 'El nombre de la categoría es obligatorio.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Verificar si ya existe
    $check = mysqli_query($conection, "SELECT idcategoria, nombre FROM categoria WHERE LOWER(nombre) = LOWER('$nombre') AND status = 1");
    if (mysqli_num_rows($check) > 0) {
        $data = mysqli_fetch_assoc($check);
        echo json_encode(array('cod' => '00', 'idcategoria' => $data['idcategoria'], 'nombre' => $data['nombre'], 'msg' => 'Categoría existente seleccionada.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $query = mysqli_query($conection, "INSERT INTO categoria (nombre) VALUES ('$nombre')");
    if ($query) {
        $id = mysqli_insert_id($conection);
        echo json_encode(array('cod' => '00', 'idcategoria' => $id, 'nombre' => $nombre, 'msg' => 'Categoría creada correctamente.'), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '02', 'msg' => 'Error al crear categoría.'), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'crearMarcaRapido') {
    $nombre = mysqli_real_escape_string($conection, trim($_POST['nombre']));
    if (empty($nombre)) {
        echo json_encode(array('cod' => '01', 'msg' => 'El nombre de la marca es obligatorio.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Verificar si ya existe
    $check = mysqli_query($conection, "SELECT idmarca, nombre FROM marca WHERE LOWER(nombre) = LOWER('$nombre') AND status = 1");
    if (mysqli_num_rows($check) > 0) {
        $data = mysqli_fetch_assoc($check);
        echo json_encode(array('cod' => '00', 'idmarca' => $data['idmarca'], 'nombre' => $data['nombre'], 'msg' => 'Marca existente seleccionada.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $query = mysqli_query($conection, "INSERT INTO marca (nombre) VALUES ('$nombre')");
    if ($query) {
        $id = mysqli_insert_id($conection);
        echo json_encode(array('cod' => '00', 'idmarca' => $id, 'nombre' => $nombre, 'msg' => 'Marca creada correctamente.'), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '02', 'msg' => 'Error al crear marca.'), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'crearProveedorRapido') {
    $nombre = mysqli_real_escape_string($conection, trim($_POST['nombre']));
    if (empty($nombre)) {
        echo json_encode(array('cod' => '01', 'msg' => 'El nombre del proveedor es obligatorio.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Verificar si ya existe
    $check = mysqli_query($conection, "SELECT codproveedor, proveedor FROM proveedor WHERE LOWER(proveedor) = LOWER('$nombre') AND status = 1");
    if (mysqli_num_rows($check) > 0) {
        $data = mysqli_fetch_assoc($check);
        echo json_encode(array('cod' => '00', 'codproveedor' => $data['codproveedor'], 'nombre' => $data['proveedor'], 'msg' => 'Proveedor existente seleccionado.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Obtener usuario actual si existe sesión
    $usuario_id = isset($_SESSION['idusuario']) ? (int)$_SESSION['idusuario'] : 0;
    
    $query = mysqli_query($conection, "INSERT INTO proveedor (proveedor, usuario_id) VALUES ('$nombre', $usuario_id)");
    if ($query) {
        $id = mysqli_insert_id($conection);
        echo json_encode(array('cod' => '00', 'codproveedor' => $id, 'nombre' => $nombre, 'msg' => 'Proveedor creado correctamente.'), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '02', 'msg' => 'Error al crear proveedor.'), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'fusionarDuplicados') {
    $original_id = isset($_POST['original_id']) ? (int)$_POST['original_id'] : 0;
    $duplicado_ids = isset($_POST['duplicado_ids']) ? json_decode($_POST['duplicado_ids'], true) : [];
    $sumar_stock = isset($_POST['sumar_stock']) ? (int)$_POST['sumar_stock'] : 0;
    
    if (empty($duplicado_ids)) {
        echo json_encode(array('cod' => '01', 'msg' => 'No se recibieron IDs de duplicados.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $eliminados = 0;
    $errores = 0;
    
    // Si hay que sumar stock al original
    if ($sumar_stock && $original_id > 0) {
        // Obtener stock total de los duplicados
        $ids_str = implode(',', array_map('intval', $duplicado_ids));
        $q_stock = mysqli_query($conection, "SELECT SUM(existencia) as stock_total FROM producto WHERE codproducto IN ($ids_str)");
        if ($q_stock) {
            $stock_data = mysqli_fetch_assoc($q_stock);
            $stock_extra = (int)$stock_data['stock_total'];
            
            if ($stock_extra > 0) {
                mysqli_query($conection, "UPDATE producto SET existencia = existencia + $stock_extra WHERE codproducto = $original_id");
            }
        }
    }
    
    // Eliminar los duplicados (DELETE real)
    foreach ($duplicado_ids as $dup_id) {
        $dup_id = (int)$dup_id;
        $q_del = mysqli_query($conection, "DELETE FROM producto WHERE codproducto = $dup_id");
        if ($q_del) {
            $eliminados++;
        } else {
            $errores++;
        }
    }
    
    if ($eliminados > 0) {
        $msg = "Se eliminaron $eliminados duplicado(s)";
        if ($sumar_stock && $original_id > 0) {
            $msg .= " y se sumó el stock al producto original (ID $original_id)";
        }
        $msg .= ".";
        echo json_encode(array('cod' => '00', 'msg' => $msg, 'eliminados' => $eliminados, 'errores' => $errores), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '02', 'msg' => 'Error al eliminar duplicados. Errores: ' . $errores), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'editarProductoMasivo') {
    $codproducto = isset($_POST['codproducto']) ? (int)$_POST['codproducto'] : 0;
    if ($codproducto <= 0) {
        echo json_encode(array('cod' => '01', 'msg' => 'ID de producto inválido.'), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $updates = [];
    $allowed_fields = ['descripcion', 'codigo', 'codigo_interno', 'existencia', 'costo', 'precio', 'categoria_id', 'marca_id', 'tamano_id'];
    
    foreach ($allowed_fields as $field) {
        if (isset($_POST[$field])) {
            $val = mysqli_real_escape_string($conection, $_POST[$field]);
            // Convert empty string to NULL or 0 for IDs
            if (in_array($field, ['categoria_id', 'marca_id', 'tamano_id']) && $val === '') {
                $val = 0;
            }
            $updates[] = "$field = '$val'";
        }
    }

    if (count($updates) > 0) {
        $query_update = mysqli_query($conection, "UPDATE producto SET " . implode(', ', $updates) . " WHERE codproducto = $codproducto");
        if ($query_update) {
            echo json_encode(array('cod' => '00', 'msg' => 'Producto actualizado.'), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array('cod' => '02', 'msg' => 'Error al actualizar el producto.'), JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(array('cod' => '03', 'msg' => 'No hay datos para actualizar.'), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}

if ($_POST['action'] == 'eliminarProductoMasivo') {
    $codproducto = isset($_POST['codproducto']) ? (int)$_POST['codproducto'] : 0;
    if ($codproducto <= 0) {
        echo json_encode(array('cod' => '01', 'msg' => 'ID de producto inválido.'), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $query_delete = mysqli_query($conection, "UPDATE producto SET status = 0 WHERE codproducto = $codproducto");
    if ($query_delete) {
        echo json_encode(array('cod' => '00', 'msg' => 'Producto eliminado.'), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '02', 'msg' => 'Error al eliminar el producto.'), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}
?>
