<?php
include "../conexion.php";
session_start();
$_SESSION['idUser'] = 1;
$_POST = [
    'action' => 'addProductoDetalleCompra',
    'txt_cod_producto_compra' => 1325,
    'txt_cant_producto_compra' => 2,
    'txt_precio_compra' => 260.50,
    'txt_fecha_vencimiento' => '2027-12-31'
];
include "ajax.php";
?>
