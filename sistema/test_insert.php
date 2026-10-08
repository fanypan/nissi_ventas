<?php
include "../conexion.php";
session_start();

$token = md5('1'); // Sample user token
$codproducto = 1325; // Sample product ID from earlier dump
$cantidad = 1;
$costo = 250.00;
$fecha_venc = "NULL";

$sql = "INSERT INTO detalle_temp_compra (token_user, codproducto, cantidad, precio_venta, fecha_vencimiento) 
        VALUES ('$token', $codproducto, $cantidad, $costo, $fecha_venc)";

echo "SQL: $sql\n";

$result = mysqli_query($conection, $sql);

if ($result) {
    echo "SUCCESS\n";
    mysqli_query($conection, "DELETE FROM detalle_temp_compra WHERE token_user = '$token' AND codproducto = $codproducto");
} else {
    echo "ERROR: " . mysqli_error($conection) . "\n";
}

mysqli_close($conection);
?>
