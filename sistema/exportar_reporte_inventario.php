<?php
session_start();
if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2) {
    header("location: ./");
    exit;
}
include "../conexion.php";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_inventario_" . date('d_m_Y') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

$categoria_id = isset($_GET['categoria_id']) ? trim($_GET['categoria_id']) : '';
$marca_id     = isset($_GET['marca_id']) ? trim($_GET['marca_id']) : '';
$estado_stock = isset($_GET['estado_stock']) ? trim($_GET['estado_stock']) : '';
$proveedor_id = isset($_GET['proveedor_id']) ? trim($_GET['proveedor_id']) : '';
$busqueda     = isset($_GET['busqueda']) ? mysqli_real_escape_string($conection, trim($_GET['busqueda'])) : '';

$where = " WHERE p.status = 1 ";

if ($categoria_id !== '' && is_numeric($categoria_id)) {
    $where .= " AND p.categoria_id = $categoria_id ";
}
if ($marca_id !== '' && is_numeric($marca_id)) {
    $where .= " AND p.marca_id = $marca_id ";
}
if ($proveedor_id !== '' && is_numeric($proveedor_id)) {
    $where .= " AND p.proveedor = $proveedor_id ";
}
if ($estado_stock === 'con_stock') {
    $where .= " AND p.existencia > 0 ";
} else if ($estado_stock === 'stock_bajo') {
    $where .= " AND p.existencia > 0 AND p.existencia <= 5 ";
} else if ($estado_stock === 'agotado') {
    $where .= " AND p.existencia = 0 ";
}
if ($busqueda !== '') {
    $where .= " AND (p.codigo LIKE '%$busqueda%' OR p.descripcion LIKE '%$busqueda%') ";
}

$query = mysqli_query($conection, "SELECT p.codproducto, p.codigo, p.descripcion, p.existencia, p.costo, p.precio, 
                                          c.nombre as categoria, m.nombre as marca, t.nombre as tamano, pr.proveedor
                                   FROM producto p
                                   LEFT JOIN categoria c ON p.categoria_id = c.idcategoria
                                   LEFT JOIN marca m ON p.marca_id = m.idmarca
                                   LEFT JOIN tamano t ON p.tamano_id = t.idtamano
                                   LEFT JOIN proveedor pr ON p.proveedor = pr.codproveedor
                                   $where ORDER BY p.descripcion ASC");

$sum_stock = 0;
$sum_costo_total = 0;
$sum_venta_total = 0;
$sum_items = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
        th { background-color: #1f497d; color: #ffffff; border: 1px solid #000000; padding: 10px; font-weight: bold; text-align: center; }
        td { border: 1px solid #000000; padding: 6px; }
        tr:nth-child(even) { background-color: #f2f5f8; }
        .total-row { background-color: #d9e1f2; font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="11" style="background-color: #1f497d; color: white; font-size: 18px; text-align: center; font-weight: bold; padding: 12px; border: 1px solid #000;">
                REPORTE DETALLADO DE INVENTARIO
            </td>
        </tr>
        <tr>
            <th style="background-color: #1f497d; color: white;">Código</th>
            <th style="background-color: #1f497d; color: white;">Descripción / Producto</th>
            <th style="background-color: #1f497d; color: white;">Categoría</th>
            <th style="background-color: #1f497d; color: white;">Marca</th>
            <th style="background-color: #1f497d; color: white;">Tamaño</th>
            <th style="background-color: #1f497d; color: white;">Proveedor</th>
            <th style="background-color: #1f497d; color: white;">Stock</th>
            <th style="background-color: #1f497d; color: white;">Costo Unit. (Gs.)</th>
            <th style="background-color: #1f497d; color: white;">Precio Unit. (Gs.)</th>
            <th style="background-color: #1f497d; color: white;">Inversión Total Costo (Gs.)</th>
            <th style="background-color: #1f497d; color: white;">Valor Venta Total (Gs.)</th>
        </tr>
        <?php
        if (mysqli_num_rows($query) > 0) {
            while ($data = mysqli_fetch_assoc($query)) {
                $stock = (int)$data['existencia'];
                $costo = (float)$data['costo'];
                $precio = (float)$data['precio'];
                $total_costo_item = $stock * $costo;
                $total_venta_item = $stock * $precio;

                $sum_items++;
                $sum_stock += $stock;
                $sum_costo_total += $total_costo_item;
                $sum_venta_total += $total_venta_item;

                $cat_txt = !empty($data['categoria']) ? $data['categoria'] : 'Sin asignar';
                $marca_txt = !empty($data['marca']) ? $data['marca'] : 'Sin asignar';
                $tamano_txt = !empty($data['tamano']) ? $data['tamano'] : 'Sin asignar';
                $prov_txt = !empty($data['proveedor']) ? $data['proveedor'] : 'Sin asignar';
                ?>
                <tr>
                    <td style="text-align: center;"><?php echo htmlspecialchars($data['codigo']); ?></td>
                    <td><?php echo htmlspecialchars($data['descripcion']); ?></td>
                    <td><?php echo htmlspecialchars($cat_txt); ?></td>
                    <td><?php echo htmlspecialchars($marca_txt); ?></td>
                    <td><?php echo htmlspecialchars($tamano_txt); ?></td>
                    <td><?php echo htmlspecialchars($prov_txt); ?></td>
                    <td style="text-align: center;"><?php echo number_format($stock, 0, ',', '.'); ?></td>
                    <td style="text-align: right;"><?php echo number_format($costo, 0, ',', '.'); ?></td>
                    <td style="text-align: right;"><?php echo number_format($precio, 0, ',', '.'); ?></td>
                    <td style="text-align: right;"><?php echo number_format($total_costo_item, 0, ',', '.'); ?></td>
                    <td style="text-align: right;"><?php echo number_format($total_venta_item, 0, ',', '.'); ?></td>
                </tr>
                <?php
            }
            ?>
            <tr class="total-row">
                <td colspan="6" style="text-align: right; font-weight: bold;">TOTALES (<?php echo $sum_items; ?> Productos):</td>
                <td style="text-align: center; font-weight: bold;"><?php echo number_format($sum_stock, 0, ',', '.'); ?></td>
                <td colspan="2"></td>
                <td style="text-align: right; font-weight: bold;"><?php echo number_format($sum_costo_total, 0, ',', '.'); ?></td>
                <td style="text-align: right; font-weight: bold;"><?php echo number_format($sum_venta_total, 0, ',', '.'); ?></td>
            </tr>
            <?php
        } else {
            echo '<tr><td colspan="11" style="text-align:center;">No se encontraron registros con los filtros seleccionados.</td></tr>';
        }
        ?>
    </table>
</body>
</html>
