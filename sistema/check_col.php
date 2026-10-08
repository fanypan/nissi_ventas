<?php
include "../conexion.php";
$res = mysqli_query($conection, "SHOW COLUMNS FROM detalle_temp_compra LIKE 'fecha_vencimiento'");
if ($res && mysqli_num_rows($res) > 0) {
    echo "EXISTS";
} else {
    echo "MISSING";
}
mysqli_close($conection);
?>
