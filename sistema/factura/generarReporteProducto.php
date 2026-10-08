<?php
// generarReporteProducto.php

// Incluir conexión a base de datos - Ajusta la ruta según tu estructura
// Prueba diferentes rutas hasta encontrar el archivo correcto:
if (file_exists("../conexion.php")) {
    include "../conexion.php";
} elseif (file_exists("../../conexion.php")) {
    include "../../conexion.php";
} elseif (file_exists("../includes/conexion.php")) {
    include "../includes/conexion.php";
} elseif (file_exists("../config/conexion.php")) {
    include "../config/conexion.php";
} else {
    // Si no encuentra el archivo, crea la conexión aquí mismo
    $servidor = "localhost";
    $usuario = "root";
    $password = "";
    $base_datos = "ventas_bd"; // Cambia por el nombre de tu base de datos
    
    $conection = mysqli_connect($servidor, $usuario, $password, $base_datos);
    
    if (!$conection) {
        die("Error de conexión: " . mysqli_connect_error());
    }
    
    // Configurar charset
    mysqli_set_charset($conection, "utf8");
}

// Obtener parámetros
$fecha_de = isset($_GET['fecha_de']) ? $_GET['fecha_de'] : null;
$fecha_a = isset($_GET['fecha_a']) ? $_GET['fecha_a'] : null;
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : null;

// Variables para el template
$codigo = null;
$descripcion_producto = null;
$codproducto = null;
$categoria = null;
$stock_actual = 0;
$stock_minimo = 0;
$precio_compra = 0;
$precio_venta = 0;
$ubicacion = null;
$ultima_actualizacion = null;
$productos_stock = [];
$configuracion = [];

// Verificar conexión antes de continuar
if (!isset($conection) || !$conection) {
    echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;'>";
    echo "<h3>Error de conexión a la base de datos</h3>";
    echo "<p>No se pudo establecer conexión con la base de datos. Verifica:</p>";
    echo "<ul>";
    echo "<li>Que XAMPP/WAMP esté corriendo</li>";
    echo "<li>Que MySQL esté activo</li>";
    echo "<li>Que el nombre de la base de datos sea correcto</li>";
    echo "<li>Que las credenciales sean correctas</li>";
    echo "</ul>";
    echo "</div>";
    exit();
}

// Configuración de la empresa (opcional)
$query_config = mysqli_query($conection, "SELECT * FROM configuracion WHERE id = 1");
if ($query_config) {
    $configuracion = mysqli_fetch_assoc($query_config);
}

if (!empty($busqueda)) {
    // BÚSQUEDA DE PRODUCTO ESPECÍFICO
    
    $query_producto = mysqli_query($conection, 
        "SELECT * FROM producto WHERE codproducto = '$busqueda' OR codigo = '$busqueda'"
    );
    
    if ($query_producto && mysqli_num_rows($query_producto) > 0) {
        $producto = mysqli_fetch_assoc($query_producto);
        
        // Asignar datos del producto según tu estructura
        $codigo = $producto['codigo'];
        $descripcion_producto = $producto['descripcion'];
        $codproducto = $producto['codproducto'];
        $categoria = 'Sin categoría'; // No tienes campo categoría
        
        // Campos de tu tabla
        $stock_actual = (int)$producto['existencia'];
        $stock_minimo = 5; // Valor por defecto ya que no tienes este campo
        $precio_compra = (float)$producto['costo']; // Tu campo se llama 'costo'
        $precio_venta = (float)$producto['precio'];  // Tu campo 'precio' es el de venta
        $ubicacion = 'No especificada'; // No tienes este campo
        $ultima_actualizacion = $producto['date_add']; // Tu campo se llama 'date_add'
        
        // Obtener información del proveedor si existe
        if (!empty($producto['proveedor'])) {
            // Verificar si existe tabla proveedor
            $check_proveedor = mysqli_query($conection, "SHOW TABLES LIKE 'proveedor'");
            if ($check_proveedor && mysqli_num_rows($check_proveedor) > 0) {
                $prov_query = mysqli_query($conection, "SELECT proveedor FROM proveedor WHERE codproveedor = " . $producto['proveedor']);
                if ($prov_query && $prov_row = mysqli_fetch_assoc($prov_query)) {
                    $categoria = 'Proveedor: ' . $prov_row['proveedor'];
                }
            }
        }
    }
} else {
    // INVENTARIO GENERAL - TODOS LOS PRODUCTOS
    
    $query_productos = mysqli_query($conection, 
        "SELECT codproducto, codigo, descripcion, existencia, costo, precio, proveedor, date_add, status
         FROM producto 
         WHERE status = 1 
         ORDER BY descripcion"
    );
    
    if ($query_productos && mysqli_num_rows($query_productos) > 0) {
        while ($row = mysqli_fetch_assoc($query_productos)) {
            $productos_stock[] = [
                'codigo' => $row['codigo'],
                'descripcion' => $row['descripcion'],
                'stock_actual' => (int)$row['existencia'],
                'stock_minimo' => 5, // Valor por defecto
                'precio' => (float)$row['costo'],      // Costo de compra
                'precio_venta' => (float)$row['precio'], // Precio de venta
                'categoria' => 'Producto',
                'codproducto' => $row['codproducto'],
                'proveedor' => $row['proveedor'],
                'fecha_registro' => $row['date_add']
            ];
        }
    }
}

