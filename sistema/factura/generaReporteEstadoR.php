<?php
// generarEstadoResultadosPDF.php
session_start();

if(empty($_SESSION['active'])) {
    header('location: ../');
    exit();
}

// Incluir conexión y librerías
include "../../conexion.php";
require_once '../pdf/vendor/autoload.php';
use Dompdf\Dompdf;

// Variables principales
$configuracion = [];
$fecha_de = null;
$fecha_a = null;
$ventas_total = 0;
$abonos_total = 0;
$compras_total = 0;
$gastos_total = 0;
$gastos_detalle = [];
$top_productos = [];
$usuario = $_SESSION['idUser'];
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';

// Configuración de la empresa
$query_conf = mysqli_query($conection, "SELECT * FROM configuracion WHERE id = 1");
if ($query_conf && mysqli_num_rows($query_conf) > 0) {
    $configuracion = mysqli_fetch_assoc($query_conf);
}

// Obtener parámetros de fecha
if (isset($_REQUEST['fecha_de']) && isset($_REQUEST['fecha_a'])) {
    $fecha_de = mysqli_escape_string($conection, $_REQUEST['fecha_de']);
    $fecha_a = mysqli_escape_string($conection, $_REQUEST['fecha_a']);
} else {
    // Si no se especifican fechas, usar el mes actual
    $fecha_de = date('Y-m-01');
    $fecha_a = date('Y-m-d');
}

// Construir rango de fechas
$f_de = $fecha_de . ' 00:00:00';
$f_a = $fecha_a . ' 23:59:59';
$where = "fecha BETWEEN '{$f_de}' AND '{$f_a}'";

// === CONSULTAS FINANCIERAS ===

// 1. VENTAS TOTALES
$query_venta = mysqli_query($conection, 
    "SELECT SUM(totalventa) as ventas FROM venta WHERE $where AND status = 1"
);
if ($query_venta && $row = mysqli_fetch_assoc($query_venta)) {
    $ventas_total = (float)($row['ventas'] ?? 0);
}

// 2. ABONOS/PAGOS PARCIALES
$query_abono = mysqli_query($conection, 
    "SELECT SUM(abono) as abonos FROM venta WHERE $where AND status = 4"
);
if ($query_abono && $row = mysqli_fetch_assoc($query_abono)) {
    $abonos_total = (float)($row['abonos'] ?? 0);
}

// 3. COSTO DE VENTAS
$query_compra = mysqli_query($conection, 
    "SELECT SUM(cantidad * costo) as compras FROM detalleventa WHERE $where AND status = 1"
);
if ($query_compra && $row = mysqli_fetch_assoc($query_compra)) {
    $compras_total = (float)($row['compras'] ?? 0);
}

// 4. GASTOS OPERATIVOS
$query_gasto = mysqli_query($conection, "SELECT * FROM egresos WHERE $where");
if ($query_gasto) {
    while ($row = mysqli_fetch_assoc($query_gasto)) {
        $gastos_total += (float)$row['cantidad'];
        $gastos_detalle[] = [
            'descripcion' => $row['descripcion'] ?? 'Gasto sin descripción',
            'cantidad' => (float)$row['cantidad'],
            'fecha' => $row['fecha']
        ];
    }
}

// 5. TOP PRODUCTOS VENDIDOS
$query_top = mysqli_query($conection, 
    "SELECT p.descripcion, SUM(dv.cantidad) as total_vendido, 
            SUM(dv.cantidad * dv.precio_venta) as ingresos
     FROM detalleventa dv
     INNER JOIN producto p ON dv.codproducto = p.codproducto
     WHERE dv.fecha BETWEEN '{$f_de}' AND '{$f_a}' AND dv.status = 1
     GROUP BY dv.codproducto
     ORDER BY ingresos DESC
     LIMIT 10"
);

if ($query_top) {
    while ($row = mysqli_fetch_assoc($query_top)) {
        $top_productos[] = $row;
    }
}

// === CÁLCULOS FINANCIEROS ===
$ingresos_brutos = $ventas_total + $abonos_total;
$utilidad_bruta = $ingresos_brutos - $compras_total;
$utilidad_neta = $utilidad_bruta - $gastos_total;
$margen_bruto = $ingresos_brutos > 0 ? ($utilidad_bruta / $ingresos_brutos) * 100 : 0;
$margen_neto = $ingresos_brutos > 0 ? ($utilidad_neta / $ingresos_brutos) * 100 : 0;

// Período de análisis
$dias_periodo = (strtotime($fecha_a) - strtotime($fecha_de)) / (60 * 60 * 24) + 1;

