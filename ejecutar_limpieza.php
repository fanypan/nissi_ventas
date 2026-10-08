<?php
// Reportar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "conexion.php";

echo "<h1>Iniciando Limpieza de Base de Datos (Modo Seguro)</h1>";
echo "<p>C: tiene espacio suficiente. Procediendo...</p>";

if (!$conection) {
    die("Error de conexión: " . mysqli_connect_error());
}

// 1. Deshabilitar revisión de llaves foráneas
mysqli_query($conection, "SET FOREIGN_KEY_CHECKS = 0;");

$tablas = [
    "detalleventa",
    "detalle_recibo",
    "detalle_temp",
    "transferencias",
    "pagos_qr",
    "pagos_tarjeta",
    "venta",
    "entradas",
    "detalle_recibo_compra",
    "detalle_temp_compra",
    "compras",
    "egresos",
    "caja",
    "movimientos_lotes",
    "lotes",
    "producto"
];

foreach ($tablas as $tabla) {
    echo "Limpiando tabla <strong>$tabla</strong>... ";
    
    // Usamos DELETE en lugar de TRUNCATE para evitar error #1701
    $del = mysqli_query($conection, "DELETE FROM `$tabla`;");
    
    if ($del) {
        // Resetear el contador de IDs
        mysqli_query($conection, "ALTER TABLE `$tabla` AUTO_INCREMENT = 1;");
        echo "<span style='color:green;'>VACIADA</span><br>";
    } else {
        echo "<span style='color:red;'>ERROR: " . mysqli_error($conection) . "</span><br>";
    }
}

// 2. Habilitar nuevamente la revisión de llaves foráneas
mysqli_query($conection, "SET FOREIGN_KEY_CHECKS = 1;");

echo "<h2>¡Limpieza completada con éxito!</h2>";
echo "<p>Ya puede borrar este archivo: <strong>ejecutar_limpieza.php</strong></p>";
echo "<p><a href='sistema/'>Volver al Sistema</a></p>";
?>
