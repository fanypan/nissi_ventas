<?php
include "conexion.php";

echo "<h1>Migración: Módulo de Insumos v2</h1>";

// 1. Añadir columna stock a la tabla insumos si no existe
$check_column = mysqli_query($conection, "SHOW COLUMNS FROM insumos LIKE 'stock'");
if (mysqli_num_rows($check_column) == 0) {
    $alter_query = mysqli_query($conection, "ALTER TABLE insumos ADD COLUMN stock DECIMAL(10,2) DEFAULT 0.00 AFTER precio_referencia");
    if ($alter_query) {
        echo "<p style='color:green;'>[OK] Columna 'stock' añadida a la tabla 'insumos'.</p>";
    } else {
        echo "<p style='color:red;'>[ERROR] No se pudo añadir la columna 'stock': " . mysqli_error($conection) . "</p>";
    }
} else {
    echo "<p style='color:blue;'>[INFO] La columna 'stock' ya existe en la tabla 'insumos'.</p>";
}

// 2. Crear tabla consumo_insumos
$table_query = "CREATE TABLE IF NOT EXISTS `consumo_insumos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_insumo` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_insumo` (`id_insumo`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if (mysqli_query($conection, $table_query)) {
    echo "<p style='color:green;'>[OK] Tabla 'consumo_insumos' creada o ya existente.</p>";
} else {
    echo "<p style='color:red;'>[ERROR] Error al crear tabla 'consumo_insumos': " . mysqli_error($conection) . "</p>";
}

echo "<br><a href='sistema/'>Volver al sistema</a>";
?>
