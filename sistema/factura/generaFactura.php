<?php
session_start();

if(empty($_SESSION['active'])) {
    header('location: ../');
    exit;
}

include "../../conexion.php";
require_once '../pdf/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Validar parámetros
if(empty($_REQUEST['cl']) || empty($_REQUEST['f'])) {
    die("No es posible generar la factura.");
}

$codCliente = intval($_REQUEST['cl']);
$noFactura = intval($_REQUEST['f']);

// Obtener configuración
$query_config = mysqli_query($conection, "SELECT * FROM configuracion");
$configuracion = array();
if($query_config && mysqli_num_rows($query_config) > 0) {
    $configuracion = mysqli_fetch_assoc($query_config);
}

// Obtener datos de la factura
$query = mysqli_query($conection, "SELECT f.noventa, DATE_FORMAT(f.fecha, '%d/%m/%Y') as fecha, 
                                         DATE_FORMAT(f.fecha,'%H:%i:%s') as hora, f.codcliente, f.status, f.descuento, 
                                         f.pago_con, f.vuelto,
                                         v.nombre as vendedor, 
                                         cl.nit, cl.nombre, cl.telefono, cl.direccion 
                                  FROM venta f 
                                  INNER JOIN usuario v ON f.usuario = v.idusuario 
                                  INNER JOIN cliente cl ON f.codcliente = cl.idcliente 
                                  WHERE f.noventa = $noFactura AND f.codcliente = $codCliente AND f.status != 10");

if(!$query || mysqli_num_rows($query) == 0) {
    die("Factura no encontrada.");
}

$factura = mysqli_fetch_assoc($query);

// Obtener productos
$query_productos = mysqli_query($conection, "SELECT p.descripcion, dt.cantidad, dt.precio_venta, 
                                                   (dt.cantidad * dt.precio_venta) as precio_total 
                                            FROM venta f 
                                            INNER JOIN detalleventa dt ON f.noventa = dt.noventa 
                                            INNER JOIN producto p ON dt.codproducto = p.codproducto 
                                            WHERE f.noventa = $noFactura");

if(!$query_productos || mysqli_num_rows($query_productos) == 0) {
    die("No se encontraron productos.");
}

$moneda = $configuracion['moneda'] ?? 'Gs';

// HTML
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 15mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        /* CABECERA */
        .header-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .header-table td {
            text-align: center;
            padding: 3px 0;
        }
        .logo_empresa {
            width: 150px;
            max-height: 80px;
            margin-bottom: 5px;
        }
        .company-name {
            font-size: 18pt;
            font-weight: bold;
        }
        .company-info {
            font-size: 9pt;
            color: #333;
        }
        .header-line {
            border-bottom: 2px solid #333;
            margin-top: 8px;
        }
        
        /* NÚMERO FACTURA */
        .invoice-number-box {
            width: 100%;
            margin: 15px 0;
        }
        .invoice-number-box td {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            padding: 10px;
            border: 2px solid #333;
            background-color: #ebebeb;
        }
        
        /* INFORMACIÓN */
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            border: 1px solid #666;
        }
        .info-table > tbody > tr > td {
            width: 50%;
            border: 1px solid #666;
            padding: 12px;
            vertical-align: top;
        }
        .section-header {
            font-weight: bold;
            font-size: 10pt;
            padding-bottom: 8px;
            margin-bottom: 10px;
            border-bottom: 1px solid #999;
        }
        .data-item {
            font-size: 9pt;
            padding: 4px 0;
        }
        
        /* PRODUCTOS */
        .products-table {
            width: 100%;
            margin-top: 15px;
            border: 1px solid #999;
        }
        .products-table th {
            background-color: #4a4a4a;
            color: #ffffff;
            padding: 8px;
            font-size: 10pt;
            border: 1px solid #4a4a4a;
            text-align: center;
            font-weight: bold;
        }
        .products-table td {
            border: 1px solid #999;
            padding: 6px 8px;
            font-size: 9pt;
        }
        .align-left { text-align: left; }
        .align-center { text-align: center; }
        .align-right { text-align: right; }
        
        /* TOTALES */
        .totals-table {
            width: 45%;
            margin-top: 15px;
            margin-left: 55%;
            border: 1px solid #999;
        }
        .totals-table td {
            padding: 7px 10px;
            font-size: 10pt;
            border: 1px solid #999;
        }
        .label-cell {
            text-align: right;
            font-weight: bold;
            background-color: #f5f5f5;
            width: 55%;
        }
        .value-cell {
            text-align: right;
            width: 45%;
        }
        .total-row td {
            background-color: #4a4a4a;
            color: #ffffff;
            font-weight: bold;
            font-size: 11pt;
        }
        
        /* FOOTER */
        .footer-text {
            width: 100%;
            margin-top: 30px;
            text-align: center;
            padding-top: 15px;
            border-top: 2px solid #333;
            font-size: 11pt;
            font-weight: bold;
        }
    </style>
</head>
<body>';

// ==================== CABECERA ====================
$html .= '<table class="header-table">';
if(!empty($configuracion['foto'])) {
    $logo_path = __DIR__ . '/img/' . $configuracion['foto'];
    if(file_exists($logo_path)) {
        $html .= '<tr><td><img src="' . $logo_path . '" class="logo_empresa"></td></tr>';
    }
}
$html .= '<tr><td class="company-name">' . htmlspecialchars($configuracion['nombre'] ?? 'MI EMPRESA') . '</td></tr>';
if(!empty($configuracion['direccion'])) {
    $html .= '<tr><td class="company-info">Dirección: ' . htmlspecialchars($configuracion['direccion']) . '</td></tr>';
}
if(!empty($configuracion['telefono'])) {
    $html .= '<tr><td class="company-info">Teléfono: ' . htmlspecialchars($configuracion['telefono']) . '</td></tr>';
}
$html .= '</table>';
$html .= '<div class="header-line"></div>';

// ==================== NÚMERO DE FACTURA ====================
$html .= '<table class="invoice-number-box">';
$html .= '<tr><td>FACTURA DE VENTA N° ' . str_pad($noFactura, 6, '0', STR_PAD_LEFT) . '</td></tr>';
$html .= '</table>';

// ==================== INFORMACIÓN ====================
$html .= '<table class="info-table">';
$html .= '<tr>';

// Cliente
$html .= '<td>';
$html .= '<table class="inner-info-table">';
$html .= '<tr class="title-row"><td colspan="2">DATOS DEL CLIENTE</td></tr>';
$html .= '<tr><td><strong>Nombre:</strong></td><td>' . htmlspecialchars($factura['nombre']) . '</td></tr>';
$html .= '<tr><td><strong>Ruc:</strong></td><td>' . htmlspecialchars($factura['nit']) . '</td></tr>';
$html .= '<tr><td><strong>Teléfono:</strong></td><td>' . htmlspecialchars($factura['telefono']) . '</td></tr>';
$html .= '<tr><td><strong>Dirección:</strong></td><td>' . htmlspecialchars($factura['direccion']) . '</td></tr>';
$html .= '</table>';
$html .= '</td>';

// CONSULTAR TIPO DE PAGO Y DETALLES
$es_transferencia = false;
$es_qr = false;
$es_tarjeta = false;
$detalles_pago = null;

// 1. Verificar Transferencia
$query_transferencia = mysqli_query($conection, "SELECT t.numero_referencia, b.nombre as banco 
                                                  FROM transferencias t 
                                                  LEFT JOIN bancos b ON t.banco_id = b.id 
                                                  WHERE t.noventa = " . $factura['noventa'] . " LIMIT 1");
if (mysqli_num_rows($query_transferencia) > 0) {
    $es_transferencia = true;
    $detalles_pago = mysqli_fetch_assoc($query_transferencia);
}

// 2. Verificar QR (si no es transferencia)
if (!$es_transferencia) {
    $query_qr = mysqli_query($conection, "SELECT numero_referencia FROM pagos_qr WHERE noventa = " . $factura['noventa'] . " LIMIT 1");
    if (mysqli_num_rows($query_qr) > 0) {
        $es_qr = true;
        $detalles_pago = mysqli_fetch_assoc($query_qr);
    }
}

// 3. Verificar Tarjeta (si no es ninguno anterior)
if (!$es_transferencia && !$es_qr) {
    $query_tarjeta = mysqli_query($conection, "SELECT numero_autorizacion, tipo_tarjeta FROM pagos_tarjeta WHERE noventa = " . $factura['noventa'] . " LIMIT 1");
    if (mysqli_num_rows($query_tarjeta) > 0) {
        $es_tarjeta = true;
        $detalles_pago = mysqli_fetch_assoc($query_tarjeta);
    }
}

// DETERMINAR TEXTO MÉTODO PAGO
$metodo_pago_texto = 'Efectivo';
if ($es_transferencia) {
    $metodo_pago_texto = 'Transferencia';
    if(!empty($detalles_pago['numero_referencia'])) $metodo_pago_texto .= ' (Ref: ' . $detalles_pago['numero_referencia'] . ')';
} elseif ($es_qr) {
    $metodo_pago_texto = 'QR';
    if(!empty($detalles_pago['numero_referencia'])) $metodo_pago_texto .= ' (Ref: ' . $detalles_pago['numero_referencia'] . ')';
} elseif ($es_tarjeta) {
    $metodo_pago_texto = 'Tarjeta';
    if(!empty($detalles_pago['numero_autorizacion'])) $metodo_pago_texto .= ' (Aut: ' . $detalles_pago['numero_autorizacion'] . ')';
}

$html .= '<td>';
$html .= '<table class="inner-info-table">';
$html .= '<tr class="title-row"><td colspan="2">INFORMACIÓN DE VENTA</td></tr>';
$html .= '<tr><td><strong>Fecha:</strong></td><td>' . $factura['fecha'] . '</td></tr>';
$html .= '<tr><td><strong>Hora:</strong></td><td>' . $factura['hora'] . '</td></tr>';
$html .= '<tr><td><strong>Vendedor:</strong></td><td>' . htmlspecialchars($factura['vendedor']) . '</td></tr>';
$html .= '<tr><td><strong>Estado:</strong></td><td>' . (($factura['status'] == 2) ? 'ANULADA' : 'ACTIVA') . '</td></tr>';
$html .= '<tr><td><strong>Pago:</strong></td><td>' . $metodo_pago_texto . '</td></tr>';

if ($metodo_pago_texto == 'Efectivo' && $factura['pago_con'] > 0) {
    $html .= '<tr><td><strong>Pagó con:</strong></td><td>' . $moneda . ' ' . formatCant($factura['pago_con']) . '</td></tr>';
    $html .= '<tr><td><strong>Su Vuelto:</strong></td><td>' . $moneda . ' ' . formatCant($factura['vuelto']) . '</td></tr>';
}
$html .= '</table>';
$html .= '</td>';

$html .= '</tr>';
$html .= '</table>';

// ==================== PRODUCTOS ====================
$html .= '<table class="products-table">';
$html .= '<thead>';
$html .= '<tr>';
$html .= '<th style="width: 45%;">DESCRIPCIÓN</th>';
$html .= '<th style="width: 15%;">CANTIDAD</th>';
$html .= '<th style="width: 20%;">PRECIO UNITARIO</th>';
$html .= '<th style="width: 20%;">IMPORTE</th>';
$html .= '</tr>';
$html .= '</thead>';
$html .= '<tbody>';

$subtotal = 0;
while($producto = mysqli_fetch_assoc($query_productos)) {
    $cantidad = floatval($producto['cantidad']);
    $precio = floatval($producto['precio_venta']);
    $total = floatval($producto['precio_total']);
    $subtotal += $total;
    
    $html .= '<tr>';
    $html .= '<td class="align-left">' . htmlspecialchars($producto['descripcion']) . '</td>';
    $html .= '<td class="align-center">' . number_format($cantidad, 0) . '</td>';
    $html .= '<td class="align-right">' . $moneda . ' ' . formatCant($precio) . '</td>';
    $html .= '<td class="align-right">' . $moneda . ' ' . formatCant($total) . '</td>';
    $html .= '</tr>';
}

$html .= '</tbody>';
$html .= '</table>';

// ==================== TOTALES ====================
$descuento = floatval($factura['descuento']);
$total_final = $subtotal - $descuento;

$html .= '<table class="totals-table">';

$html .= '<tr>';
$html .= '<td class="label-cell">SUBTOTAL:</td>';
$html .= '<td class="value-cell">' . $moneda . ' ' . formatCant($subtotal) . '</td>';
$html .= '</tr>';

if($descuento > 0) {
    $html .= '<tr>';
    $html .= '<td class="label-cell">DESCUENTO:</td>';
    $html .= '<td class="value-cell">- ' . $moneda . ' ' . formatCant($descuento) . '</td>';
    $html .= '</tr>';
}

$html .= '<tr class="total-row">';
$html .= '<td class="label-cell">TOTAL:</td>';
$html .= '<td class="value-cell">' . $moneda . ' ' . formatCant($total_final) . '</td>';
$html .= '</tr>';

if (!$es_transferencia && !$es_qr && !$es_tarjeta && $factura['status'] != 3 && $factura['pago_con'] > 0) {
    $html .= '<tr style="font-size: 8pt;">';
    $html .= '<td class="label-cell">PAGÓ CON:</td>';
    $html .= '<td class="value-cell">' . $moneda . ' ' . formatCant($factura['pago_con']) . '</td>';
    $html .= '</tr>';
    $html .= '<tr style="font-size: 8pt;">';
    $html .= '<td class="label-cell">SU VUELTO:</td>';
    $html .= '<td class="value-cell">' . $moneda . ' ' . formatCant($factura['vuelto']) . '</td>';
    $html .= '</tr>';
}

$html .= '</table>';

// ==================== FOOTER ====================
$html .= '<div class="footer-text">¡Gracias por su compra!</div>';

$html .= '</body></html>';

// ==================== GENERAR PDF ====================
try {
    $options = new Options();
    $options->set('isPhpEnabled', false);
    $options->set('isRemoteEnabled', false);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="factura_'.$noFactura.'.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    echo $dompdf->output();
    
} catch (Exception $e) {
    die("Error al generar PDF: " . $e->getMessage());
}
?>
