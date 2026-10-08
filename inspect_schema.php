<?php
include "conexion.php";
$result = mysqli_query($conection, "SHOW TABLES");
while ($row = mysqli_fetch_array($result)) {
    $table = $row[0];
    echo "TABLE: $table\n";
    $result2 = mysqli_query($conection, "DESCRIBE $table");
    while ($row2 = mysqli_fetch_assoc($result2)) {
        echo "  - " . $row2['Field'] . " (" . $row2['Type'] . ")\n";
    }
    echo "\n";
}
?>
