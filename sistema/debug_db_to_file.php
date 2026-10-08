<?php
include "../conexion.php";

$data = [];

$res = mysqli_query($conection, "DESCRIBE venta");
while($row = mysqli_fetch_assoc($res)){
    $data['venta_struct'][] = $row;
}

$res = mysqli_query($conection, "SHOW CREATE PROCEDURE procesar_venta");
$row = mysqli_fetch_assoc($res);
$data['procesar_venta_create'] = $row['Create Procedure'];

file_put_contents('db_diag.json', json_encode($data, JSON_PRETTY_PRINT));
mysqli_close($conection);
?>
