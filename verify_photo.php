<?php
include "conexion.php";
$query = mysqli_query($conection, "SELECT foto FROM configuracion");
$data = mysqli_fetch_assoc($query);
$foto = $data['foto'];
$path = __DIR__ . "/sistema/factura/img/" . $foto;
echo "Buscando foto en: " . $path . "\n";
if (file_exists($path)) {
    echo "EL ARCHIVO EXISTE Y ES LEGIBLE.\n";
    echo "Tamaño: " . filesize($path) . " bytes\n";
} else {
    echo "EL ARCHIVO NO EXISTE EN ESA RUTA.\n";
}
?>
