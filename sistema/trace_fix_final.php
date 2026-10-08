<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../conexion.php";
if (!$conection) {
    die("Connection failed: " . mysqli_connect_error());
}

session_start();
$_SESSION['idUser'] = 1;

$_POST = [
    'action' => 'addProductoDetalleCompra',
    'txt_cod_producto_compra' => 1325,
    'txt_cant_producto_compra' => 1,
    'txt_precio_compra' => 100.00,
    'txt_fecha_vencimiento' => '2026-12-31'
];

echo "--- NEW FORMAT TEST ---\n";
include "ajax.php";
// Note: ajax.php calls exit, so we can't test more in one run.
?>
