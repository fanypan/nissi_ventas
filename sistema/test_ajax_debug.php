<?php
include "../conexion.php";
mysqli_report(MYSQLI_REPORT_OFF);

$procedures_needed = [
    'actualizar_precio_producto',
    'add_detalle_temp',
    'del_detalle_temp',
    'cancelar_venta',
    'cancelar_compra',
    'procesar_venta',
    'anular_venta',
    'anular_compra',
    'procesar_compra',
    'dataDashboard'
];

echo "=== VERIFICANDO PROCEDIMIENTOS ===\n";

$r = mysqli_query($conection, "SHOW PROCEDURE STATUS WHERE Db = 'ventas_bd'");
$existing = [];
while($row = mysqli_fetch_assoc($r)) {
    $existing[] = $row['Name'];
}

foreach ($procedures_needed as $proc) {
    if (in_array($proc, $existing)) {
        echo "OK  $proc\n";
    } else {
        echo "FALTA  $proc\n";
    }
}

mysqli_close($conection);
