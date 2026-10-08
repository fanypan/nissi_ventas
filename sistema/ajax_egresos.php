<?php 
/**
 * Acciones AJAX para el módulo mejorado de egresos
 * Maneja Gastos Generales, Compras Múltiples de Insumos y Gestión de Insumos
 */ 

// ============================================
// REGISTRAR EGRESO MEJORADO (Múltiples Ítems)
// ============================================
if ($_POST['action'] == 'nuevoEgresoMejorado') {
    $tipo_egreso = isset($_POST['tipoEgreso']) ? (int)$_POST['tipoEgreso'] : 1;
    $establecimiento = isset($_POST['establecimiento']) ? mysqli_real_escape_string($conection, $_POST['establecimiento']) : '';
    $user = $_SESSION['idUser'];
    $code = '00';
    $msg = "Operación exitosa";

    if ($tipo_egreso == 1) {
        // Gasto General (Un solo ítem como antes)
        if (empty($_POST['descEgreso']) || empty($_POST['cantEgreso'])) {
            $code = '1';
            $msg = "Todos los campos son obligatorios.";
        } else {
            $descripcion = mysqli_real_escape_string($conection, $_POST['descEgreso']);
            $cantidad = (float)$_POST['cantEgreso'];
            
            $query_insert = mysqli_query($conection, 
                "INSERT INTO egresos(descripcion, establecimiento, cantidad, tipo_egreso, usuario, caja)
                 VALUES('$descripcion', '$establecimiento', $cantidad, 1, $user, $id_caja)");
            
            if (!$query_insert) {
                $code = '2';
                $msg = "Error al registrar el gasto: " . mysqli_error($conection);
            } else {
                $msg = "Gasto general registrado correctamente";
            }
        }
    } else if ($tipo_egreso == 2) {
        // Compra de Insumos (Múltiples ítems)
        $items = isset($_POST['items']) ? $_POST['items'] : [];
        
        if (empty($items)) {
            $code = '1';
            $msg = "Debe agregar al menos un insumo a la lista.";
        } else {
            // Generar un ID de compra único basado en microtime para agrupar los registros
            $id_compra = (int)(microtime(true) * 100);
            $errores = 0;

            foreach ($items as $item) {
                $id_insumo = (int)$item['id_insumo'];
                $precio_unitario = (float)$item['precio_unitario'];
                $cantidad_unidades = (float)$item['cantidad_unidades'];
                $total_fila = $precio_unitario * $cantidad_unidades;
                
                // Obtener nombre del insumo para la descripción
                $query_insumo = mysqli_query($conection, "SELECT nombre FROM insumos WHERE id = $id_insumo");
                $data_insumo = mysqli_fetch_assoc($query_insumo);
                $nombre_insumo = $data_insumo['nombre'];
                
                $descripcion = "Compra Insumo: " . $nombre_insumo;
                if (!empty($establecimiento)) $descripcion .= " (En: $establecimiento)";

                $query_insert = mysqli_query($conection, 
                    "INSERT INTO egresos(descripcion, establecimiento, cantidad, tipo_egreso, id_compra_insumo, id_insumo, precio_unitario, cantidad_unidades, usuario, caja)
                     VALUES('$descripcion', '$establecimiento', $total_fila, 2, $id_compra, $id_insumo, $precio_unitario, $cantidad_unidades, $user, $id_caja)");
                
                if ($query_insert) {
                    // Actualizar stock del insumo
                    $cantidad_f = (float)$cantidad_unidades;
                    $id_ins_f = (int)$id_insumo;
                    $query_upd = mysqli_query($conection, "UPDATE insumos SET stock = stock + $cantidad_f WHERE id = $id_ins_f");
                    
                    if (!$query_upd) {
                        file_put_contents('debug_insumos.log', date('Y-m-d H:i:s') . " - Error Update Stock (ID $id_ins_f): " . mysqli_error($conection) . "\n", FILE_APPEND);
                    }
                } else {
                    $errores++;
                    file_put_contents('debug_insumos.log', date('Y-m-d H:i:s') . " - Error Insert Egreso: " . mysqli_error($conection) . "\n", FILE_APPEND);
                }
            }

            if ($errores > 0) {
                $code = '2';
                $msg = "Se registraron algunos errores durante la carga ($errores).";
            } else {
                $msg = "Compra de insumos registrada correctamente (" . count($items) . " ítems).";
            }
        }
    } else {
        $code = '2';
        $msg = "Tipo de egreso no válido";
    }
    
    echo json_encode(array('cod' => $code, 'msg' => $msg), JSON_UNESCAPED_UNICODE);
    mysqli_close($conection);
    exit;
}

// ============================================
// OBTENER INFO PARA ANULAR EGRESO
// ============================================
if ($_POST['action'] == 'infoEgreso') {
    if (!empty($_POST['nofactura'])) {
        $id = (int)$_POST['nofactura'];
        $query = mysqli_query($conection, "SELECT * FROM egresos WHERE id = $id");
        
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
// ANULAR / ELIMINAR EGRESO (Con reversión de stock)
// ============================================
if ($_POST['action'] == 'anularEgreso') {
    if (!empty($_POST['noFactura'])) {
        $id = (int)$_POST['noFactura'];
        
        // Consultar si es una compra de insumos para revertir stock
        $query_info = mysqli_query($conection, "SELECT tipo_egreso, id_insumo, cantidad_unidades FROM egresos WHERE id = $id");
        $data_egreso = mysqli_fetch_assoc($query_info);
        
        if ($data_egreso['tipo_egreso'] == 2 && !empty($data_egreso['id_insumo'])) {
            $id_ins = (int)$data_egreso['id_insumo'];
            $cant_u = (float)$data_egreso['cantidad_unidades'];
            mysqli_query($conection, "UPDATE insumos SET stock = stock - $cant_u WHERE id = $id_ins");
        }

        // Ejecutar eliminación
        $query_delete = mysqli_query($conection, "DELETE FROM egresos WHERE id = $id");
        
        if ($query_delete) {
            echo json_encode(array('res' => 'ok', 'msg' => 'Egreso eliminado y stock revertido si aplicaba'), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array('res' => 'error', 'msg' => 'Error al eliminar el registro'), JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo "error";
    }
    mysqli_close($conection);
    exit;
}

// ============================================
// ACTUALIZAR EGRESO
// ============================================
if ($_POST['action'] == 'actualizarEgreso') {
    if (empty($_POST['idEgreso']) || empty($_POST['descEgreso']) || empty($_POST['cantEgreso'])) {
        echo json_encode(array('cod' => '1', 'msg' => 'Todos los campos son obligatorios'), JSON_UNESCAPED_UNICODE);
    } else {
        $id = (int)$_POST['idEgreso'];
        $desc = mysqli_real_escape_string($conection, $_POST['descEgreso']);
        $estab = mysqli_real_escape_string($conection, $_POST['establecimiento']);
        $cant = (float)$_POST['cantEgreso'];
        
        $query_upd = mysqli_query($conection, "UPDATE egresos SET descripcion = '$desc', establecimiento = '$estab', cantidad = $cant WHERE id = $id");
        
        if ($query_upd) {
            echo json_encode(array('cod' => '00', 'msg' => 'Egreso actualizado correctamente'), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array('cod' => '2', 'msg' => 'Error al actualizar: ' . mysqli_error($conection)), JSON_UNESCAPED_UNICODE);
        }
    }
    mysqli_close($conection);
    exit;
}

// ============================================
// CREAR NUEVO INSUMO
// ============================================
if ($_POST['action'] == 'crearInsumo') {
    if (empty($_POST['nombreInsumo'])) {
        echo json_encode(array('cod' => '1', 'msg' => 'El nombre es obligatorio'), JSON_UNESCAPED_UNICODE);
        mysqli_close($conection);
        exit;
    }
    
    $nombre = mysqli_real_escape_string($conection, $_POST['nombreInsumo']);
    $descripcion = isset($_POST['descripcionInsumo']) ? mysqli_real_escape_string($conection, $_POST['descripcionInsumo']) : '';
    $unidad_medida = isset($_POST['unidadMedida']) ? mysqli_real_escape_string($conection, $_POST['unidadMedida']) : '';
    $precio_ref = isset($_POST['precioReferencia']) ? (float)$_POST['precioReferencia'] : 0.00;
    
    $query = mysqli_query($conection, 
        "INSERT INTO insumos(nombre, descripcion, unidad_medida, precio_referencia)
         VALUES('$nombre', '$descripcion', '$unidad_medida', $precio_ref)");
    
    if ($query) {
        $id = mysqli_insert_id($conection);
        echo json_encode(array('cod' => '00', 'msg' => 'Insumo creado correctamente', 'id' => $id, 'nombre' => $nombre), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '2', 'msg' => 'Error al crear insumo: ' . mysqli_error($conection)), JSON_UNESCAPED_UNICODE);
    }
    
    mysqli_close($conection);
    exit;
}

// ============================================
// LISTAR INSUMOS
// ============================================
if ($_POST['action'] == 'listarInsumos') {
    $query = mysqli_query($conection, "SELECT * FROM insumos WHERE status = 1 ORDER BY nombre ASC");
    $insumos = array();
    
    if ($query) {
        while ($data = mysqli_fetch_assoc($query)) {
            $insumos[] = $data;
        }
    }
    
    echo json_encode($insumos, JSON_UNESCAPED_UNICODE);
    mysqli_close($conection);
    exit;
}

// ============================================
// OBTENER INFO DE UN INSUMO
// ============================================
if ($_POST['action'] == 'infoInsumo') {
    if (empty($_POST['id_insumo'])) {
        echo json_encode(array('cod' => '1', 'msg' => 'ID de insumo no especificado'), JSON_UNESCAPED_UNICODE);
        mysqli_close($conection);
        exit;
    }
    
    $id_insumo = (int)$_POST['id_insumo'];
    $query = mysqli_query($conection, "SELECT * FROM insumos WHERE id = $id_insumo");
    
    if ($query && mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        echo json_encode(array('cod' => '00', 'data' => $data), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '2', 'msg' => 'Insumo no encontrado'), JSON_UNESCAPED_UNICODE);
    }
    
    mysqli_close($conection);
    exit;
}

// ============================================
// EDITAR INSUMO
// ============================================
if ($_POST['action'] == 'editarInsumo') {
    if (empty($_POST['idInsumo']) || empty($_POST['nombreInsumo'])) {
        echo json_encode(array('cod' => '1', 'msg' => 'Faltan datos obligatorios'), JSON_UNESCAPED_UNICODE);
        mysqli_close($conection);
        exit;
    }
    $id = (int)$_POST['idInsumo'];
    $nombre = mysqli_real_escape_string($conection, $_POST['nombreInsumo']);
    $unidad = mysqli_real_escape_string($conection, $_POST['unidadMedida']);
    $precio = (float)($_POST['precioReferencia'] ?? 0);

    $query = mysqli_query($conection, "UPDATE insumos SET nombre='$nombre', unidad_medida='$unidad', precio_referencia=$precio WHERE id=$id");
    if ($query) {
        echo json_encode(array('cod' => '00', 'msg' => 'Insumo actualizado correctamente'), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '2', 'msg' => 'Error al actualizar: ' . mysqli_error($conection)), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}

// ============================================
// ELIMINAR INSUMO (desactivación lógica)
// ============================================
if ($_POST['action'] == 'eliminarInsumo') {
    if (empty($_POST['idInsumo'])) {
        echo json_encode(array('cod' => '1', 'msg' => 'ID no especificado'), JSON_UNESCAPED_UNICODE);
        mysqli_close($conection);
        exit;
    }
    $id = (int)$_POST['idInsumo'];
    $query = mysqli_query($conection, "UPDATE insumos SET status=0 WHERE id=$id");
    if ($query) {
        echo json_encode(array('cod' => '00', 'msg' => 'Insumo eliminado correctamente'), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array('cod' => '2', 'msg' => 'Error al eliminar: ' . mysqli_error($conection)), JSON_UNESCAPED_UNICODE);
    }
    mysqli_close($conection);
    exit;
}
