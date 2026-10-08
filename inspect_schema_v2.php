<?php
include "conexion.php";

echo "--- ALL TABLES AND ROW COUNTS ---\n";
$result = mysqli_query($conection, "SHOW TABLES");
while ($row = mysqli_fetch_array($result)) {
    $table = $row[0];
    $countRes = mysqli_query($conection, "SELECT COUNT(*) as total FROM `$table` balance");
    // Some tables might have reserved names, using backticks
    $countRes = mysqli_query($conection, "SELECT COUNT(*) as total FROM `$table` ");
    $count = mysqli_fetch_assoc($countRes)['total'];
    echo "TABLE: $table ($count rows)\n";
}

echo "\n--- PRODUCT STRUCTURE ---\n";
$resProd = mysqli_query($conection, "DESCRIBE producto");
if ($resProd) {
    while ($row = mysqli_fetch_assoc($resProd)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Table 'producto' not found.\n";
}
?>
