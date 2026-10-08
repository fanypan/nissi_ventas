<?php
include "../conexion.php";

echo "Iniciando limpieza de la base de datos...\n";

// Desactivar restricciones de llaves foráneas para poder vaciar
mysqli_query($conection, "SET FOREIGN_KEY_CHECKS = 0;");

$tablas_a_vaciar = [
    'venta',
    'detalleventa',
    'detalle_recibo',
    'detalle_temp',
    'compras',
    'detalle_recibo_compra',
    'detalle_temp_compra',
    'egresos',
    'caja',
    'entradas',
    'producto',
    'proveedor',
    'cliente',
    'categoria',
    'marca',
    'tamano',
    'categoria_marca',
    'marca_tamano'
];

foreach ($tablas_a_vaciar as $tabla) {
    // Verificamos si la tabla existe por las dudas
    $check = mysqli_query($conection, "SHOW TABLES LIKE '$tabla'");
    if(mysqli_num_rows($check) > 0) {
        $truncate = mysqli_query($conection, "TRUNCATE TABLE `$tabla`");
        if ($truncate) {
            echo "Tabla $tabla vaciada correctamente.\n";
        } else {
            echo "Error al vaciar $tabla: " . mysqli_error($conection) . "\n";
        }
    } else {
        echo "La tabla $tabla no existe.\n";
    }
}

mysqli_query($conection, "SET FOREIGN_KEY_CHECKS = 1;");
echo "Limpieza finalizada.\n";
?>
