<?php
session_start();
if ($_SESSION['rol'] != 1) {
    header("location: ./");
}

include "../conexion.php";

$fecha_de = isset($_POST['fecha_de']) ? $_POST['fecha_de'] : date('Y-m-d');
$fecha_a = isset($_POST['fecha_a']) ? $_POST['fecha_a'] : date('Y-m-d');
$filtro_mes = isset($_POST['filtro_mes']) ? $_POST['filtro_mes'] : '';

// Ajustar fechas si es filtro mensual
if (!empty($filtro_mes)) {
    $fecha_de = $filtro_mes . '-01';
    $fecha_a = date("Y-m-t", strtotime($fecha_de));
}

// Obtener todas las cajas cerradas en el rango
// Nota: También podrías incluir la abierta si quieres un reporte "hasta el momento"
$query_cajas = mysqli_query($conection, "SELECT id FROM caja WHERE DATE(fecha) BETWEEN '$fecha_de' AND '$fecha_a'");
$ids_caja = [];
while($row = mysqli_fetch_assoc($query_cajas)){
    $ids_caja[] = $row['id'];
}

$resumen = [
    'inicio' => 0,
    'efectivo' => 0,
    'transferencia' => 0,
    'qr' => 0,
    'tarjeta' => 0,
    'credito' => 0,
    'abonos' => 0,
    'egresos' => 0
];

