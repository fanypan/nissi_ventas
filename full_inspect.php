<?php
include "conexion.php";

function q($sql) {
    global $conection;
    $res = mysqli_query($conection, $sql);
    if (!$res) return "Error: " . mysqli_error($conection);
    return $res;
}

echo "--- TABLES ---\n";
$tables = q("SHOW TABLES");
while ($t = mysqli_fetch_array($tables)) {
    $table = $t[0];
    $count = mysqli_fetch_array(q("SELECT COUNT(*) FROM `$table`"))[0];
    echo "[$table] - $count rows\n";
}

echo "\n--- PRODUCTO SCHEMA ---\n";
$schema = q("DESCRIBE producto");
while ($s = mysqli_fetch_assoc($schema)) {
    echo $s['Field'] . " | " . $s['Type'] . "\n";
}

// Search for category table
$catTable = "";
$search = q("SHOW TABLES LIKE '%cat%'");
if (mysqli_num_rows($search) > 0) {
    while ($r = mysqli_fetch_array($search)) {
        echo "\n--- CATEGORY TABLE CANDIDATE: " . $r[0] . " ---\n";
        $catTable = $r[0];
        $sCat = q("DESCRIBE `$catTable` ");
        while ($sc = mysqli_fetch_assoc($sCat)) {
            echo $sc['Field'] . " | " . $sc['Type'] . "\n";
        }
    }
}
?>
