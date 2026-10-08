<?php
include "conexion.php";
$query = mysqli_query($conection, "DESCRIBE detalleventa");
while ($row = mysqli_fetch_assoc($query)) {
    echo "Campo: " . $row['Field'] . " - Tipo: " . $row['Type'] . "<br>";
}
?>
