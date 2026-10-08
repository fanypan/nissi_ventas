<?php
include "../conexion.php";
session_start();
$_SESSION['idUser'] = 1;

function test($data) {
    global $conection;
    $_POST = $data;
    ob_start();
    include "ajax.php";
    $res = ob_get_clean();
    echo "Testing Action: " . $data['action'] . "\n";
    echo "Input Data: " . json_encode($data) . "\n";
    echo "Output: " . $res . "\n\n";
    
    // Cleanup if success
    if (strpos($res, 'detalle') !== false) {
        $token = md5('1');
        mysqli_query($conection, "DELETE FROM detalle_temp_compra WHERE token_user = '$token'");
    }
}

echo "Starting Verification...\n\n";

// Case 1: New form format
test([
    'action' => 'addProductoDetalleCompra',
    'txt_cod_producto_compra' => 1325,
    'txt_cant_producto_compra' => 2,
    'txt_precio_compra' => 260.50,
    'txt_fecha_vencimiento' => '2027-12-31'
]);

// Case 2: Old form format (fallback)
test([
    'action' => 'addProductoDetalleCompra',
    'producto' => 1326,
    'cantidad' => 3,
    'precio' => 210.00
]);

// Case 3: Error case (missing data)
test([
    'action' => 'addProductoDetalleCompra'
]);
?>
