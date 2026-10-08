<?php
include "conexion.php";
$tables = ['caja', 'motivos_ajuste', 'ajuste_stock', 'egresos', 'insumos', 'usuario', 'producto'];
foreach ($tables as $table) {
    if ($result = mysqli_query($conection, "SHOW TABLES LIKE '$table'")) {
        if (mysqli_num_rows($result) > 0) {
            echo "Tabla $table: OK<br>";
        } else {
            echo "Tabla $table: NO EXISTE<br>";
        }
    } else {
        echo "Error verificando $table: " . mysqli_error($conection) . "<br>";
    }
}
?>
