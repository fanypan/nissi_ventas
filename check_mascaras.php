<?php
include "conexion.php";

echo "=== BUSCAR CATEGORIA MASCARAS ===\n";
$q_cat = mysqli_query($conection, "SELECT * FROM categoria WHERE nombre LIKE '%MASCARA%' OR nombre LIKE '%Mascara%'");
while ($r = mysqli_fetch_assoc($q_cat)) {
    echo "ID: {$r['idcategoria']} | Nombre: {$r['nombre']} | Status: {$r['status']}\n";
}

echo "\n=== PROVEEDOR ASA IMPORT ===\n";
$q_prov = mysqli_query($conection, "SELECT * FROM proveedor WHERE proveedor LIKE '%Asa%'");
while ($r = mysqli_fetch_assoc($q_prov)) {
    echo "ID: {$r['codproveedor']} | Proveedor: {$r['proveedor']}\n";
}

echo "\n=== ULTIMO CODIGO REGISTRADO ===\n";
$q_max = mysqli_query($conection, "SELECT codproducto, codigo, descripcion FROM producto ORDER BY codproducto DESC LIMIT 5");
while ($r = mysqli_fetch_assoc($q_max)) {
    echo "ID: {$r['codproducto']} | Cod: {$r['codigo']} | Desc: {$r['descripcion']}\n";
}
