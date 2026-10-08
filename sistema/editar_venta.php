<?php
session_start();
if ($_SESSION['rol'] != 1) {
    header("location: ./");
}
include "../conexion.php";

if (!empty($_POST)) {
    $alert = '';
    if (empty($_POST['fecha']) || empty($_POST['idcliente']) || empty($_POST['tipo_pago'])) {
        $alert = '<p class="msg_error">Todos los campos son obligatorios.</p>';
    } else {
        $noventa = $_POST['id'];
        $fecha = $_POST['fecha'];
        $idcliente = $_POST['idcliente'];
        $tipo_pago = $_POST['tipo_pago'];
        $status = $_POST['status'];

        $query_update = mysqli_query($conection, "UPDATE venta 
                                                SET fecha = '$fecha', codcliente = $idcliente, tipo_pago_detalle = $tipo_pago, status = $status 
                                                WHERE noventa = $noventa");

        if ($query_update) {
            $alert = '<p class="msg_save">Venta actualizada correctamente.</p>';
        } else {
            $alert = '<p class="msg_error">Error al actualizar la venta.</p>';
        }
    }
}

// Validar ID
if (empty($_REQUEST['id'])) {
    header("location: ventas.php");
}
$noventa = $_REQUEST['id'];

$sql = mysqli_query($conection, "SELECT v.noventa, v.fecha, v.codcliente, v.tipo_pago_detalle, v.status, c.nombre as cliente 
                                  FROM venta v 
                                  INNER JOIN cliente c ON v.codcliente = c.idcliente 
                                  WHERE v.noventa = $noventa");
$result_sql = mysqli_num_rows($sql);

if ($result_sql == 0) {
    header("location: ventas.php");
} else {
    $data = mysqli_fetch_array($sql);
    $fecha = $data['fecha'];
    $idcliente = $data['codcliente'];
    $nombre_cliente = $data['cliente'];
    $tipo_pago = $data['tipo_pago_detalle'];
    $status_venta = $data['status'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php include "includes/scripts.php"; ?>
    <title>Editar Venta</title>
</head>
<body>
    <?php include "includes/header.php"; ?>
    <section id="container">
        <div class="form_register">
            <h1><i class="fas fa-edit"></i> Editar Venta No. <?php echo $noventa; ?></h1>
            <hr>
            <div class="alert"><?php echo isset($alert) ? $alert : ''; ?></div>

            <form action="" method="post">
                <input type="hidden" name="id" value="<?php echo $noventa; ?>">
                
                <label for="fecha">Fecha y Hora</label>
                <input type="text" name="fecha" id="fecha" value="<?php echo $fecha; ?>">

                <label for="cliente">Cliente</label>
                <input type="text" name="dummy_cliente" id="dummy_cliente" value="<?php echo $nombre_cliente; ?>" readonly>
                <input type="hidden" name="idcliente" id="idcliente" value="<?php echo $idcliente; ?>">
                <a href="#" class="btn_view" onclick="event.preventDefault(); abrirModalBusquedaCliente();" style="display:inline-block; margin-bottom:10px;"><i class="fas fa-search"></i> Cambiar Cliente</a>

                <label for="tipo_pago">Método de Pago</label>
                <select name="tipo_pago" id="tipo_pago">
                    <option value="1" <?php if($tipo_pago == 1) echo 'selected'; ?>>Efectivo</option>
                    <option value="2" <?php if($tipo_pago == 2) echo 'selected'; ?>>Transferencia</option>
                    <option value="3" <?php if($tipo_pago == 3) echo 'selected'; ?>>Crédito</option>
                    <option value="4" <?php if($tipo_pago == 4) echo 'selected'; ?>>QR</option>
                    <option value="5" <?php if($tipo_pago == 5) echo 'selected'; ?>>Tarjeta</option>
                </select>

                <label for="status">Estado</label>
                <select name="status" id="status">
                    <option value="1" <?php if($status_venta == 1) echo 'selected'; ?>>Pagada</option>
                    <option value="3" <?php if($status_venta == 3) echo 'selected'; ?>>Crédito</option>
                    <option value="2" <?php if($status_venta == 2) echo 'selected'; ?>>Anulada</option>
                </select>

                <button type="submit" class="btn_save"><i class="fas fa-save"></i> Actualizar Venta</button>
                <a href="ventas.php" class="btn_cancel" style="display:inline-block; text-align:center; padding:10px; margin-top:10px; text-decoration:none; color:#fff; background:#e74c3c; border-radius:3px;">Cancelar</a>
            </form>
        </div>
    </section>

    <script>
    // Sobrescribir seleccionarCliente para que funcione con este formulario
    function seleccionarCliente(id, nombre, nit, telefono, direccion) {
        $('#idcliente').val(id);
        $('#dummy_cliente').val(nombre);
        coloseModal();
    }

    <?php if (isset($query_update) && $query_update): ?>
    // Redirigir a la lista de ventas después de 2 segundos si la actualización fue exitosa
    setTimeout(function() {
        window.location.href = 'ventas.php';
    }, 2000);
    <?php endif; ?>
    </script>
</body>
</html>
