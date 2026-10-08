<?php
include "../conexion.php";
session_start();

if (empty($_SESSION['active'])) {
    header('location: ../');
    exit;
}

if (!empty($_POST)) {
    $user = $_SESSION['idUser'];

    // Registrar Consumo de Insumo
    if ($_POST['action'] == 'registrarConsumo') {
        if (empty($_POST['id_insumo']) || empty($_POST['cantidad'])) {
            echo json_encode(['cod' => '1', 'msg' => 'Faltan datos obligatorios']);
            exit;
        }

        $id_insumo = (int)$_POST['id_insumo'];
        $cantidad = (float)$_POST['cantidad'];
        $obs = mysqli_real_escape_string($conection, $_POST['observaciones']);

        // 1. Verificar stock actual
        $query_insumo = mysqli_query($conection, "SELECT stock, nombre FROM insumos WHERE id = $id_insumo");
        $data_insumo = mysqli_fetch_assoc($query_insumo);
        $stock_actual = $data_insumo['stock'];

        if ($cantidad > $stock_actual) {
            echo json_encode(['cod' => '2', 'msg' => 'Stock insuficiente. Disponible: ' . $stock_actual]);
            exit;
        }

        // 2. Registrar consumo
        $query_insert = mysqli_query($conection, "INSERT INTO consumo_insumos (id_insumo, cantidad, usuario_id, observaciones) 
                                                  VALUES ($id_insumo, $cantidad, $user, '$obs')");

        if ($query_insert) {
            // 3. Descontar stock
            mysqli_query($conection, "UPDATE insumos SET stock = stock - $cantidad WHERE id = $id_insumo");
            echo json_encode(['cod' => '00', 'msg' => 'Consumo registrado correctamente']);
        } else {
            echo json_encode(['cod' => '3', 'msg' => 'Error al registrar el consumo: ' . mysqli_error($conection)]);
        }
        exit;
    }

    // Listar Historial de Consumo
    if ($_POST['action'] == 'listarConsumos') {
        $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
        $por_pagina = 10;
        
        $f_de = !empty($_POST['f_de']) ? mysqli_real_escape_string($conection, $_POST['f_de']) : date('Y-m-d', strtotime('-30 days'));
        $f_a = !empty($_POST['f_a']) ? mysqli_real_escape_string($conection, $_POST['f_a']) : date('Y-m-d');

        $where = " DATE(c.fecha) BETWEEN '$f_de' AND '$f_a' ";

        $sql_registe = mysqli_query($conection, "SELECT COUNT(*) as total_registro FROM consumo_insumos c WHERE $where");
        $result_register = mysqli_fetch_assoc($sql_registe);
        $total_registro = $result_register['total_registro'];
        
        $total_paginas = ceil($total_registro / $por_pagina);
        $desde = ($pagina - 1) * $por_pagina;

        $query = mysqli_query($conection, "SELECT c.*, i.nombre as insumo, i.unidad_medida, u.nombre as usuario 
                                           FROM consumo_insumos c
                                           INNER JOIN insumos i ON c.id_insumo = i.id
                                           INNER JOIN usuario u ON c.usuario_id = u.idusuario
                                           WHERE $where
                                           ORDER BY c.fecha DESC LIMIT $desde, $por_pagina");
        
        $lista = '';
        if (mysqli_num_rows($query) > 0) {
            while ($data = mysqli_fetch_assoc($query)) {
                $acciones = '';
                if ($_SESSION['rol'] == 1) {
                    $acciones = '<a class="link_edit" style="color:#3498db;" href="javascript:infoEditarConsumo('.$data['id'].');"><i class="fas fa-edit"></i></a> | 
                                 <a class="link_delete" style="color:#e74c3c;" href="javascript:infoAnularConsumo('.$data['id'].');"><i class="fas fa-trash-alt"></i></a>';
                } else {
                    $acciones = '<span style="color:#ccc;">-</span>';
                }

                $lista .= '<tr>
                            <td>'.date('d/m/Y H:i', strtotime($data['fecha'])).'</td>
                            <td>'.$data['insumo'].' ('.$data['unidad_medida'].')</td>
                            <td>'.floatval($data['cantidad']).'</td>
                            <td>'.$data['usuario'].'</td>
                            <td>'.$data['observaciones'].'</td>
                            <td>'.$acciones.'</td>
                           </tr>';
            }
        } else {
            $lista = '<tr><td colspan="6" class="textcenter">No hay registros en este periodo</td></tr>';
        }

        echo json_encode(['tabla' => $lista, 'paginas' => $total_paginas], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Info de un consumo
    if ($_POST['action'] == 'infoConsumo') {
        $id = (int)$_POST['id'];
        $query = mysqli_query($conection, "SELECT * FROM consumo_insumos WHERE id = $id");
        $data = mysqli_fetch_assoc($query);
        echo json_encode(['cod' => '00', 'data' => $data]);
        exit;
    }

    // Actualizar Consumo (Solo observaciones)
    if ($_POST['action'] == 'actualizarConsumo') {
        $id = (int)$_POST['idConsumo'];
        $obs = mysqli_real_escape_string($conection, $_POST['observaciones']);
        $query = mysqli_query($conection, "UPDATE consumo_insumos SET observaciones = '$obs' WHERE id = $id");
        if ($query) {
            echo json_encode(['cod' => '00', 'msg' => 'Actualizado correctamente']);
        } else {
            echo json_encode(['cod' => '1', 'msg' => 'Error al actualizar']);
        }
        exit;
    }

    // Anular Consumo (Revertir stock)
    if ($_POST['action'] == 'anularConsumo') {
        $id = (int)$_POST['id'];
        
        // Info para revertir
        $query_info = mysqli_query($conection, "SELECT id_insumo, cantidad FROM consumo_insumos WHERE id = $id");
        if (mysqli_num_rows($query_info) > 0) {
            $data = mysqli_fetch_assoc($query_info);
            $id_insumo = $data['id_insumo'];
            $cantidad = $data['cantidad'];

            // 1. Devolver stock
            mysqli_query($conection, "UPDATE insumos SET stock = stock + $cantidad WHERE id = $id_insumo");
            
            // 2. Eliminar registro
            mysqli_query($conection, "DELETE FROM consumo_insumos WHERE id = $id");
            
            echo json_encode(['cod' => '00', 'msg' => 'Consumo anulado y stock devuelto']);
        } else {
            echo json_encode(['cod' => '1', 'msg' => 'Registro no encontrado']);
        }
        exit;
    }
}
?>
