<?php
include "../conexion.php";

echo "<h1>Diagnóstico de Base de Datos</h1>";

echo "<h2>Estructura de tabla 'venta'</h2>";
$res = mysqli_query($conection, "DESCRIBE venta");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while($row = mysqli_fetch_assoc($res)){
    echo "<tr><td>".implode("</td><td>", $row)."</td></tr>";
}
echo "</table>";

echo "<h2>Procedimiento 'procesar_venta'</h2>";
$res = mysqli_query($conection, "SHOW CREATE PROCEDURE procesar_venta");
$row = mysqli_fetch_assoc($res);
echo "<pre>".htmlspecialchars($row['Create Procedure'])."</pre>";

mysqli_close($conection);
?>
