<?php
include "conexion.php";
// 1. Rename 'motivo' to 'motivo_id' and change type to INT
$sql = "ALTER TABLE ajuste_stock CHANGE motivo motivo_id INT NOT NULL";
$result = mysqli_query($conection, $sql);

if ($result) {
    echo "Estructura de tabla ajuste_stock actualizada correctamente.<br>";
} else {
    // Si falla porque no existe 'motivo', tal vez ya tiene 'motivo_id' o es otro error
    echo "Error actualizando tabla: " . mysqli_error($conection) . "<br>";
    
    // Intentar agregarla como nueva si no existe ni 'motivo' ni 'motivo_id'
    $sql_add = "ALTER TABLE ajuste_stock ADD COLUMN motivo_id INT NOT NULL AFTER tipo_movimiento";
    if (mysqli_query($conection, $sql_add)) {
        echo "Columna motivo_id agregada correctamente.<br>";
    }
}
?>
