<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../conexion.php";
if (!$conection) {
    die("Connection failed: " . mysqli_connect_error());
}

session_start();
$_SESSION['idUser'] = 1; e

$_POST = [
    'action' => 'addProductoDetalleCompra',
    'txt_cod_producto_compra' => 1325,
    'txt_cant_producto_compra' => 2,
    'txt_precio_compra' => 260.50,
    'txt_fecha_vencimiento' => '2027-12-31'
];

echo "Including ajax.php...\n";
include "ajax.php";
echo "\nAfter include.\n";
?>
