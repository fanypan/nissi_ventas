<?php
include "../conexion.php";
echo "<h2>Iniciando migración de relaciones...</h2>";

$sql1 = "CREATE TABLE IF NOT EXISTS `categoria_marca` (
    `categoria_id` INT NOT NULL,
    `marca_id` INT NOT NULL,
    PRIMARY KEY (`categoria_id`, `marca_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if(mysqli_query($conection, $sql1)) echo "<p>Tabla categoria_marca OK</p>";

$sql2 = "CREATE TABLE IF NOT EXISTS `marca_tamano` (
    `marca_id` INT NOT NULL,
    `tamano_id` INT NOT NULL,
    PRIMARY KEY (`marca_id`, `tamano_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if(mysqli_query($conection, $sql2)) echo "<p>Tabla marca_tamano OK</p>";

// Llenar con datos de prueba si están vacíos
$q = mysqli_query($conection, "SELECT COUNT(*) as c FROM categoria_marca");
$r = mysqli_fetch_assoc($q);
if($r['c'] == 0) {
    mysqli_query($conection, "INSERT INTO categoria_marca (categoria_id, marca_id) VALUES (1, 1), (1, 2), (2, 3)");
    echo "<p>Datos en categoria_marca insertados</p>";
}

$q = mysqli_query($conection, "SELECT COUNT(*) as c FROM marca_tamano");
$r = mysqli_fetch_assoc($q);
if($r['c'] == 0) {
    mysqli_query($conection, "INSERT INTO marca_tamano (marca_id, tamano_id) VALUES (1, 1), (1, 2), (2, 3)");
    echo "<p>Datos en marca_tamano insertados</p>";
}
echo "<h3>Listo</h3>";
?>
