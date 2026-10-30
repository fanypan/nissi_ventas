<?php
nissi_ensure_session();
if (empty($_SESSION['active'])) {
    header('location: ../');
}

include "../../conexion.php";
require_once '../pdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (empty($_REQUEST['cl']) || empty($_REQUEST['f'])) {
    echo "No es posible generar la venta.";
    exit;
} 

$codCliente = intval($_REQUEST['cl']);
$noVenta = intval($_REQUEST['f']);

$query = mysqli_query($conection, "SELECT f.noventa, DATE_FORMAT(f.fecha, '%d/%m/%Y') as fechaF, DATE_FORMAT(f.fecha,'%H:%i:%s') as horaF, f.codcliente, f.status,f.fecha,f.descuento,
                                         f.pago_con, f.vuelto,
                                         v.nombre as vendedor,
                                         cl.nit, cl.nombre, cl.telefono,cl.direccion
                                    FROM venta f
                                    INNER JOIN usuario v ON f.usuario = v.idusuario
                                    INNER JOIN cliente cl ON f.codcliente = cl.idcliente
                                    WHERE f.noventa = $noVenta AND f.codcliente = $codCliente AND f.status != 10");

if (mysqli_num_rows($query) == 0) {
    echo "No se encontró la venta especificada.";
    exit;
}

$venta = mysqli_fetch_assoc($query);

$query_productos = mysqli_query($conection, "SELECT p.descripcion,dt.cantidad,dt.precio_venta,(dt.cantidad * dt.precio_venta) as precio_total,p.codigo
                                            FROM venta f
                                            INNER JOIN detalleventa dt ON f.noventa = dt.noventa
                                            INNER JOIN producto p ON dt.codproducto = p.codproducto
                                            WHERE f.noventa = " . $venta['noventa']);

// CONSULTAR TIPO DE PAGO Y DETALLES
$es_transferencia = false;
$es_qr = false;
$es_tarjeta = false;
$detalles_pago = null;

// 1. Verificar Transferencia
$query_transferencia = mysqli_query($conection, "SELECT t.numero_referencia, b.nombre as banco 
                                                  FROM transferencias t 
                                                  LEFT JOIN bancos b ON t.banco_id = b.id 
                                                  WHERE t.noventa = " . $venta['noventa'] . " LIMIT 1");
if (mysqli_num_rows($query_transferencia) > 0) {
    $es_transferencia = true;
    $detalles_pago = mysqli_fetch_assoc($query_transferencia);
}

// 2. Verificar QR (si no es transferencia)
if (!$es_transferencia) {
    $query_qr = mysqli_query($conection, "SELECT numero_referencia FROM pagos_qr WHERE noventa = " . $venta['noventa'] . " LIMIT 1");
    if (mysqli_num_rows($query_qr) > 0) {
        $es_qr = true;
        $detalles_pago = mysqli_fetch_assoc($query_qr);
    }
}

// 3. Verificar Tarjeta (si no es ninguno anterior)
if (!$es_transferencia && !$es_qr) {
    $query_tarjeta = mysqli_query($conection, "SELECT numero_autorizacion, tipo_tarjeta FROM pagos_tarjeta WHERE noventa = " . $venta['noventa'] . " LIMIT 1");
    if (mysqli_num_rows($query_tarjeta) > 0) {
        $es_tarjeta = true;
        $detalles_pago = mysqli_fetch_assoc($query_tarjeta);
    }
}

$empresa_nombre = "FERRETERIA EL TORNILLO";
$empresa_direccion = "Av. Principal 1234";
$empresa_ruc = "80123456-7";
$empresa_tel = "0981-123-456";
$moneda = "Gs.";

$query_config = mysqli_query($conection, "SELECT * FROM configuracion");
if ($query_config && mysqli_num_rows($query_config) > 0) {
    $config = mysqli_fetch_assoc($query_config);
    if (!empty($config['nombre'])) $empresa_nombre = strtoupper($config['nombre']);
    if (!empty($config['direccion'])) $empresa_direccion = $config['direccion'];
    if (!empty($config['nit'])) $empresa_ruc = $config['nit'];
    if (!empty($config['telefono'])) $empresa_tel = $config['telefono'];
    if (!empty($config['moneda'])) $moneda = $config['moneda'];
}

if (mysqli_num_rows($query_productos) == 0) {
    echo "No se encontraron productos para esta venta.";
    exit;
}
// CONSTRUIR HTML - Versión 5cm / 8cm para ticket de venta
$html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>
<body style="font-family: Arial, sans-serif; margin: 0; padding: 0; color: #000;">';

$style = '<style>
    @page { margin: 0px; }
    * { color: #000 !important; margin: 0; padding: 0; box-sizing: border-box; }
    body { padding: 4px; width: 100%; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 2px; page-break-inside: avoid; }
    tr { page-break-inside: avoid; }
    td, th { padding: 1px 0; line-height: 1.1; font-size: 7.2pt; vertical-align: top; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .bold { font-weight: bold; }
    .line { border-top: 0.5pt solid #000; margin: 3px 0; width: 100%; }
</style>';

$html .= $style;

// ============ CONTENIDO PRINCIPAL ============
$html .= '<table class="text-center">
    <tr><td><strong style="font-size: 8.5pt; text-transform: uppercase;">' . $empresa_nombre . '</strong></td></tr>
    <tr><td style="font-size: 7pt;">' . $empresa_direccion . '</td></tr>
    <tr><td style="font-size: 7pt;">RUC: ' . $empresa_ruc . '</td></tr>
    <tr><td style="font-size: 7pt;">Tel: ' . $empresa_tel . '</td></tr>
    <tr><td><div class="line"></div></td></tr>
</table>';

// ============ DATOS VENTA ============
$html .= '<table>
    <tr><td colspan="2"><strong style="font-size: 7.5pt;">TICKET # ' . $venta['noventa'] . '</strong></td></tr>
    <tr><td style="font-size: 7pt;">FECHA:</td><td align="right" style="font-size: 7pt;">' . $venta['fechaF'] . ' ' . $venta['horaF'] . '</td></tr>
    <tr><td><div class="line"></div></td></tr>
</table>';

// ============ DATOS CLIENTE ============
$html .= '<table>
    <tr><td colspan="2"><strong style="font-size: 7.5pt;">CLIENTE:</strong></td></tr>
    <tr><td colspan="2" style="font-size: 7pt;">' . $venta['nombre'] . '</td></tr>
    <tr><td colspan="2" style="font-size: 7pt;">RUC/CI: ' . $venta['nit'] . '</td></tr>
    <tr><td colspan="2"><div class="line"></div></td></tr>
</table>';

// ============ PRODUCTOS ============
$html .= '<table>
    <thead>
        <tr style="border-bottom: 0.5pt solid #000;">
            <th align="left" width="55%">DESC</th>
            <th align="center" width="10%">CT</th>
            <th align="right" width="35%">TOT</th>
        </tr>
    </thead>
    <tbody>';

$subtotal = 0;
mysqli_data_seek($query_productos, 0);
while($prod = mysqli_fetch_assoc($query_productos)) {
    $html .= '<tr>
        <td style="font-size: 7pt; padding-top: 1px;">' . $prod['descripcion'] . '</td>
        <td align="center" style="font-size: 7pt; padding-top: 1px;">' . $prod['cantidad'] . '</td>
        <td align="right" style="font-size: 7pt; padding-top: 1px;"><b>' . formatCant($prod['precio_total']) . '</b></td>
    </tr>';
    $subtotal += $prod['precio_total'];
}
$html .= '</tbody></table>';

// ============ TOTALES ============
$total_final = $subtotal - $venta['descuento'];

$html .= '<table style="margin-top: 3px; border-top: 0.5pt solid #000; padding-top: 1px;">
    <tr><td align="right" style="font-size: 7pt;">SUBTOT:</td><td align="right" width="45%" style="font-size: 7pt;"><b>' . formatCant($subtotal) . '</b></td></tr>';

if($venta['descuento'] > 0) {
    $html .= '<tr><td align="right" style="font-size: 7pt;">DSCTO:</td><td align="right" style="font-size: 7pt;"><b>-' . formatCant($venta['descuento']) . '</b></td></tr>';
}

$html .= '<tr><td align="right" style="font-size: 9pt; padding-top: 2px;"><strong>TOTAL:</strong></td><td align="right" style="font-size: 9pt; padding-top: 2px;"><strong>' . formatCant($total_final) . '</strong></td></tr>';
$html .= '</table>';

// ============ PIE ============
$html .= '<table class="text-center" style="margin-top: 5px; margin-bottom: 10px;">
    <tr><td><div class="line"></div></td></tr>
    <tr><td style="padding-top: 3px;"><strong style="font-size: 8.5pt;">¡GRACIAS POR SU COMPRA!</strong></td></tr>
    <tr><td style="height: 35px;">&nbsp;</td></tr>
</table>';

$html .= '</body></html>';

// GENERAR PDF
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', false); 
$options->set('defaultFont', 'Arial');
$options->set('dpi', 96); 
$options->set('isFontSubsettingEnabled', true);

$dompdf = new Dompdf($options);
error_reporting(E_ERROR | E_PARSE); 
$dompdf->loadHtml($html);

// PAPEL TÉRMICO 50mm - CÁLCULO DINÁMICO DE ALTURA
$alturaBase = 220; 
$alturaProductos = 0;

mysqli_data_seek($query_productos, 0);
while($prod_calc = mysqli_fetch_assoc($query_productos)) {
    $len = mb_strlen(trim($prod_calc['descripcion']));
    // Con columna de 55% en 115pt caben aprox. 12-14 caracteres por línea
    $lineas = max(1, ceil($len / 13));
    $alturaProductos += 14 + ($lineas * 10);
}

$alturaCalculada = $alturaBase + $alturaProductos;

if(!empty($venta['descuento']) && $venta['descuento'] > 0) $alturaCalculada += 18;
if(!empty($venta['pago_con']) && $venta['pago_con'] > 0) $alturaCalculada += 25;

// Margen de seguridad extra para asegurar que NUNCA desborde a una 2da página
$alturaCalculada += 45;

$alturaFinal = max(290, $alturaCalculada);

$dompdf->setPaper([0, 0, 115, $alturaFinal], 'portrait');
$dompdf->render();

// Limpiar el búfer antes de enviar el PDF
if (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="ticket_'.$noVenta.'.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

$dompdf->stream('ticket_' . $noVenta . '.pdf', array('Attachment' => 0));
?>