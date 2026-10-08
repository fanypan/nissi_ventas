<?php
include "conexion.php";
$query = mysqli_query($conection, "DESCRIBE ajuste_stock");
if (!$query) {
    echo "Error: " . mysqli_error($conection);
} else {
    while ($row = mysqli_fetch_assoc($query)) {
        echo "Campo: " . $row['Field'] . " - Tipo: " . $row['Type'] . "<br>";
    }
}
?>
