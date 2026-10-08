<?php
include __DIR__ . "/../conexion.php";

$sql1 = "CREATE TABLE IF NOT EXISTS `motivos_ajuste` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$sql2 = "CREATE TABLE IF NOT EXISTS `ajuste_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codproducto` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `cantidad` decimal(10,2) NOT NULL,
  `tipo_movimiento` enum('entrada','salida') NOT NULL,
  `motivo_id` int(11) NOT NULL,
  `nota` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$result1 = mysqli_query($conection, $sql1);
if($result1){
    echo "Tabla motivos_ajuste creada correctamente.\n";
} else {
    echo "Error creando motivos_ajuste: " . mysqli_error($conection) . "\n";
}

$result2 = mysqli_query($conection, $sql2);
if($result2){
    echo "Tabla ajuste_stock creada correctamente.\n";
} else {
    echo "Error creando ajuste_stock: " . mysqli_error($conection) . "\n";
}
?>