// === GENERAR HTML PARA PDF ===
ob_start();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Estado de Resultados</title>
	
    <style>
        @page {
            margin: 1cm;
            size: A4;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .company-info {
            color: #666;
            font-size: 10px;
            margin: 3px 0;
        }
        
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #34495e;
            margin: 15px 0 5px 0;
            text-transform: uppercase;
        }
        
        .period-info {
            background: #f8f9fa;
            padding: 12px;
            border: 1px solid #ddd;
            margin: 15px 0;
            font-size: 11px;
        }
        
        .period-info strong {
            color: #2c3e50;
        }
        
        /* Métricas en cuadros simples */
        .metrics-container {
            margin: 20px 0;
        }
        
        .metric-box {
            width: 23%;
            float: left;
            margin: 0 1% 10px 1%;
            background: #f8f9fa;
            border: 1px solid #ddd;
            text-align: center;
            padding: 10px 5px;
            box-sizing: border-box;
        }
        
        .metric-value {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .metric-value.positive { color: #27ae60; }
        .metric-value.negative { color: #e74c3c; }
        
        .metric-label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
        }
        
        .clear {
            clear: both;
            height: 0;
            overflow: hidden;
        }
        
        /* Tabla principal del estado de resultados */
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 11px;
        }
        
        .financial-table th {
            background: #34495e;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }
        
        .financial-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
        }
        
        .financial-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .amount.positive { color: #27ae60; }
        .amount.negative { color: #e74c3c; }
        
        .total-row {
            border-top: 2px solid #2c3e50;
            font-weight: bold;
            background: #ecf0f1 !important;
        }
        
        .indent {
            padding-left: 20px;
        }
        
        /* Secciones de detalles */
        .section-title {
            background: #34495e;
            color: white;
            padding: 8px 12px;
            margin: 25px 0 10px 0;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Contenedor para columnas lado a lado */
        .columns-container {
            margin-top: 20px;
        }
        
        .column-left {
            width: 48%;
            float: left;
            margin-right: 4%;
        }
        
        .column-right {
            width: 48%;
            float: right;
        }
        
        /* Tablas de detalles */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 15px;
        }
        
        .detail-table th {
            background: #95a5a6;
            color: white;
            padding: 6px;
            text-align: left;
            font-size: 10px;
        }
        
        .detail-table td {
            padding: 5px 6px;
            border-bottom: 1px solid #ddd;
        }
        
        .detail-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .no-data {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #777;
            font-size: 9px;
            clear: both;
        }
        
        .alert-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-left: 4px solid #f39c12;
            padding: 12px;
            margin: 15px 0;
            font-size: 11px;
        }
        
        .alert-title {
            font-weight: bold;
            color: #e67e22;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table style="width: 100%; border-bottom: 3px solid #2c3e50; margin-bottom: 15px;">
        <tr>
            <td style="text-align: center; padding-bottom: 15px;">
                <?php if(isset($configuracion['nombre'])): ?>
									<br>

                    <div style="font-size: 22px; font-weight: bold; color: #2c3e50; margin-bottom: 8px;">
                        <?php echo htmlspecialchars($configuracion['nombre']); ?>
                    </div>
									<br>
				<br>

                    <div style="color: #666; font-size: 10px; margin: 3px 0;">
                        <?php echo htmlspecialchars($configuracion['direccion'] ?? ''); ?>
                    </div>
									<br>

                    <div style="color: #666; font-size: 10px; margin: 3px 0;">
                        Tel: <?php echo htmlspecialchars($configuracion['telefono'] ?? ''); ?> | 
                        Email: <?php echo htmlspecialchars($configuracion['email'] ?? ''); ?>
                    </div>
									<br>

                <?php else: ?>
                    <div style="font-size: 22px; font-weight: bold; color: #2c3e50; margin-bottom: 8px;">EMPRESA</div>
                <?php endif; ?>
                
                <div style="font-size: 16px; font-weight: bold; color: #34495e; margin: 15px 0 5px 0; text-transform: uppercase;">
                    Estado de Resultados Financiero
                </div>
				<br>
				<br>

            </td>
        </tr>
    </table>

    <!-- Información del período -->
    <table style="width: 100%; background: #f8f9fa; border: 1px solid #ddd; margin: 15px 0;">
        <tr>
            <td style="padding: 12px; font-size: 11px;">
                <strong>Período:</strong> 
                <?php echo date('d/m/Y', strtotime($fecha_de)); ?> al <?php echo date('d/m/Y', strtotime($fecha_a)); ?> 
                (<?php echo $dias_periodo; ?> días) | 
                <strong>Generado por:</strong> <?php echo htmlspecialchars($nombre_usuario); ?> | 
                <strong>Fecha:</strong> <?php echo date('d/m/Y H:i:s'); ?>
            </td>
        </tr>
    </table>

    <!-- Métricas principales -->
    <table style="width: 100%; margin: 20px 0;">
        <tr>
            <td style="width: 25%; text-align: center; background: #f8f9fa; border: 1px solid #ddd; padding: 10px;">
                <div style="font-size: 14px; font-weight: bold; color: #27ae60; margin-bottom: 5px;">
 <br>
                    <?php echo formatCant($ingresos_brutos); ?>
                </div>
                <div style="font-size: 9px; color: #666; text-transform: uppercase;">Ingresos Brutos</div>
            </td>
			
            <td style="width: 25%; text-align: center; background: #f8f9fa; border: 1px solid #ddd; padding: 10px;">
                <div style="font-size: 14px; font-weight: bold; color: #2c3e50; margin-bottom: 5px;">
                <br>
    
                   
				<?php echo formatCant($compras_total); ?>
                </div>
                <div style="font-size: 9px; color: #666; text-transform: uppercase;">Costo de Ventas</div>
            </td>
            <td style="width: 25%; text-align: center; background: #f8f9fa; border: 1px solid #ddd; padding: 10px;">
                <div style="font-size: 14px; font-weight: bold; color: #e74c3c; margin-bottom: 5px;">
                 <br>
    
				<?php echo formatCant($gastos_total); ?>
                </div>
                <div style="font-size: 9px; color: #666; text-transform: uppercase;">Gastos Operativos</div>
            </td>
            <td style="width: 25%; text-align: center; background: #f8f9fa; border: 1px solid #ddd; padding: 10px;">
                <div style="font-size: 14px; font-weight: bold; color: <?php echo $utilidad_neta >= 0 ? '#27ae60' : '#e74c3c'; ?>; margin-bottom: 5px;">
                 <br>
    
				<?php echo formatCant($utilidad_neta); ?>
                </div>
                <div style="font-size: 9px; color: #666; text-transform: uppercase;">Utilidad Neta</div>
            </td>
        </tr>
    </table>
 <br>

    <!-- Estado de Resultados Detallado -->
    <table class="financial-table">
        <thead>
            <tr>
                <th style="width: 60%;">CONCEPTO</th>
                <th style="width: 25%;">IMPORTE</th>
                <th style="width: 15%;">% INGRESOS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>INGRESOS BRUTOS</strong></td>
                <td class="amount positive"><?php echo formatCant($ingresos_brutos); ?></td>
                <td class="amount">100.0%</td>
            </tr>
            <tr>
                <td class="indent">• Ventas Completadas</td>
                <td class="amount"><?php echo formatCant($ventas_total); ?></td>
                <td class="amount">
                    <?php echo $ingresos_brutos > 0 ? number_format(($ventas_total / $ingresos_brutos) * 100, 1) : 0; ?>%
                </td>
            </tr>
            <tr>
                <td class="indent">• Abonos Recibidos</td>
                <td class="amount"><?php echo formatCant($abonos_total); ?></td>
                <td class="amount">
                    <?php echo $ingresos_brutos > 0 ? number_format(($abonos_total / $ingresos_brutos) * 100, 1) : 0; ?>%
                </td>
            </tr>
            <tr><td colspan="3" style="height: 8px; border: none;"></td></tr>
            <tr>
                <td><strong>COSTO DE VENTAS</strong></td>
                <td class="amount"><?php echo formatCant($compras_total); ?></td>
                <td class="amount">
                    <?php echo $ingresos_brutos > 0 ? number_format(($compras_total / $ingresos_brutos) * 100, 1) : 0; ?>%
                </td>
            </tr>
            <tr>
                <td><strong>UTILIDAD BRUTA</strong></td>
                <td class="amount <?php echo $utilidad_bruta >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo formatCant($utilidad_bruta); ?>
                </td>
                <td class="amount">
                    <?php echo number_format($margen_bruto, 1); ?>%
                </td>
            </tr>
            <tr><td colspan="3" style="height: 8px; border: none;"></td></tr>
            <tr>
                <td><strong>GASTOS OPERATIVOS</strong></td>
                <td class="amount negative"><?php echo formatCant($gastos_total); ?></td>
                <td class="amount">
                    <?php echo $ingresos_brutos > 0 ? number_format(($gastos_total / $ingresos_brutos) * 100, 1) : 0; ?>%
                </td>
            </tr>
            <tr class="total-row">
                <td><strong>UTILIDAD NETA</strong></td>
                <td class="amount <?php echo $utilidad_neta >= 0 ? 'positive' : 'negative'; ?>">
                    <strong><?php echo formatCant($utilidad_neta); ?></strong>
                </td>
                <td class="amount">
                    <strong><?php echo number_format($margen_neto, 1); ?>%</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <?php if ($utilidad_neta < 0): ?>
    <div class="alert-box">
        <div class="alert-title">⚠️ Alerta Financiera</div>
        El período presenta una pérdida neta de <strong><?php echo formatCant(abs($utilidad_neta)); ?></strong>. 
        Se recomienda revisar la estructura de costos y gastos.
    </div>
    <?php endif; ?>

    <!-- Detalles en columnas usando tabla -->
    <table style="width: 100%; margin-top: 25px;">
        <tr>
            <td style="width: 48%; vertical-align: top; padding-right: 2%;">
                <!-- Detalle de Gastos -->
                <table style="width: 100%; background: #34495e; color: white; margin-bottom: 10px;">
                    <tr>
                        <td style="padding: 8px 12px; font-size: 12px; font-weight: bold;">DETALLE DE GASTOS</td>
                    </tr>
                </table>
                
                <?php if (!empty($gastos_detalle)): ?>
                <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                    <thead>
                        <tr>
                            <th style="background: #95a5a6; color: white; padding: 6px; text-align: left;">Descripción</th>
                            <th style="background: #95a5a6; color: white; padding: 6px; text-align: left;">Fecha</th>
                            <th style="background: #95a5a6; color: white; padding: 6px; text-align: left;">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($gastos_detalle as $i => $gasto): ?>
                        <tr style="<?php echo $i % 2 == 0 ? '' : 'background: #f9f9f9;'; ?>">
                            <td style="padding: 5px 6px; border-bottom: 1px solid #ddd;">
                                <?php echo htmlspecialchars(substr($gasto['descripcion'], 0, 25)); ?>
                            </td>
                            <td style="padding: 5px 6px; border-bottom: 1px solid #ddd;">
                                <?php echo date('d/m/Y', strtotime($gasto['fecha'])); ?>
                            </td>
                            <td style="padding: 5px 6px; border-bottom: 1px solid #ddd; text-align: right; font-weight: bold;">
                                <?php echo formatCant($gasto['cantidad']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; color: #999; font-style: italic; padding: 20px; background: #f8f9fa;">
                    No se registraron gastos
                </div>
                <?php endif; ?>
            </td>
            
            <td style="width: 48%; vertical-align: top; padding-left: 2%;">
                <!-- Top Productos -->
                <table style="width: 100%; background: #34495e; color: white; margin-bottom: 10px;">
                    <tr>
                        <td style="padding: 8px 12px; font-size: 12px; font-weight: bold;">TOP PRODUCTOS</td>
                    </tr>
                </table>
                
                <?php if (!empty($top_productos)): ?>
                <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                    <thead>
                        <tr>
                            <th style="background: #95a5a6; color: white; padding: 6px; text-align: left;">Producto</th>
                            <th style="background: #95a5a6; color: white; padding: 6px; text-align: left;">Cant.</th>
                            <th style="background: #95a5a6; color: white; padding: 6px; text-align: left;">Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach(array_slice($top_productos, 0, 10) as $index => $producto): ?>
                        <tr style="<?php echo $index % 2 == 0 ? '' : 'background: #f9f9f9;'; ?>">
                            <td style="padding: 5px 6px; border-bottom: 1px solid #ddd;">
                                <?php echo ($index + 1) . '. ' . htmlspecialchars(substr($producto['descripcion'], 0, 18)); ?>
                            </td>
                            <td style="padding: 5px 6px; border-bottom: 1px solid #ddd; text-align: right;">
                                <?php echo number_format($producto['total_vendido'], 0); ?>
                            </td>
                            <td style="padding: 5px 6px; border-bottom: 1px solid #ddd; text-align: right; font-weight: bold;">
                                <?php echo formatCant($producto['ingresos']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; color: #999; font-style: italic; padding: 20px; background: #f8f9fa;">
                    No se encontraron productos
                </div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <table style="width: 100%; margin-top: 40px; border-top: 1px solid #ddd;">
        <tr>
            <td style="padding-top: 15px; text-align: center; color: #777; font-size: 9px;">
                <p style="margin: 5px 0;">Reporte generado automáticamente el <?php echo date('d/m/Y H:i:s'); ?></p>
                 <br>

				<p style="margin: 5px 0;">Este documento es confidencial y de uso interno exclusivo</p>
            </td>
        </tr>
    </table>
</body>
</html>

<?php
$html = ob_get_clean();

// Generar PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Nombre del archivo
$filename = 'Estado_Resultados_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, array('Attachment' => 0));
exit;

?>