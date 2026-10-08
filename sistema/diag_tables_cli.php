<?php
include "../conexion.php";

$tables = ['detalle_temp', 'detalle_temp_compra'];

foreach ($tables as $table) {
    echo "Table: $table\n";
    $res = mysqli_query($conection, "DESCRIBE $table");
    if ($res) {
        while($row = mysqli_fetch_assoc($res)){
            echo " - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo " - ERROR: Table does not exist.\n";
    }
    echo "\n";
}

mysqli_close($conection);
?>
