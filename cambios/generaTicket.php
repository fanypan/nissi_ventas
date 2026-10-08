<?php
session_start();
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

$codCliente = $_REQUEST['cl'];
$noVenta = $_REQUEST['f'];

$query = mysqli_query($conection, "SELECT f.noventa, DATE_FORMAT(f.fecha, '%d/%m/%Y') as fechaF, DATE_FORMAT(f.fecha,'%H:%i:%s') as horaF, f.codcliente, f.status,f.fecha,f.descuento,
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

// CONSULTAR SI ES TRANSFERENCIA
$query_transferencia = mysqli_query($conection, "SELECT t.numero_referencia, b.nombre as banco 
                                                  FROM transferencias t 
                                                  LEFT JOIN bancos b ON t.banco_id = b.id 
                                                  WHERE t.noventa = " . $venta['noventa'] . " LIMIT 1");
$transferencia = mysqli_fetch_assoc($query_transferencia);
$es_transferencia = $transferencia !== null;

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

// CONSTRUIR HTML
$html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body style="font-family: Arial; font-size: 9pt; margin: 5px; padding: 0;">';

// ============ CABECERA EMPRESA ============
$html .= '<table width="100%" style="border-bottom: 1px solid black; margin-bottom: 5px;">
<tr><td style="text-align: center; padding-bottom: 5px;">
<strong style="font-size: 12pt;">' . $empresa_nombre . '</strong><br>
<span style="font-size: 8pt;">' . $empresa_direccion . '<br>
RUC: ' . $empresa_ruc . '<br>
Tel: ' . $empresa_tel . '</span>
</td></tr>
</table>';

// ============ DATOS VENTA ============
$html .= '<table width="100%" style="margin: 5px 0; font-size: 8pt;">
<tr><td>
<strong>No. Venta:</strong> ' . str_pad($venta['noventa'], 11, '0', STR_PAD_LEFT) . '<br>
<strong>Fecha:</strong> ' . $venta['fechaF'] . ' ' . $venta['horaF'] . '<br>
<strong>Vendedor:</strong> ' . $venta['vendedor'] . '
</td></tr>
</table>';

$html .= '<table width="100%" style="border-top: 1px dashed black; border-bottom: 1px dashed black; margin: 5px 0;">
<tr><td style="padding: 4px 0; font-size: 8pt;">
<strong>Cliente:</strong> ' . $venta['nombre'] . '<br>
<strong>Ruc:</strong> ' . $venta['nit'] . '
</td></tr>
</table>';

// ============ MÉTODO DE PAGO ============
if ($es_transferencia) {
    $banco_nombre = !empty($transferencia['banco']) ? $transferencia['banco'] : 'N/A';
    $referencia = $transferencia['numero_referencia'];
    
    $html .= '<table width="100%" style="margin: 5px 0;">
    <tr><td style="padding: 2px 0; font-size: 7pt;">
    <strong>Método de Pago:</strong> Transferencia<br>
    <strong>Banco:</strong> ' . $banco_nombre . '<br>
    <strong>Referencia:</strong> ' . $referencia . '
    </td></tr>
    </table>';
} else {
    $html .= '<table width="100%" style="margin: 5px 0;">
    <tr><td style="padding: 2px 0; font-size: 7pt;">
    <strong>Método de Pago:</strong> Efectivo
    </td></tr>
    </table>';
}

// ============ PRODUCTOS ============
$html .= '<table width="100%" border="0" cellpadding="2" cellspacing="0" style="font-size: 8pt; margin: 5px 0;">
<tr style="border-bottom: 1px solid black;">
<th width="45%" align="left">Producto</th>
<th width="15%" align="center">Cant</th>
<th width="20%" align="right">Precio</th>
<th width="20%" align="right">Total</th>
</tr>';

$subtotal = 0;
mysqli_data_seek($query_productos, 0);
while($prod = mysqli_fetch_assoc($query_productos)) {
    $html .= '<tr style="border-bottom: 1px dashed #ccc;">
    <td><strong>' . $prod['descripcion'] . '</strong><br><span style="font-size: 7pt;">' . $prod['codigo'] . '</span></td>
    <td align="center">' . $prod['cantidad'] . '</td>
    <td align="right">' . number_format($prod['precio_venta'], 0) . '</td>
    <td align="right">' . number_format($prod['precio_total'], 0) . '</td>
    </tr>';
    $subtotal += $prod['precio_total'];
}

$html .= '</table>';

// ============ TOTALES ============
$total_final = $subtotal - $venta['descuento'];

$html .= '<table width="100%" style="margin-top: 8px; font-size: 9pt;">
<tr>
<td width="50%"></td>
<td width="25%" align="right"><strong>Subtotal:</strong></td>
<td width="25%" align="right"><strong>' . $moneda . ' ' . number_format($subtotal, 0) . '</strong></td>
</tr>';

if($venta['descuento'] > 0) {
    $html .= '<tr>
    <td></td>
    <td align="right"><strong>Descuento:</strong></td>
    <td align="right"><strong>-' . $moneda . ' ' . number_format($venta['descuento'], 0) . '</strong></td>
    </tr>';
}

$html .= '<tr style="background-color: #e0e0e0;">
<td></td>
<td align="right" style="border-top: 1px solid black; padding-top: 4px;"><strong>TOTAL:</strong></td>
<td align="right" style="border-top: 1px solid black; padding-top: 4px;"><strong>' . $moneda . ' ' . number_format($total_final, 0) . '</strong></td>
</tr>
</table>';

// ============ PIE ============
$html .= '<table width="100%" style="border-top: 1px dashed black; margin-top: 8px;">
<tr><td style="text-align: center; padding-top: 5px; font-size: 9pt;">
<strong>¡Gracias por su compra!</strong><br>
<span style="font-size: 7pt;">Revise su producto, no hay devoluciones.</span>
</td></tr>
</table>';

$html .= '</body></html>';

// GENERAR PDF
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', false);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// PAPEL TÉRMICO 80mm - ancho fijo, alto variable según productos
$numProductos = mysqli_num_rows($query_productos);
mysqli_data_seek($query_productos, 0);

// Altura base + altura por producto
$alturaBase = 310;
$alturaPorProducto = 35;
$alturaCalculada = $alturaBase + ($numProductos * $alturaPorProducto);

// Agregar altura extra para el método de pago
$alturaCalculada += 45;

// Agregar extra si hay descuento
if($venta['descuento'] > 0) {
    $alturaCalculada += 15;
}

// Limitar altura máxima y mínima
$alturaFinal = max(400, min($alturaCalculada + 30, 1200));

$dompdf->setPaper([0, 0, 226.77, $alturaFinal], 'portrait');
$dompdf->render();

// Enviar al navegador
$dompdf->stream('ticket_' . $noVenta . '.pdf', array('Attachment' => 0));
?>