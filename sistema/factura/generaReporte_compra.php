<?php
// generarReporteCompras.php
session_start();

if(empty($_SESSION['active'])) {
    header('location: ../');
    exit();
}

// Incluir conexión a base de datos - Ajusta la ruta según tu estructura
if (file_exists("../../conexion.php")) {
    include "../../conexion.php";
} elseif (file_exists("../conexion.php")) {
    include "../conexion.php";
} elseif (file_exists("../includes/conexion.php")) {
    include "../includes/conexion.php";
} else {
    // Si no encuentra el archivo, crea la conexión aquí mismo
    $servidor = "localhost";
    $usuario_db = "root";
    $password = "";
    $base_datos = "ventas_bd"; // Cambia por el nombre de tu base de datos
    
    $conection = mysqli_connect($servidor, $usuario_db, $password, $base_datos);
    
    if (!$conection) {
        die("Error de conexión: " . mysqli_connect_error());
    }
    
    // Configurar charset
    mysqli_set_charset($conection, "utf8");
}

// Obtener parámetros
$busqueda = isset($_REQUEST['busqueda']) ? mysqli_escape_string($conection, $_REQUEST['busqueda']) : null;
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : null;
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : null;
$proveedor_id = isset($_GET['proveedor']) ? $_GET['proveedor'] : null;

// Variables para el template
$compras = [];
$configuracion = [];
$total_compras = 0;
$total_monto = 0;
$usuario = $_SESSION['idUser'];
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';
$rol_usuario = $_SESSION['rol'];

// Verificar conexión
if (!isset($conection) || !$conection) {
    echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;'>";
    echo "<h3>Error de conexión a la base de datos</h3>";
    echo "</div>";
    exit();
}

// Configuración de la empresa
$query_conf = mysqli_query($conection, "SELECT * FROM configuracion WHERE id = 1");
if ($query_conf && mysqli_num_rows($query_conf) > 0) {
    $configuracion = mysqli_fetch_assoc($query_conf);
}

// Construir consulta base
$where_conditions = ["f.status != 2"]; // Excluir eliminadas
$params_info = [];

// Filtros según rol
if ($rol_usuario != 1 && $rol_usuario != 2) {
    // Si no es admin, solo sus compras
    $where_conditions[] = "f.usuario = $usuario";
    $params_info[] = "Usuario: " . htmlspecialchars($nombre_usuario);
}

// Filtro por búsqueda
if (!empty($busqueda)) {
    $where_conditions[] = "(f.nocompra LIKE '%$busqueda%' OR 
                           cl.proveedor LIKE '%$busqueda%' OR 
                           u.nombre LIKE '%$busqueda%' OR 
                           f.fecha LIKE '%$busqueda%')";
    $params_info[] = "Búsqueda: " . htmlspecialchars($busqueda);
}

// Filtro por fechas
if (!empty($fecha_desde) && !empty($fecha_hasta)) {
    $where_conditions[] = "DATE(f.fecha) BETWEEN '$fecha_desde' AND '$fecha_hasta'";
    $params_info[] = "Período: " . date('d/m/Y', strtotime($fecha_desde)) . " - " . date('d/m/Y', strtotime($fecha_hasta));
} elseif (!empty($fecha_desde)) {
    $where_conditions[] = "DATE(f.fecha) >= '$fecha_desde'";
    $params_info[] = "Desde: " . date('d/m/Y', strtotime($fecha_desde));
} elseif (!empty($fecha_hasta)) {
    $where_conditions[] = "DATE(f.fecha) <= '$fecha_hasta'";
    $params_info[] = "Hasta: " . date('d/m/Y', strtotime($fecha_hasta));
}

// Filtro por proveedor
if (!empty($proveedor_id)) {
    $where_conditions[] = "f.codproveedor = $proveedor_id";
    // Obtener nombre del proveedor
    $prov_query = mysqli_query($conection, "SELECT proveedor FROM proveedor WHERE codproveedor = $proveedor_id");
    if ($prov_query && $prov_row = mysqli_fetch_assoc($prov_query)) {
        $params_info[] = "Proveedor: " . htmlspecialchars($prov_row['proveedor']);
    }
}

// Construir consulta final
$where_clause = implode(" AND ", $where_conditions);

$query = mysqli_query($conection, 
    "SELECT f.nocompra, f.fecha, f.totalcompra, f.codproveedor, f.status,
            u.nombre as vendedor, u.idusuario,
            cl.proveedor as cliente, cl.telefono, cl.direccion
     FROM compras f
     INNER JOIN usuario u ON f.usuario = u.idusuario
     INNER JOIN proveedor cl ON f.codproveedor = cl.codproveedor
     WHERE $where_clause
     ORDER BY f.fecha DESC"
);

