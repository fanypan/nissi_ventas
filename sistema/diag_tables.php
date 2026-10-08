<?php
include "../conexion.php";

echo "<h1>Diagnóstico de Tablas de Detalle Temporal</h1>";

$tables = ['detalle_temp', 'detalle_temp_compra'];

foreach ($tables as $table) {
    echo "<h2>Estructura de tabla '$table'</h2>";
    $res = mysqli_query($conection, "DESCRIBE $table");
    if ($res) {
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while($row = mysqli_fetch_assoc($res)){
            echo "<tr><td>".implode("</td><td>", $row)."</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>La tabla '$table' no existe o no se puede acceder.</p>";
    }
}

mysqli_close($conection);
?>
