<?php
session_start();
if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2) {
    header("location: ./");
}

include "../conexion.php";

$fecha_de = isset($_POST['fecha_de']) ? $_POST['fecha_de'] : date('Y-m-d');
$fecha_a = isset($_POST['fecha_a']) ? $_POST['fecha_a'] : date('Y-m-d');
$tipo_egreso = isset($_POST['tipo_egreso']) ? $_POST['tipo_egreso'] : '';

// Filtro de búsqueda
$where = "DATE(e.fecha) BETWEEN '$fecha_de' AND '$fecha_a'";
if ($tipo_egreso != '') {
    $where .= " AND e.tipo_egreso = " . (int)$tipo_egreso;
}

// Obtener totales por tipo
$query_totales = mysqli_query($conection, "
    SELECT 
        SUM(CASE WHEN tipo_egreso = 1 THEN cantidad ELSE 0 END) as total_general,
        SUM(CASE WHEN tipo_egreso = 2 THEN cantidad ELSE 0 END) as total_insumos,
        SUM(cantidad) as total_egresos
    FROM egresos e
    WHERE $where
");
$resumen = mysqli_fetch_assoc($query_totales);

// Configuración de moneda
$query_conf = mysqli_query($conection, "SELECT * FROM configuracion");
$config = mysqli_fetch_assoc($query_conf);
$moneda = $config['moneda'];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php include "includes/scripts.php"; ?>
    <title>Reporte de Egresos</title>
    <style>
        .report_container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 1100px;
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
        .report_table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .report_table th, .report_table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .report_table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .summary_cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .card {
            padding: 20px;
            border-radius: 8px;
            color: white;
            text-align: center;
        }
        .card_total { background-color: #2c3e50; }
        .card_general { background-color: #95a5a6; }
        .card_insumos { background-color: #3498db; }
        
        .card h2 { margin: 0; font-size: 1.8em; }
        .card p { margin: 5px 0 0; font-size: 0.9em; opacity: 0.9; }

        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.85em;
            color: white;
        }
        .badge_general { background-color: #95a5a6; }
        .badge_insumo { background-color: #3498db; }

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
            <h1><i class="fas fa-file-invoice-dollar"></i> Reporte de Egresos</h1>
            
            <form action="" method="post" class="filter_box">
                <div>
                    <label>Desde:</label><br>
                    <input type="date" name="fecha_de" value="<?= $fecha_de; ?>">
                </div>
                <div>
                    <label>Hasta:</label><br>
                    <input type="date" name="fecha_a" value="<?= $fecha_a; ?>">
                </div>
                <div>
                    <label>Tipo de Egreso:</label><br>
                    <select name="tipo_egreso" style="width: 150px; padding: 5px;">
                        <option value="">-- Todos --</option>
                        <option value="1" <?= ($tipo_egreso == '1') ? 'selected' : ''; ?>>Gasto General</option>
                        <option value="2" <?= ($tipo_egreso == '2') ? 'selected' : ''; ?>>Insumo</option>
                    </select>
                </div>
                <button type="submit" class="btn_new" style="background-color: #3498db; border:none; height: 35px; border-radius: 4px; color: white; cursor: pointer; padding: 0 15px;"><i class="fas fa-search"></i> Filtrar</button>
                <button type="button" class="btn_print" onclick="window.print()" style="background-color: #95a5a6; color:white; border:none; height: 35px; border-radius: 4px; cursor: pointer; padding: 0 15px;"><i class="fas fa-print"></i> Imprimir</button>
                
                <a href="factura/generaReporteEgreso.php?f_de=<?= $fecha_de ?>&f_a=<?= $fecha_a ?>&t=<?= $tipo_egreso ?>" target="_blank" class="btn_new" style="background-color: #e74c3c; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;"><i class="fas fa-file-pdf"></i> PDF</a>
                
                <a href="factura/generaReporteEgresoExcel.php?f_de=<?= $fecha_de ?>&f_a=<?= $fecha_a ?>&t=<?= $tipo_egreso ?>" class="btn_new" style="background-color: #27ae60; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;"><i class="fas fa-file-excel"></i> Excel</a>
            </form>

            <div class="summary_cards">
                <div class="card card_general">
                    <p>Gastos Generales</p>
                    <h2><?= $moneda; ?> <?= formatCant($resumen['total_general']); ?></h2>
                </div>
                <div class="card card_insumos">
                    <p>Insumos</p>
                    <h2><?= $moneda; ?> <?= formatCant($resumen['total_insumos']); ?></h2>
                </div>
                <div class="card card_total">
                    <p>TOTAL EGRESOS</p>
                    <h2><?= $moneda; ?> <?= formatCant($resumen['total_egresos']); ?></h2>
                </div>
            </div>

            <h3>Detalle de Movimientos: <?= date('d/m/Y', strtotime($fecha_de)); ?> al <?= date('d/m/Y', strtotime($fecha_a)); ?></h3>
            
            <table class="report_table">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Local</th>
                        <th class="textright">Precio Unit.</th>
                        <th class="textright">Cant.</th>
                        <th class="textright">Total</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query_lista = mysqli_query($conection, "
                        SELECT e.*, u.nombre as usuario_nombre
                        FROM egresos e
                        INNER JOIN usuario u ON e.usuario = u.idusuario
                        WHERE $where
                        ORDER BY e.fecha DESC
                    ");
                    
                    if (mysqli_num_rows($query_lista) > 0) {
                        while ($row = mysqli_fetch_assoc($query_lista)) {
                            $tipo_clase = ($row['tipo_egreso'] == 2) ? 'badge_insumo' : 'badge_general';
                            $tipo_texto = ($row['tipo_egreso'] == 2) ? 'Insumo' : 'Gasto General';
                            
                            $p_unitario = ($row['tipo_egreso'] == 2) ? $moneda . ' ' . formatCant($row['precio_unitario']) : '-';
                            $c_unidades = ($row['tipo_egreso'] == 2) ? $row['cantidad_unidades'] : '-';
                            
                            echo "<tr>
                                <td>" . date('d/m/Y H:i', strtotime($row['fecha'])) . "</td>
                                 <td><span class='badge $tipo_clase'>$tipo_texto</span></td>
                                 <td>{$row['descripcion']}</td>
                                 <td>{$row['establecimiento']}</td>
                                 <td class='textright'>$p_unitario</td>
                                <td class='textright'>$c_unidades</td>
                                <td class='textright'><strong>{$moneda} " . formatCant($row['cantidad']) . "</strong></td>
                                <td>{$row['usuario_nombre']}</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='textcenter'>No se encontraron egresos en este periodo.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php include "includes/footer.php"; ?>
</body>
</html>
