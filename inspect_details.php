<?php
include "conexion.php";

echo "--- CATEGORIAS ---\n";
$resCat = mysqli_query($conection, "SHOW TABLES LIKE 'categoria'");
if (mysqli_num_rows($resCat) > 0) {
    $res = mysqli_query($conection, "SELECT * FROM categoria");
    while ($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
} else {
    // Try plural or common names if 'categoria' doesn't exist
    $resCat = mysqli_query($conection, "SHOW TABLES LIKE 'categorias'");
    if (mysqli_num_rows($resCat) > 0) {
        $res = mysqli_query($conection, "SELECT * FROM categorias");
        while ($row = mysqli_fetch_assoc($res)) {
            print_r($row);
        }
    }
}

echo "\n--- PRODUCTO STRUCTURE ---\n";
$resProd = mysqli_query($conection, "DESCRIBE producto");
while ($row = mysqli_fetch_assoc($resProd)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n--- PROVEEDOR ---\n";
$resProv = mysqli_query($conection, "SHOW TABLES LIKE 'proveedor'");
if (mysqli_num_rows($resProv) > 0) {
    $res = mysqli_query($conection, "SELECT * FROM proveedor LIMIT 5");
    while ($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
}
?>
