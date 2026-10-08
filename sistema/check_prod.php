<?php
$c = mysqli_connect("localhost","root","","ventas_bd");
$r = mysqli_query($c, "DESCRIBE producto");
while($row = mysqli_fetch_assoc($r)) {
    print_r($row);
}
?>