if ($query && mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        $compras[] = [
            'nocompra' => $row['nocompra'],
            'fecha' => $row['fecha'],
            'total' => (float)$row['totalcompra'],
            'proveedor' => $row['cliente'],
            'vendedor' => $row['vendedor'],
            'status' => $row['status'],
            'telefono' => $row['telefono'] ?? '',
            'direccion' => $row['direccion'] ?? ''
        ];
        $total_monto += (float)$row['totalcompra'];
        $total_compras++;
    }
}

// Estadísticas adicionales
$compras_activas = 0;
$compras_anuladas = 0;
$monto_activo = 0;

foreach ($compras as $compra) {
    if ($compra['status'] == 1) {
        $compras_activas++;
        $monto_activo += $compra['total'];
    } else {
        $compras_anuladas++;
    }
}

// Obtener top proveedores para estadísticas
$top_proveedores = [];
$prov_query = mysqli_query($conection, 
    "SELECT cl.proveedor, COUNT(f.nocompra) as total_compras, SUM(f.totalcompra) as total_monto
     FROM compras f
     INNER JOIN proveedor cl ON f.codproveedor = cl.codproveedor
     WHERE $where_clause
     GROUP BY cl.codproveedor
     ORDER BY total_monto DESC
     LIMIT 5"
);

