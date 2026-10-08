<?php
include "../conexion.php";

echo "--- STRUCT VENTA ---\n";
$res = mysqli_query($conection, "DESCRIBE venta");
while($row = mysqli_fetch_assoc($res)){
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n--- PROC PROCESAR_VENTA ---\n";
$res = mysqli_query($conection, "SHOW CREATE PROCEDURE procesar_venta");
$row = mysqli_fetch_assoc($res);
echo $row['Create Procedure'] . "\n";

mysqli_close($conection);
?>
