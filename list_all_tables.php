<?php
include "conexion.php";

echo "--- DATABASE TABLES ---\n";
$result = mysqli_query($conection, "SHOW TABLES");
if ($result) {
    while ($row = mysqli_fetch_array($result)) {
        $table = $row[0];
        $countResult = mysqli_query($conection, "SELECT COUNT(*) as total FROM `$table` ");
        $count = 0;
        if ($countResult) {
            $countRow = mysqli_fetch_assoc($countResult);
            $count = $countRow['total'];
        }
        echo "- $table ($count rows)\n";
    }
} else {
    echo "Error listing tables: " . mysqli_error($conection);
}
?>