if ($prov_query) {
    while ($row = mysqli_fetch_assoc($prov_query)) {
        $top_proveedores[] = $row;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Compras</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f6fa;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .report-info {
            background: white;
            margin-bottom: 25px;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 6px solid #28a745;
        }
        
        .report-info h2 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 1.8em;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-item strong {
            color: #34495e;
            min-width: 120px;
        }
        
        /* Cards de métricas */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        
        .metric-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-top: 5px solid;
            transition: transform 0.2s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
        }
        
        .metric-card.total { border-top-color: #3498db; }
        .metric-card.monto { border-top-color: #2ecc71; }
        .metric-card.activas { border-top-color: #f39c12; }
        .metric-card.anuladas { border-top-color: #e74c3c; }
        
        .metric-number {
            font-size: 3em;
            font-weight: bold;
            margin: 15px 0;
            line-height: 1;
        }
        
        .metric-number.blue { color: #3498db; }
        .metric-number.green { color: #2ecc71; }
        .metric-number.orange { color: #f39c12; }
        .metric-number.red { color: #e74c3c; }
        
        .metric-title {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .metric-subtitle {
            color: #7f8c8d;
            font-size: 0.95em;
        }
        
        /* Tabla de compras */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            margin: 25px 0;
        }
        
        .table-header {
            background: #34495e;
            color: white;
            padding: 20px 25px;
            font-size: 1.3em;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .table-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #ecf0f1;
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 3px solid #bdc3c7;
            font-size: 0.95em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: middle;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Badges de estado */
        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            color: white;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        
        .status-activa { background: linear-gradient(45deg, #2ecc71, #27ae60); }
        .status-anulada { background: linear-gradient(45deg, #e74c3c, #c0392b); }
        
        /* Top proveedores */
        .top-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin: 25px 0;
        }
        
        .top-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 10px 0;
            border-left: 4px solid #3498db;
        }
        
        .top-info {
            display: flex;
            flex-direction: column;
        }
        
        .top-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .top-stats {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .top-amount {
            font-size: 1.2em;
            font-weight: bold;
            color: #27ae60;
        }
        
        /* Estado sin datos */
        .no-data {
            text-align: center;
            padding: 60px 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            color: #7f8c8d;
        }
        
        .no-data-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .no-data h3 {
            margin: 0 0 15px 0;
            color: #95a5a6;
            font-size: 1.5em;
        }
        
        /* Filtros activos */
        .filters-active {
            background: #e8f4f8;
            border: 1px solid #bee5eb;
            border-radius: 10px;
            padding: 15px 20px;
            margin: 20px 0;
        }
        
        .filter-tag {
            display: inline-block;
            background: #17a2b8;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            margin: 3px;
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            text-align: center;
            padding: 25px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            color: #7f8c8d;
        }
        
        .footer p {
            margin: 5px 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .metric-number {
                font-size: 2.5em;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .table-controls {
                justify-content: center;
            }
            
            table {
                font-size: 0.9em;
            }
            
            th, td {
                padding: 12px 8px;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .metric-number {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <?php if(isset($configuracion['nombre'])): ?>
                <h1><?php echo htmlspecialchars($configuracion['nombre']); ?></h1>
                <p><?php echo htmlspecialchars($configuracion['direccion'] ?? ''); ?></p>
                <p>📞 <?php echo htmlspecialchars($configuracion['telefono'] ?? ''); ?> | 📧 <?php echo htmlspecialchars($configuracion['email'] ?? ''); ?></p>
            <?php else: ?>
                <h1>🛒 REPORTE DE COMPRAS</h1>
                <p>Control y Seguimiento de Adquisiciones</p>
            <?php endif; ?>
        </div>

        <!-- Información del reporte -->
        <div class="report-info">
            <h2>📊 Reporte de Compras</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>📅 Fecha:</strong>
                    <span><?php echo date('d/m/Y H:i:s'); ?></span>
                </div>
                <div class="info-item">
                    <strong>👤 Generado por:</strong>
                    <span><?php echo htmlspecialchars($nombre_usuario); ?></span>
                </div>
                <div class="info-item">
                    <strong>🎯 Alcance:</strong>
                    <span><?php echo ($rol_usuario == 1 || $rol_usuario == 2) ? 'Todas las compras' : 'Mis compras'; ?></span>
                </div>
                <div class="info-item">
                    <strong>📈 Total registros:</strong>
                    <span style="color: #27ae60; font-weight: bold;"><?php echo $total_compras; ?></span>
                </div>
            </div>
        </div>

        <!-- Filtros activos -->
        <?php if (!empty($params_info)): ?>
        <div class="filters-active">
            <strong>🔍 Filtros aplicados:</strong>
            <?php foreach ($params_info as $param): ?>
                <span class="filter-tag"><?php echo $param; ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Métricas principales -->
        <div class="metrics-grid">
            <div class="metric-card total">
                <div class="metric-number blue">
                    <?php echo $total_compras; ?>
                </div>
                <div class="metric-title">Total Compras</div>
                <div class="metric-subtitle">Registros encontrados</div>
            </div>
            
            <div class="metric-card monto">
                <div class="metric-number green">
                    <?php echo formatCant($total_monto); ?>
                </div>
                <div class="metric-title">Monto Total</div>
                <div class="metric-subtitle">Valor de compras</div>
            </div>
            
            <div class="metric-card activas">
                <div class="metric-number orange">
                    <?php echo $compras_activas; ?>
                </div>
                <div class="metric-title">Compras Activas</div>
                <div class="metric-subtitle"><?php echo formatCant($monto_activo); ?></div>
            </div>
            
            <div class="metric-card anuladas">
                <div class="metric-number red">
                    <?php echo $compras_anuladas; ?>
                </div>
                <div class="metric-title">Compras Anuladas</div>
                <div class="metric-subtitle"><?php echo formatCant($total_monto - $monto_activo); ?></div>
            </div>
        </div>

        <?php if (!empty($compras)): ?>
            <!-- Tabla de compras -->
            <div class="table-container">
                <div class="table-header">
                    <span>📋 Detalle de Compras</span>
                    <div class="table-controls">
                        <span style="font-size: 0.9em; opacity: 0.9;"><?php echo $total_compras; ?> registros</span>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>No. Compra</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Responsable</th>
                            <th>Monto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($compras as $compra): ?>
                        <tr>
                            <td><strong><?php echo str_pad($compra['nocompra'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($compra['fecha'])); ?></td>
                            <td>
                                <?php echo htmlspecialchars($compra['proveedor']); ?>
                                <?php if (!empty($compra['telefono'])): ?>
                                    <br><small style="color: #7f8c8d;">📞 <?php echo $compra['telefono']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($compra['vendedor']); ?></td>
                            <td style="text-align: right; font-weight: bold; color: #27ae60;">
                                <?php echo formatCant($compra['total']); ?>
                            </td>
                            <td>
                                <?php if($compra['status'] == 1): ?>
                                    <span class="status-badge status-activa">Activa</span>
                                <?php else: ?>
                                    <span class="status-badge status-anulada">Anulada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top proveedores -->
            <?php if (!empty($top_proveedores)): ?>
            <div class="top-section">
                <div class="table-header" style="background: white; color: #2c3e50; padding: 0 0 20px 0;">
                    🏆 Top Proveedores por Monto
                </div>
                <?php foreach($top_proveedores as $index => $proveedor): ?>
                <div class="top-item">
                    <div class="top-info">
                        <div class="top-name">
                            #<?php echo $index + 1; ?> - <?php echo htmlspecialchars($proveedor['proveedor']); ?>
                        </div>
                        <div class="top-stats">
                            <?php echo $proveedor['total_compras']; ?> compras realizadas
                        </div>
                    </div>
                    <div class="top-amount">
                        <?php echo formatCant($proveedor['total_monto']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Sin datos -->
            <div class="no-data">
                <div class="no-data-icon">📭</div>
                <h3>No se encontraron compras</h3>
                <p>No hay registros que coincidan con los criterios de búsqueda.</p>
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px; text-align: left;">
                    <strong>Posibles razones:</strong><br>
                    • No hay compras registradas en el período<br>
                    • Los filtros aplicados son muy restrictivos<br>
                    • Las compras fueron eliminadas (status = 2)<br>
                    • No tienes permisos para ver otras compras
                </div>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Reporte generado el <?php echo date('d/m/Y H:i:s'); ?></strong></p>
            <p>Sistema de Control de Compras | Datos actualizados en tiempo real</p>
            <p style="font-size: 0.85em; margin-top: 10px;">
                Este documento contiene información confidencial de compras
            </p>
        </div>
    </div>
</body>
</html>