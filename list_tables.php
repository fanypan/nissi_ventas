<?php
include "conexion.php";
$result = mysqli_query($conection, "SHOW TABLES");
while ($row = mysqli_fetch_array($result)) {
    echo $row[0] . "<br>";
}
?>
