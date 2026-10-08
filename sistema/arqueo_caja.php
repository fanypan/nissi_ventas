<?php
session_start();
if ($_SESSION['rol'] != 1) {
    header("location: ./");
}

include "../conexion.php";

// Obtener datos iniciales de la caja abierta
$query_caja = mysqli_query($conection, "SELECT * FROM caja WHERE status = 1");
$caja_abierta = mysqli_fetch_assoc($query_caja);

if (!$caja_abierta) {
    header("location: lista_caja.php");
    exit;
}

$id_caja = $caja_abierta['id'];

// Obtener totales por método de pago usando un procedimiento almacenado o consultas directas
// Reutilizamos parte de la lógica de dataDashboard pero expandida
$query_resumen = mysqli_query($conection, "
    SELECT 
        (SELECT IFNULL(SUM(totalventa),0) FROM venta WHERE caja = $id_caja AND status = 1 AND (tipo_pago_detalle IS NULL OR tipo_pago_detalle = 1)) as efectivo,
        (SELECT IFNULL(SUM(totalventa),0) FROM venta WHERE caja = $id_caja AND status = 1 AND tipo_pago_detalle = 2) as transferencia,
        (SELECT IFNULL(SUM(totalventa),0) FROM venta WHERE caja = $id_caja AND status = 1 AND tipo_pago_detalle = 4) as qr,
        (SELECT IFNULL(SUM(totalventa),0) FROM venta WHERE caja = $id_caja AND status = 1 AND tipo_pago_detalle = 5) as tarjeta,
        (SELECT IFNULL(SUM(totalventa),0) FROM venta WHERE caja = $id_caja AND status = 3) as credito,
        (SELECT IFNULL(SUM(cantidad),0) FROM detalle_recibo WHERE caja = $id_caja) as abonos,
        (SELECT IFNULL(SUM(cantidad),0) FROM egresos WHERE caja = $id_caja) as egresos
");

$resumen = mysqli_fetch_assoc($query_resumen);

// Sumamos los abonos al efectivo? Normalmente sí, si el abono fue en efectivo.
// Para este sistema simplificado, asumimos que ventas y abonos son efectivo a menos que se indique lo contrario.
$total_sistema = $caja_abierta['inicio'] + $resumen['efectivo'] + $resumen['abonos'] - $resumen['egresos'];

$query_conf = mysqli_query($conection, "SELECT * FROM configuracion");
$config = mysqli_fetch_assoc($query_conf);
$moneda = $config['moneda'];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php include "includes/scripts.php"; ?>
    <title>Arqueo de Caja</title>
    <style>
        .arqueo_container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: auto;
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
            font-size: 1.5em;
            font-weight: bold;
            color: #2c3e50;
        }
        .input_arqueo {
            width: 100%;
            padding: 10px;
            font-size: 1.2em;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .diff_positive { color: #27ae60; }
        .diff_negative { color: #e74c3c; }
        .btn_print {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>
    <section id="container">
        <div class="arqueo_container">
            <h1><i class="fas fa-cash-register"></i> Arqueo de Caja</h1>
            <p><strong>Fecha de apertura:</strong> <?= $caja_abierta['fecha']; ?></p>
            <p><strong>Administrador:</strong> <?= $_SESSION['nombre']; ?></p>
            <hr>

            <table class="arqueo_table">
                <tr>
                    <td>Monto Inicial</td>
                    <td class="textright"><?= $moneda; ?> <?= formatCant($caja_abierta['inicio']); ?></td>
                </tr>
                <tr>
                    <td>Ventas en Efectivo</td>
                    <td class="textright"><?= $moneda; ?> <?= formatCant($resumen['efectivo']); ?></td>
                </tr>
                <tr>
                    <td>Abonos Recibidos</td>
                    <td class="textright"><?= $moneda; ?> <?= formatCant($resumen['abonos']); ?></td>
                </tr>
                <tr>
                    <td>Total Egresos</td>
                    <td class="textright">- <?= $moneda; ?> <?= formatCant($resumen['egresos']); ?></td>
                </tr>
                <tr class="total_highlight">
                    <td>Total Estimado en Caja</td>
                    <td class="textright"><?= $moneda; ?> <span id="total_sistema"><?= formatCant($total_sistema); ?></span></td>
                </tr>
            </table>

            <div style="margin-top: 30px; background: #fdfdfd; padding: 15px; border: 1px solid #eee;">
                <h3>Otras Ventas (No Efectivo)</h3>
                <p>Transferencias: <?= $moneda; ?> <?= formatCant($resumen['transferencia']); ?></p>
                <p>QR: <?= $moneda; ?> <?= formatCant($resumen['qr']); ?></p>
                <p>Tarjeta: <?= $moneda; ?> <?= formatCant($resumen['tarjeta']); ?></p>
                <p>Créditos Otorgados: <?= $moneda; ?> <?= formatCant($resumen['credito']); ?></p>
            </div>

            <div style="margin-top: 30px;">
                <h3><i class="fas fa-coins"></i> Contador de Efectivo</h3>
                <table class="arqueo_table" id="tabla_denominaciones">
                    <thead>
                        <tr>
                            <th>Denominación</th>
                            <th>Cantidad</th>
                            <th class="textright">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $denominaciones = [
                            ['valor' => 100000, 'label' => '100.000 Gs'],
                            ['valor' => 50000, 'label' => '50.000 Gs'],
                            ['valor' => 20000, 'label' => '20.000 Gs'],
                            ['valor' => 10000, 'label' => '10.000 Gs'],
                            ['valor' => 5000, 'label' => '5.000 Gs'],
                            ['valor' => 2000, 'label' => '2.000 Gs'],
                            ['valor' => 1000, 'label' => '1.000 Gs (Moneda)'],
                            ['valor' => 500, 'label' => '500 Gs'],
                            ['valor' => 100, 'label' => '100 Gs'],
                            ['valor' => 50, 'label' => '50 Gs']
                        ];
                        foreach ($denominaciones as $d):
                        ?>
                        <tr>
                            <td><?= $d['label']; ?></td>
                            <td>
                                <input type="number" min="0" class="cant_denom" data-valor="<?= $d['valor']; ?>" onkeyup="sumarEfectivo()" onchange="sumarEfectivo()" style="width: 80px; padding: 5px;">
                            </td>
                            <td class="textright lb_subtotal">0.00</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 30px;">
                <label><strong>Efectivo Físico Total:</strong></label>
                <input type="number" step="1" id="efectivo_fisico" class="input_arqueo" placeholder="0" onkeyup="calcularDiferencia()" readonly>
            </div>

            <div style="margin-top: 20px;">
                <p class="total_highlight">Diferencia: <?= $moneda; ?> <span id="diferencia">0</span></p>
                <p id="mensaje_diferencia"></p>
            </div>

            <button class="btn_print" onclick="window.print()"><i class="fas fa-print"></i> Imprimir Arqueo</button>
            <a href="lista_caja.php" class="btn_new" style="background-color: #95a5a6; margin-left: 10px;">Volver</a>
        </div>
    </section>

    <script>
        function sumarEfectivo() {
            let total = 0;
            let inputs = document.querySelectorAll('.cant_denom');
            
            inputs.forEach(input => {
                let valor = parseFloat(input.getAttribute('data-valor'));
                let cant = parseInt(input.value) || 0;
                let subtotal = valor * cant;
                total += subtotal;
                
                // Actualizar subtotal en la fila
                input.closest('tr').querySelector('.lb_subtotal').innerHTML = subtotal.toLocaleString('es-PY', { minimumFractionDigits: 0 });
            });
            
            document.getElementById('efectivo_fisico').value = total;
            calcularDiferencia();
        }

        function calcularDiferencia() {
            let totalSistema = parseFloat(<?= $total_sistema; ?>);
            let efectivoFisico = parseFloat(document.getElementById('efectivo_fisico').value) || 0;
            let diferencia = efectivoFisico - totalSistema;

            let spanDiff = document.getElementById('diferencia');
            let msgDiff = document.getElementById('mensaje_diferencia');

            spanDiff.innerHTML = diferencia.toLocaleString('es-PY', { minimumFractionDigits: 0 });

            if (diferencia === 0) {
                spanDiff.className = '';
                msgDiff.innerHTML = "Caja cuadrada.";
                msgDiff.style.color = "#27ae60";
            } else if (diferencia > 0) {
                spanDiff.className = 'diff_positive';
                msgDiff.innerHTML = "Sobrante de caja.";
                msgDiff.style.color = "#27ae60";
            } else {
                spanDiff.className = 'diff_negative';
                msgDiff.innerHTML = "Faltante de caja.";
                msgDiff.style.color = "#e74c3c";
            }
        }
    </script>
    <?php include "includes/footer.php"; ?>
</body>
</html>
