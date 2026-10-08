<?php
include "../conexion.php";
session_start();
$_SESSION['idUser'] = 1;

// Simulate active caja for foreign key
$id_caja = 0;
$query_caja = mysqli_query($conection, "SELECT id FROM caja WHERE status = 1 LIMIT 1");
if ($query_caja && mysqli_num_rows($query_caja) > 0) {
    $data_caja = mysqli_fetch_assoc($query_caja);
    $id_caja = $data_caja['id'];
}

echo "Active Caja: $id_caja\n";

// Test data
$id_insumo = 9; // Agua mineral
$cantidad_unidades = 5.00;
$precio_unitario = 14000.00;
$total_fila = $precio_unitario * $cantidad_unidades;
$id_compra = time();
$descripcion = "Test Purchase";
$establecimiento = "Test Store";
$user = 1;

echo "Attempting to insert egreso and update stock for insumo $id_insumo...\n";

$query_insert = mysqli_query($conection, 
    "INSERT INTO egresos(descripcion, establecimiento, cantidad, tipo_egreso, id_compra_insumo, id_insumo, precio_unitario, cantidad_unidades, usuario, caja)
     VALUES('$descripcion', '$establecimiento', $total_fila, 2, $id_compra, $id_insumo, $precio_unitario, $cantidad_unidades, $user, $id_caja)");

if ($query_insert) {
    echo "Insert SUCCESS\n";
    $query_update = mysqli_query($conection, "UPDATE insumos SET stock = stock + $cantidad_unidades WHERE id = $id_insumo");
    if ($query_update) {
        echo "Update SUCCESS. New stock checked below.\n";
        $res = mysqli_query($conection, "SELECT stock FROM insumos WHERE id = $id_insumo");
        $data = mysqli_fetch_assoc($res);
        echo "Current Stock in DB: " . $data['stock'] . "\n";
    } else {
        echo "Update FAILED: " . mysqli_error($conection) . "\n";
    }
} else {
    echo "Insert FAILED: " . mysqli_error($conection) . "\n";
}

mysqli_close($conection);
?>
