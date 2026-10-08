<?php
session_start();
if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2) {
    header("location: ./");
}

include "../conexion.php";

$fecha_de = isset($_REQUEST['fecha_de']) ? $_REQUEST['fecha_de'] : date('Y-m-01'); // Primer dia del mes por defecto
$fecha_a = isset($_REQUEST['fecha_a']) ? $_REQUEST['fecha_a'] : date('Y-m-d');

$where_ventas = "DATE(v.fecha) BETWEEN '$fecha_de' AND '$fecha_a' AND v.status != 10";
$where_egresos = "DATE(e.fecha) BETWEEN '$fecha_de' AND '$fecha_a'";

// Configuración de moneda
$query_conf = mysqli_query($conection, "SELECT * FROM configuracion");
$config = mysqli_fetch_assoc($query_conf);
$moneda = $config['moneda'];

// 1. Ingresos y Costo de Ventas
$query_ventas = mysqli_query($conection, "
    SELECT 
        IFNULL(SUM(dv.cantidad * dv.precio_venta), 0) as total_ingresos,
        IFNULL(SUM(dv.cantidad * dv.costo), 0) as total_costos
    FROM detalleventa dv
    INNER JOIN venta v ON v.noventa = dv.noventa
    WHERE $where_ventas
");
$datos_ventas = mysqli_fetch_assoc($query_ventas);
$ingresos = $datos_ventas['total_ingresos'];
$costos_ventas = $datos_ventas['total_costos'];
$ganancia_bruta = $ingresos - $costos_ventas;

// 2. Egresos Operativos
$query_egresos = mysqli_query($conection, "
    SELECT 
        IFNULL(SUM(e.cantidad), 0) as total_egresos
    FROM egresos e
    WHERE $where_egresos
");
$datos_egresos = mysqli_fetch_assoc($query_egresos);
$egresos = $datos_egresos['total_egresos'];

// 3. Ganancia Neta
$ganancia_neta = $ganancia_bruta - $egresos;

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php include "includes/scripts.php"; ?>
    <title>Reporte Consolidado (Estado de Resultados)</title>
    <!-- Incluir Chart.js para el gráfico -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report_container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            max-width: 1100px;
            margin: 20px auto;
        }
        .report_header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }
        .report_header h2 {
            color: #2c3e50;
            font-size: 24px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filter_form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .filter_form .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter_form label {
            font-size: 13px;
            font-weight: bold;
            color: #555;
        }
        .filter_form input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .filter_form button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }
        .filter_form button:hover {
            background-color: #2980b9;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card-stat {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #ecf0f1;
            position: relative;
            overflow: hidden;
        }
        .card-stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }
        .card-ingresos::before { background: #2ecc71; }
        .card-costos::before { background: #e67e22; }
        .card-bruta::before { background: #3498db; }
        .card-egresos::before { background: #e74c3c; }
        .card-neta::before { background: <?php echo $ganancia_neta >= 0 ? '#27ae60' : '#c0392b'; ?>; }
        
        .card-stat h3 {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 0;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .card-stat .value {
            font-size: 26px;
            font-weight: bold;
            color: #2c3e50;
        }
        .card-neta .value {
            color: <?php echo $ganancia_neta >= 0 ? '#27ae60' : '#c0392b'; ?>;
        }
        .card-stat .icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 40px;
            opacity: 0.1;
        }

        .report-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        @media (max-width: 768px) {
            .report-content {
                grid-template-columns: 1fr;
            }
        }
        
        .table-resumen {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #ecf0f1;
        }
        .table-resumen th, .table-resumen td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        .table-resumen th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: bold;
        }
        .table-resumen .text-right {
            text-align: right;
        }
        .table-resumen .row-total {
            background-color: #f1f8ff;
            font-weight: bold;
        }
        .table-resumen .row-neta {
            background-color: <?php echo $ganancia_neta >= 0 ? '#eafaf1' : '#fdedec'; ?>;
            font-weight: bold;
            font-size: 16px;
            color: <?php echo $ganancia_neta >= 0 ? '#27ae60' : '#c0392b'; ?>;
        }
        
        .chart-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ecf0f1;
            height: 300px;
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>
    <section id="container">
        
        <div class="report_container">
            <div class="report_header">
                <h2><i class="fas fa-chart-line"></i> Estado de Resultados (Ganancias y Egresos)</h2>
            </div>
            
            <form action="" method="POST" class="filter_form">
                <div class="form-group">
                    <label>Desde:</label>
                    <input type="date" name="fecha_de" id="fecha_de" value="<?php echo $fecha_de; ?>" required>
                </div>
                <div class="form-group">
                    <label>Hasta:</label>
                    <input type="date" name="fecha_a" id="fecha_a" value="<?php echo $fecha_a; ?>" required>
                </div>
                <div class="form-group" style="justify-content: flex-end;">
                    <button type="submit"><i class="fas fa-search"></i> Filtrar</button>
                </div>
            </form>
            
            <!-- TARJETAS PRINCIPALES -->
            <div class="dashboard-grid">
                <div class="card-stat card-ingresos">
                    <h3>Ingresos Totales (Ventas)</h3>
                    <div class="value"><?php echo $moneda . ' ' . number_format($ingresos, 2); ?></div>
                    <i class="fas fa-cash-register icon"></i>
                </div>
                <div class="card-stat card-costos">
                    <h3>Costo de Ventas</h3>
                    <div class="value"><?php echo $moneda . ' ' . number_format($costos_ventas, 2); ?></div>
                    <i class="fas fa-box-open icon"></i>
                </div>
                <div class="card-stat card-egresos">
                    <h3>Egresos Operativos</h3>
                    <div class="value"><?php echo $moneda . ' ' . number_format($egresos, 2); ?></div>
                    <i class="fas fa-hand-holding-usd icon"></i>
                </div>
                <div class="card-stat card-neta">
                    <h3>Ganancia Neta</h3>
                    <div class="value"><?php echo $moneda . ' ' . number_format($ganancia_neta, 2); ?></div>
                    <i class="fas <?php echo $ganancia_neta >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?> icon"></i>
                </div>
            </div>
            
            <div class="report-content">
                <!-- TABLA DE DESGLOSE -->
                <div>
                    <h3 style="color: #2c3e50; margin-bottom: 15px;"><i class="fas fa-list"></i> Desglose Detallado</h3>
                    <table class="table-resumen">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th class="text-right">Monto (<?php echo $moneda; ?>)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>(+) Ventas Totales</td>
                                <td class="text-right"><?php echo number_format($ingresos, 2); ?></td>
                            </tr>
                            <tr>
                                <td>(-) Costo de Productos Vendidos</td>
                                <td class="text-right" style="color: #e67e22;"><?php echo number_format($costos_ventas, 2); ?></td>
                            </tr>
                            <tr class="row-total">
                                <td>(=) GANANCIA BRUTA</td>
                                <td class="text-right text-primary" style="color: #3498db;"><?php echo number_format($ganancia_bruta, 2); ?></td>
                            </tr>
                            <tr>
                                <td>(-) Egresos y Gastos</td>
                                <td class="text-right" style="color: #e74c3c;"><?php echo number_format($egresos, 2); ?></td>
                            </tr>
                            <tr class="row-neta">
                                <td>(=) GANANCIA NETA FINAL</td>
                                <td class="text-right"><?php echo number_format($ganancia_neta, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <p style="font-size: 12px; color: #7f8c8d; margin-top: 10px;">
                        * El "Costo de Productos Vendidos" representa el costo de adquisición únicamente de la mercadería que fue vendida en este periodo, no el total de compras al proveedor.
                    </p>
                </div>
                
                <!-- GRÁFICO -->
                <div class="chart-container">
                    <canvas id="reportChart"></canvas>
                </div>
            </div>
            
        </div>
    </section>
    
    <script>
        // Inicializar el gráfico con Chart.js
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('reportChart').getContext('2d');
            var reportChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Ingresos', 'Costos de Ventas', 'Egresos', 'Ganancia Neta'],
                    datasets: [{
                        label: 'Monto en <?php echo $moneda; ?>',
                        data: [
                            <?php echo $ingresos; ?>, 
                            <?php echo $costos_ventas; ?>, 
                            <?php echo $egresos; ?>, 
                            <?php echo $ganancia_neta; ?>
                        ],
                        backgroundColor: [
                            'rgba(46, 204, 113, 0.7)',  // Verde para ingresos
                            'rgba(230, 126, 34, 0.7)',  // Naranja para costos
                            'rgba(231, 76, 60, 0.7)',   // Rojo para egresos
                            '<?php echo $ganancia_neta >= 0 ? "rgba(39, 174, 96, 0.9)" : "rgba(192, 57, 43, 0.9)"; ?>' // Verde/Rojo para neta
                        ],
                        borderColor: [
                            'rgb(46, 204, 113)',
                            'rgb(230, 126, 34)',
                            'rgb(231, 76, 60)',
                            '<?php echo $ganancia_neta >= 0 ? "rgb(39, 174, 96)" : "rgb(192, 57, 43)"; ?>'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Resumen Visual del Periodo',
                            font: {
                                size: 16
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
