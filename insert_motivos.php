<?php
include "conexion.php";
$motivos = [
    'Corrección de Inventario',
    'Producto Vencido',
    'Producto Dañado',
    'Robo/Pérdida',
    'Consumo Interno',
    'Error de Carga'
];

foreach ($motivos as $motivo) {
    $query_insert = mysqli_query($conection, "INSERT INTO motivos_ajuste(descripcion) VALUES('$motivo')");
    if ($query_insert) {
        echo "Insertado: $motivo<br>";
    } else {
        echo "Error insertando $motivo: " . mysqli_error($conection) . "<br>";
    }
}
?>
