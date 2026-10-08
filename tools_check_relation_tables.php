<?php
require 'conexion.php';
$tables = ['categoria_marca', 'categoria_tamano', 'marca_tamano'];
$result = [];
foreach ($tables as $table) {
    $query = mysqli_query($conection, "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . mysqli_real_escape_string($conection, $table) . "'");
    $result[$table] = ($query && mysqli_num_rows($query) > 0);
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'ok', 'tables' => $result], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
mysqli_close($conection);
