<?php
require 'conexion.php';

$colsToCheck = ['codigo_interno','precio_mayorista','categoria_id','marca_id','tamano_id'];
$result = [];
foreach ($colsToCheck as $col) {
    $q = mysqli_query($conection, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'producto' AND COLUMN_NAME = '". mysqli_real_escape_string($conection, $col) ."'");
    $result[$col] = ($q && mysqli_num_rows($q) > 0) ? true : false;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([ 'status' => 'ok', 'database' => mysqli_get_client_info($conection), 'columns' => $result ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

mysqli_close($conection);
