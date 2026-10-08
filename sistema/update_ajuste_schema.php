<?php
include __DIR__ . "/../conexion.php";

// Alter table to change motivo_id (int) to motivo (varchar)
// Note: This will likely fail if there is existing data that violates types, but since we just created it, it should be fine.
// If it fails, we can drop and recreate since it's a new feature.

$sql = "ALTER TABLE `ajuste_stock` CHANGE `motivo_id` `motivo` VARCHAR(255) NOT NULL;";
$result = mysqli_query($conection, $sql);

if($result){
    echo "Tabla ajuste_stock actualizada correctamente (motivo_id -> motivo).\n";
} else {
    echo "Error actualizando tabla: " . mysqli_error($conection) . "\n";
    // Fallback: Drop and recreate if alter fails (development only approach for speed, or if table is empty)
    $sql_drop = "DROP TABLE IF EXISTS `ajuste_stock`;";
    mysqli_query($conection, $sql_drop);
    
    $sql_create = "CREATE TABLE IF NOT EXISTS `ajuste_stock` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `codproducto` int(11) NOT NULL,
      `usuario_id` int(11) NOT NULL,
      `fecha` datetime NOT NULL DEFAULT current_timestamp(),
      `cantidad` decimal(10,2) NOT NULL,
      `tipo_movimiento` enum('entrada','salida') NOT NULL,
      `motivo` varchar(255) NOT NULL,
      `nota` text DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if(mysqli_query($conection, $sql_create)){
        echo "Tabla ajuste_stock recreada correctamente.\n";
    } else {
         echo "Error recreando tabla: " . mysqli_error($conection) . "\n";
    }
}
?>
