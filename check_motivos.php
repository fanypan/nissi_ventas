<?php
include "conexion.php";
$query = mysqli_query($conection, "SELECT * FROM motivos_ajuste");
if (!$query) {
    echo "Error: " . mysqli_error($conection);
} else {
    $rows = mysqli_num_rows($query);
    echo "Total motivos: " . $rows . "<br>";
    while ($data = mysqli_fetch_assoc($query)) {
        echo "ID: " . $data['id'] . " - Desc: " . $data['descripcion'] . " - Status: " . $data['status'] . "<br>";
    }
}
?>
