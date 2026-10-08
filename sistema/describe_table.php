<?php
include "../conexion.php";
$res = mysqli_query($conection, "DESCRIBE detalle_temp_compra");
while($row = mysqli_fetch_assoc($res)){
    echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Default'] . "\n";
}
mysqli_close($conection);
?>