// Verificar si tenemos datos
$tiene_datos = (!empty($codigo) && !empty($descripcion_producto)) || (!empty($productos_stock));

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Stock</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f6fa;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
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
        
        /* Producto específico */
        .product-header {
            background: white;
            margin-bottom: 25px;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 6px solid #3498db;
        }
        
        .product-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .product-title h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.8em;
        }
        
        .product-code {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
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
        
        .metric-card.stock { border-top-color: #2ecc71; }
        .metric-card.minimo { border-top-color: #f39c12; }
        .metric-card.valor { border-top-color: #9b59b6; }
        .metric-card.estado { border-top-color: #e74c3c; }
        
        .metric-number {
            font-size: 3em;
            font-weight: bold;
            margin: 15px 0;
            line-height: 1;
        }
        
        .metric-number.green { color: #2ecc71; }
        .metric-number.orange { color: #f39c12; }
        .metric-number.purple { color: #9b59b6; }
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
        
        /* Tabla de productos */
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
            gap: 10px;
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
        
        .status-disponible { background: linear-gradient(45deg, #2ecc71, #27ae60); }
        .status-minimo { background: linear-gradient(45deg, #f39c12, #e67e22); }
        .status-agotado { background: linear-gradient(45deg, #e74c3c, #c0392b); }
        
        /* Alertas */
        .alert {
            padding: 20px 25px;
            margin: 20px 0;
            border-radius: 10px;
            border-left: 6px solid;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .alert-warning {
            background: #fff3cd;
            border-left-color: #f39c12;
            color: #856404;
        }
        
        .alert-danger {
            background: #f8d7da;
            border-left-color: #e74c3c;
            color: #721c24;
        }
        
        .alert-info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        
        .alert-icon {
            font-size: 1.5em;
        }
        
        /* Detalles del producto */
        .details-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin: 25px 0;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #3498db;
        }
        
        .detail-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .detail-value {
            color: #7f8c8d;
            font-weight: 500;
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
        
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
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
            
            .product-title {
                flex-direction: column;
                align-items: flex-start;
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
                <p><?php echo htmlspecialchars($configuracion['direccion']); ?></p>
                <p>📞 <?php echo htmlspecialchars($configuracion['telefono']); ?> | 📧 <?php echo htmlspecialchars($configuracion['email']); ?></p>
            <?php else: ?>
                <h1>📦 CONTROL DE INVENTARIO</h1>
                <p>Reporte de Stock y Disponibilidad</p>
            <?php endif; ?>
        </div>

        <!-- Información del reporte -->
        <div class="report-info">
            <h2>📊 Reporte de Stock Actual</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>📅 Fecha:</strong>
                    <span><?php echo date('d/m/Y H:i:s'); ?></span>
                </div>
                <div class="info-item">
                    <strong>👤 Generado por:</strong>
                    <span><?php echo isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Sistema'; ?></span>
                </div>
                <div class="info-item">
                    <strong>🎯 Tipo:</strong>
                    <span><?php echo !empty($codigo) ? 'Producto Específico' : 'Inventario General'; ?></span>
                </div>
                <div class="info-item">
                    <strong>⏰ Estado:</strong>
                    <span style="color: #27ae60; font-weight: bold;">Actualizado</span>
                </div>
            </div>
        </div>

        <!-- Información de debug (solo para desarrollo) -->
        <?php if (isset($_GET['debug'])): ?>
        <div class="debug-info">
            <strong>Información de Debug:</strong><br>
            Búsqueda: <?php echo htmlspecialchars($busqueda ?? 'No definida'); ?><br>
            Código encontrado: <?php echo htmlspecialchars($codigo ?? 'No'); ?><br>
            Descripción: <?php echo htmlspecialchars($descripcion_producto ?? 'No'); ?><br>
            Total productos: <?php echo count($productos_stock); ?><br>
            Tiene datos: <?php echo $tiene_datos ? 'Sí' : 'No'; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($codigo) && !empty($descripcion_producto)): ?>
            <!-- VISTA DE PRODUCTO ESPECÍFICO -->
            
            <!-- Header del producto -->
            <div class="product-header">
                <div class="product-title">
                    <h2><?php echo htmlspecialchars($descripcion_producto); ?></h2>
                    <div class="product-code"><?php echo htmlspecialchars($codigo); ?></div>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>ID Interno:</strong>
                        <span><?php echo $codproducto; ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Categoría:</strong>
                        <span><?php echo htmlspecialchars($categoria); ?></span>
                    </div>
                </div>
            </div>

            <!-- Métricas principales -->
            <div class="metrics-grid">
                <div class="metric-card stock">
                    <div class="metric-number green">
                        <?php echo number_format($stock_actual, 0); ?>
                    </div>
                    <div class="metric-title">Stock Disponible</div>
                    <div class="metric-subtitle">Unidades en inventario</div>
                </div>
                
                <div class="metric-card minimo">
                    <div class="metric-number orange">
                        <?php echo number_format($stock_minimo, 0); ?>
                    </div>
                    <div class="metric-title">Stock Mínimo</div>
                    <div class="metric-subtitle">Punto de reorden</div>
                </div>
                
                <div class="metric-card valor">
                    <div class="metric-number purple">
                        <?php echo formatCant($stock_actual * $precio_compra); ?>
                    </div>
                    <div class="metric-title">Valor Inventario</div>
                    <div class="metric-subtitle">Costo total del stock</div>
                </div>
                
                <div class="metric-card estado">
                    <div class="metric-number <?php 
                        if($stock_actual <= 0) echo 'red';
                        elseif($stock_actual <= $stock_minimo) echo 'orange';
                        else echo 'green';
                    ?>">
                        <?php 
                        if($stock_actual <= 0) echo 'AGOTADO';
                        elseif($stock_actual <= $stock_minimo) echo 'MÍNIMO';
                        else echo 'OK';
                        ?>
                    </div>
                    <div class="metric-title">Estado</div>
                    <div class="metric-subtitle">Condición actual</div>
                </div>
            </div>

            <!-- Alertas de stock -->
            <?php if($stock_actual <= 0): ?>
                <div class="alert alert-danger">
                    <div class="alert-icon">🚨</div>
                    <div>
                        <strong>¡PRODUCTO AGOTADO!</strong><br>
                        Este producto no tiene unidades disponibles. Se requiere reabastecimiento inmediato.
                    </div>
                </div>
            <?php elseif($stock_actual <= $stock_minimo): ?>
                <div class="alert alert-warning">
                    <div class="alert-icon">⚠️</div>
                    <div>
                        <strong>¡Stock en nivel mínimo!</strong><br>
                        El producto ha alcanzado el punto de reorden. Considere reabastecer pronto.
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <div class="alert-icon">✅</div>
                    <div>
                        <strong>Stock en niveles normales</strong><br>
                        El producto tiene suficiente inventario disponible.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Detalles adicionales -->
            <div class="details-section">
                <div class="table-header">
                    📋 Información Detallada
                </div>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">💰 Precio de Compra</div>
                        <div class="detail-value"><?php echo formatCant($precio_compra); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">🏷️ Precio de Venta</div>
                        <div class="detail-value"><?php echo formatCant($precio_venta); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">📈 Margen de Ganancia</div>
                        <div class="detail-value">
                            <?php 
                            if($precio_compra > 0) {
                                $margen = (($precio_venta - $precio_compra) / $precio_compra) * 100;
                                echo number_format($margen, 1) . '%';
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">📍 Ubicación</div>
                        <div class="detail-value"><?php echo htmlspecialchars($ubicacion); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">🔄 Última Actualización</div>
                        <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($ultima_actualizacion)); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">📊 Estado del Stock</div>
                        <div class="detail-value">
                            <?php 
                            if($stock_actual <= 0) {
                                echo '<span class="status-badge status-agotado">Agotado</span>';
                            } elseif($stock_actual <= $stock_minimo) {
                                echo '<span class="status-badge status-minimo">Stock Mínimo</span>';
                            } else {
                                echo '<span class="status-badge status-disponible">Disponible</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif (!empty($productos_stock)): ?>
            <!-- VISTA DE INVENTARIO GENERAL -->
            
            <div class="table-container">
                <div class="table-header">
                    📋 Inventario General - Todos los Productos (<?php echo count($productos_stock); ?> productos)
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Precio Compra</th>
                            <th>Precio Venta</th>
                            <th>Valor Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_valor = 0;
                        $total_productos = 0;
                        $productos_agotados = 0;
                        $productos_minimos = 0;
                        
                        foreach($productos_stock as $producto): 
                            $valor_producto = $producto['stock_actual'] * $producto['precio'];
                            $total_valor += $valor_producto;
                            $total_productos++;
                            
                            if ($producto['stock_actual'] <= 0) $productos_agotados++;
                            elseif ($producto['stock_actual'] <= $producto['stock_minimo']) $productos_minimos++;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($producto['codigo']); ?></strong></td>
                            <td><?php echo htmlspecialchars($producto['descripcion']); ?></td>
                            <td><?php echo number_format($producto['stock_actual'], 0); ?></td>
                            <td><?php echo number_format($producto['stock_minimo'], 0); ?></td>
                            <td><?php echo formatCant($producto['precio']); ?></td>
                            <td><?php echo formatCant($producto['precio_venta']); ?></td>
                            <td><?php echo formatCant($valor_producto); ?></td>
                            <td>
                                <?php if($producto['stock_actual'] <= 0): ?>
                                    <span class="status-badge status-agotado">Agotado</span>
                                <?php elseif($producto['stock_actual'] <= $producto['stock_minimo']): ?>
                                    <span class="status-badge status-minimo">Mínimo</span>
                                <?php else: ?>
                                    <span class="status-badge status-disponible">Disponible</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Resumen del inventario -->
            <div class="metrics-grid">
                <div class="metric-card stock">
                    <div class="metric-number green"><?php echo $total_productos; ?></div>
                    <div class="metric-title">Total Productos</div>
                    <div class="metric-subtitle">En el inventario</div>
                </div>
                
                <div class="metric-card valor">
                    <div class="metric-number purple"><?php echo formatCant($total_valor); ?></div>
                    <div class="metric-title">Valor Total</div>
                    <div class="metric-subtitle">Del inventario</div>
                </div>
                
                <div class="metric-card minimo">
                    <div class="metric-number orange"><?php echo $productos_minimos; ?></div>
                    <div class="metric-title">Stock Mínimo</div>
                    <div class="metric-subtitle">Productos en mínimo</div>
                </div>
                
                <div class="metric-card estado">
                    <div class="metric-number red"><?php echo $productos_agotados; ?></div>
                    <div class="metric-title">Agotados</div>
                    <div class="metric-subtitle">Sin stock</div>
                </div>
            </div>

            <!-- Alertas generales -->
            <?php if ($productos_agotados > 0): ?>
                <div class="alert alert-danger">
                    <div class="alert-icon">🚨</div>
                    <div>
                        <strong>¡Atención!</strong> Tienes <?php echo $productos_agotados; ?> producto(s) agotado(s) que requieren reabastecimiento urgente.
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($productos_minimos > 0): ?>
                <div class="alert alert-warning">
                    <div class="alert-icon">⚠️</div>
                    <div>
                        <strong>Stock bajo:</strong> <?php echo $productos_minimos; ?> producto(s) han alcanzado el nivel mínimo de inventario.
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Sin datos -->
            <div class="no-data">
                <div class="no-data-icon">📭</div>
                <h3>No hay datos de stock disponibles</h3>
                <p>No se encontraron productos en el sistema o no se pudo establecer conexión con la base de datos.</p>
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px; text-align: left;">
                    <strong>Posibles causas:</strong><br>
                    • No hay productos registrados en la base de datos<br>
                    • Error en la conexión con la base de datos<br>
                    • Las tablas 'producto' o 'categorias' no existen<br>
                    • El parámetro de búsqueda no coincide con ningún producto<br>
                    • Los productos están marcados como inactivos (estado = 0)
                </div>
                
                <?php if (!empty($busqueda)): ?>
                <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 8px; font-size: 0.9em;">
                    <strong>Búsqueda realizada:</strong> "<?php echo htmlspecialchars($busqueda); ?>"<br>
                    <em>Intenta verificar que el código o ID del producto sea correcto.</em>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Reporte generado el <?php echo date('d/m/Y H:i:s'); ?></strong></p>
            <p>Sistema de Control de Inventarios | Datos actualizados en tiempo real</p>
            <p style="font-size: 0.85em; margin-top: 10px;">
                Este documento contiene información confidencial del inventario
            </p>
        </div>
    </div>
</body>
</html>