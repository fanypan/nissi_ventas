<?php
include "conexion.php";
$query_caja = mysqli_query($conection,"SELECT * FROM caja WHERE status = 1");
if(mysqli_num_rows($query_caja) > 0) {
    $data_caja = mysqli_fetch_assoc($query_caja);
    $id_caja = $data_caja['id'];
    echo "caja id: $id_caja \n";
    $query_dash = mysqli_query($conection,"CALL dataDashboard($id_caja);");
    if(!$query_dash) {
        echo "Error: " . mysqli_error($conection) . "\n";
    } else {
        $data_dash = mysqli_fetch_assoc($query_dash);
        print_r($data_dash);
    }
}
?>
