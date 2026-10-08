<?php
include "conexion.php";
$query = mysqli_query($conection, "SELECT * FROM configuracion");
$data = mysqli_fetch_assoc($query);
print_r($data);
?>
