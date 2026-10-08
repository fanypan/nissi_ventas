<?php
include "conexion.php";

echo "=== VERIFICAR CODIGOS 1157 A 1176 ===\n";
for ($i = 1157; $i <= 1176; $i++) {
    $c = (string)$i;
    $q = mysqli_query($conection, "SELECT codigo, descripcion FROM producto WHERE codigo = '$c'");
    if (mysqli_num_rows($q) > 0) {
        $r = mysqli_fetch_assoc($q);
        echo "Codigo $c existe: " . $r['descripcion'] . "\n";
    } else {
        echo "Codigo $c DISPONIBLE\n";
    }
}