if (!empty($ids_caja)) {
    $ids_str = implode(',', $ids_caja);
    
    // Sumar inicios
    $query_inicio = mysqli_query($conection, "SELECT SUM(inicio) as total FROM caja WHERE id IN ($ids_str)");
    $row_inicio = mysqli_fetch_assoc($query_inicio);
    $resumen['inicio'] = $row_inicio['total'];

    // Sumar ventas y abonos
    $query_ventas = mysqli_query($conection, "
        SELECT 
            SUM(CASE WHEN status = 1 AND (tipo_pago_detalle IS NULL OR tipo_pago_detalle = 1) THEN totalventa ELSE 0 END) as efectivo,
            SUM(CASE WHEN status = 1 AND tipo_pago_detalle = 2 THEN totalventa ELSE 0 END) as transferencia,
            SUM(CASE WHEN status = 1 AND tipo_pago_detalle = 4 THEN totalventa ELSE 0 END) as qr,
            SUM(CASE WHEN status = 1 AND tipo_pago_detalle = 5 THEN totalventa ELSE 0 END) as tarjeta,
            SUM(CASE WHEN status = 3 THEN totalventa ELSE 0 END) as credito
        FROM venta 
        WHERE caja IN ($ids_str)
    ");
    $resumen = array_merge($resumen, mysqli_fetch_assoc($query_ventas));

    // Abonos
    $query_abonos = mysqli_query($conection, "SELECT SUM(cantidad) as total FROM detalle_recibo WHERE caja IN ($ids_str)");
    $row_abonos = mysqli_fetch_assoc($query_abonos);
    $resumen['abonos'] = $row_abonos['total'];

    // Egresos
    $query_egresos = mysqli_query($conection, "SELECT SUM(cantidad) as total FROM egresos WHERE caja IN ($ids_str)");
    $row_egresos = mysqli_fetch_assoc($query_egresos);
    $resumen['egresos'] = $row_egresos['total'];
}

$total_sistema = $resumen['inicio'] + $resumen['efectivo'] + $resumen['abonos'] - $resumen['egresos'];

$query_conf = mysqli_query($conection, "SELECT * FROM configuracion");
$config = mysqli_fetch_assoc($query_conf);
$moneda = $config['moneda'];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php include "includes/scripts.php"; ?>
    <title>Reporte de Arqueos</title>
    <style>
        .report_container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 1000px;
            margin: auto;
        }
        .filter_box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        . arqueo_table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .arqueo_table th, .arqueo_table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .arqueo_table th {
            background-color: #f8f9fa;
        }
        .total_highlight {
            font-size: 1.3em;
            font-weight: bold;
            color: #2c3e50;
        }
        .btn_search {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        @media print {
            .filter_box, .btn_print, nav, header {
                display: none;
            }
            .report_container {
                box-shadow: none;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>
    <section id="container">
        <div class="report_container">
            <h1><i class="fas fa-history"></i> Reporte Histórico de Arqueos</h1>
            
            <form action="" method="post" class="filter_box">
                <div>
                    <label>Desde:</label><br>
                    <input type="date" name="fecha_de" value="<?= $fecha_de; ?>">
                </div>
                <div>
                    <label>Hasta:</label><br>
                    <input type="date" name="fecha_a" value="<?= $fecha_a; ?>">
                </div>
                <div style="border-left: 1px solid #ccc; padding-left: 15px;">
                    <label>O Seleccionar Mes:</label><br>
                    <input type="month" name="filtro_mes" value="<?= $filtro_mes; ?>">
                </div>
                <button type="submit" class="btn_search"><i class="fas fa-search"></i> Filtrar</button>
                <button type="button" class="btn_print" onclick="window.print()" style="background-color: #27ae60; color:white; border:none; padding:10px 20px; border-radius:5px;"><i class="fas fa-print"></i> Imprimir</button>
            </form>

            <hr>
            <h3>Resumen del Periodo: <?= date('d/m/Y', strtotime($fecha_de)); ?> al <?= date('d/m/Y', strtotime($fecha_a)); ?></h3>
            
            <table class="arqueo_table">
                <tr>
                    <td><strong>Total Aperturas (Monto Inicial)</strong></td>
                    <td class="textright"><?= $moneda; ?> <?= formatCant($resumen['inicio']); ?></td>
                </tr>
                <tr>
                    <td><strong>Ventas en Efectivo</strong></td>
                    <td class="textright"><?= $moneda; ?> <?= formatCant($resumen['efectivo']); ?></td>
                </tr>
                <tr>
                    <td><strong>Abonos Recibidos (Efectivo)</strong></td>
                    <td class="textright"><?= $moneda; ?> <?= formatCant($resumen['abonos']); ?></td>
                </tr>
                <tr>
                    <td><strong>Total Egresos</strong></td>
                    <td class="textright" style="color: #e74c3c;">- <?= $moneda; ?> <?= formatCant($resumen['egresos']); ?></td>
                </tr>
<!-- <tr class="total_highlight" style="background: #f1f1f1;">
    <td>TOTAL EFECTIVO ESTIMADO</td>
    <td class="textright"><?= $moneda; ?> <?= formatCant($total_sistema); ?></td>
</tr> -->
            </table>

            <div style="margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="background: #fdfdfd; padding: 15px; border: 1px solid #eee;">
                    <h3>Desglose Otras Ventas</h3>
                    <p>Transferencias: <?= $moneda; ?> <?= formatCant($resumen['transferencia']); ?></p>
                    <p>QR: <?= $moneda; ?> <?= formatCant($resumen['qr']); ?></p>
                    <p>Tarjeta: <?= $moneda; ?> <?= formatCant($resumen['tarjeta']); ?></p>
                    <p><strong>Subtotal No Efectivo:</strong> <?= $moneda; ?> <?= formatCant($resumen['transferencia'] + $resumen['qr'] + $resumen['tarjeta']); ?></p>
                </div>
                <div style="background: #fdfdfd; padding: 15px; border: 1px solid #eee;">
                    <h3>Ventas al Crédito</h3>
                    <p>Total Créditos Otorgados: <?= $moneda; ?> <?= formatCant($resumen['credito']); ?></p>
                    <p style="font-size: 0.9em; color: #7f8c8d;">* Estos valores no afectan el efectivo en caja.</p>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <h3>Sesiones de Caja en este Periodo</h3>
                <table class="arqueo_table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th class="textright">Ventas Efec.</th>
                            <th class="textright">Egresos</th>
                            <th class="textright">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($ids_caja)) {
                            $query_lista = mysqli_query($conection, "
                                SELECT c.*, u.nombre as usuario_nombre,
                                (SELECT IFNULL(SUM(totalventa),0) FROM venta WHERE caja = c.id AND status = 1 AND (tipo_pago_detalle IS NULL OR tipo_pago_detalle = 1)) as v_efectivo,
                                (SELECT IFNULL(SUM(cantidad),0) FROM egresos WHERE caja = c.id) as e_monto
                                FROM caja c
                                INNER JOIN usuario u ON c.usuario = u.idusuario
                                WHERE c.id IN ($ids_str)
                                ORDER BY c.fecha DESC
                            ");
                            while ($c = mysqli_fetch_assoc($query_lista)) {
                                $status = ($c['status'] == 1) ? '<span class="pagada">Abierta</span>' : '<span class="anulada">Cerrada</span>';
                                echo "<tr>
                                    <td>{$c['id']}</td>
                                    <td>" . date('d/m/Y H:i', strtotime($c['fecha'])) . "</td>
                                    <td>{$c['usuario_nombre']}</td>
                                    <td class='textright'>{$moneda} " . formatCant($c['v_efectivo']) . "</td>
                                    <td class='textright'>{$moneda} " . formatCant($c['e_monto']) . "</td>
                                    <td class='textcenter'>$status</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='textcenter'>No se encontraron movimientos en este periodo.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div>
    </section>
    <?php include "includes/footer.php"; ?>
</body>
</html>
