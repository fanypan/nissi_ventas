<?php
include "../conexion.php";
session_start();

// Mock session
$_SESSION['idUser'] = 1;

// Mock POST data (formato nuevo)
$_POST['action'] = 'addProductoDetalleCompra';
$_POST['txt_cod_producto_compra'] = 1325;
$_POST['txt_cant_producto_compra'] = 2;
$_POST['txt_precio_compra'] = 260.50;
$_POST['txt_fecha_vencimiento'] = '2027-12-31';

echo "--- Testing New Form Format ---\n";
ob_start();
include "ajax.php";
$output1 = ob_get_clean();
echo "Output 1: $output1\n\n";

// Mock POST data (formato antiguo/fallback)
$_POST['action'] = 'addProductoDetalleCompra';
unset($_POST['txt_cod_producto_compra']);
unset($_POST['txt_cant_producto_compra']);
unset($_POST['txt_precio_compra']);
$_POST['producto'] = 1326;
$_POST['cantidad'] = 3;
$_POST['precio'] = 210.00;

echo "--- Testing Old Form Format ---\n";
ob_start();
// Reset some state if needed, but ajax.php exists on first include
// Since we used include, we might need a separate process or a function-wrapped version, 
// but for a quick check, let's see if it runs again (it likely won't because of the exit in ajax.php)
// So we'll run it as a separate command for each test.
?>
