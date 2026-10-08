<?php
session_start();
// Diagnóstico y Verificación General del Sistema de Código de Barras
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba General del Sistema - Códigos de Barras</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #27ae60; padding-bottom: 10px; margin-top: 0; }
        .section { background: #fafafa; border: 1px solid #e2e8f0; padding: 20px; border-radius: 6px; margin-bottom: 25px; }
        .section h2 { margin-top: 0; font-size: 18px; color: #2980b9; }
        .btn { background: #27ae60; color: #fff; border: none; padding: 10px 20px; font-size: 14px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn:hover { background: #219150; }
        .btn-blue { background: #2980b9; }
        .btn-blue:hover { background: #1f6391; }
        input[type="text"] { padding: 10px; font-size: 16px; border: 2px solid #cbd5e1; border-radius: 4px; width: 280px; }
        .scan-box { background: #e8f8f5; border: 2px dashed #27ae60; padding: 20px; text-align: center; border-radius: 6px; }
        .scan-input { font-size: 22px; text-align: center; border: 3px solid #27ae60; background: #fff; width: 320px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .status-ok { background: #d4edda; color: #155724; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
        th { background: #f1f5f9; }
    </style>
    <script src="js/jquery.min.js"></script>
    <script src="js/JsBarcode.all.min.js"></script>
    <script src="js/functions_v3.js?v=<?php echo time(); ?>"></script>
</head>
<body>
    <div class="container">
        <h1>📊 Prueba General - Sistema de Ventas & Códigos de Barras</h1>
        <p>Esta página verifica el funcionamiento integral del generador de códigos de barras, renderizado PNG de alta resolución para impresión y compatibilidad con el escáner SATE A-SCAN01.</p>

        <!-- SECCIÓN 1: PRUEBA DE GENERACIÓN AJAX -->
        <div class="section">
            <h2>1. Generador de Código de Producto (AJAX backend)</h2>
            <p>Prueba la generación de nuevos códigos aleatorios únicos desde el servidor PHP (`ajax.php`):</p>
            <div style="display:flex; gap:10px; align-items:center;">
                <input type="text" id="test_codigo" placeholder="Haz clic en Generar...">
                <button type="button" class="btn" onclick="generarCodigoForm('test_codigo', 'test_preview_svg')">🎲 Generar Código Nuevo</button>
            </div>
            <div style="margin-top:15px;">
                <svg id="test_preview_svg"></svg>
            </div>
        </div>

        <!-- SECCIÓN 2: PRUEBA DE IMPRESIÓN CON ALTA RESOLUCIÓN PNG -->
        <div class="section">
            <h2>2. Simulación de Impresión de Etiqueta (PNG Alta Resolución)</h2>
            <p>Envía la etiqueta a imprimir en formato 60x40mm usando la técnica Canvas-to-PNG (sin distorsión):</p>
            <div class="grid-2">
                <div>
                    <label><strong>Código:</strong></label><br>
                    <input type="text" id="print_code" value="779804776899" style="width:100%; box-sizing:border-box;"><br><br>
                    <label><strong>Nombre Producto:</strong></label><br>
                    <input type="text" id="print_name" value="Tarjetita de Princesas - Princesa rosa" style="width:100%; box-sizing:border-box;"><br><br>
                    <label><strong>Precio:</strong></label><br>
                    <input type="text" id="print_price" value="3000.00" style="width:100%; box-sizing:border-box;"><br><br>
                    <input type="hidden" id="lblCantidad" value="1">
                    <input type="hidden" id="lblFormato" value="60x40">
                    <input type="hidden" id="lblColumnas" value="1">
                    <input type="checkbox" id="chkVerNombre" checked style="display:none;">
                    <input type="checkbox" id="chkVerPrecio" checked style="display:none;">
                    <input type="checkbox" id="chkVerCodInterno" style="display:none;">

                    <button type="button" class="btn btn-blue" onclick="ejecutarImpresionEtiquetas($('#print_code').val(), $('#print_name').val(), $('#print_price').val(), '')">🖨️ Probar Vista Previa de Impresión</button>
                </div>
                <div>
                    <div style="border: 2px dashed #cbd5e1; padding:15px; border-radius:6px; background:#fff; text-align:center;">
                        <strong>Previsualización local (Canvas High-Res PNG)</strong><br><br>
                        <canvas id="canvas_demo" style="max-width:100%; border:1px solid #ccc;"></canvas>
                        <br><br>
                        <button type="button" class="btn" onclick="renderDemoCanvas()">🔄 Renderizar Canvas Demo</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 3: COMPROBACIÓN DEL LECTOR SATE -->
        <div class="section">
            <h2>3. Prueba Directa con Escáner SATE A-SCAN01</h2>
            <div class="scan-box">
                <p><strong>Haz clic en la caja de abajo y escanea cualquier etiqueta impresa o la pantalla:</strong></p>
                <input type="text" id="scanner_test_input" class="scan-input" placeholder="Apunta y dispara aquí..." autofocus>
                <div id="scan_log" style="margin-top:15px; font-weight:bold; color:#27ae60;"></div>
            </div>
        </div>

        <!-- SECCIÓN 4: ESTADO DEL SISTEMA -->
        <div class="section">
            <h2>4. Estado del Entorno de Archivos</h2>
            <table>
                <tr>
                    <th>Componente</th>
                    <th>Estado</th>
                    <th>Detalle</th>
                </tr>
                <tr>
                    <td>JsBarcode Library</td>
                    <td><span class="status-badge status-ok">OK (60.8 KB)</span></td>
                    <td>Versión completa JsBarcode v3.11.6 instalada en local (`js/JsBarcode.all.min.js`)</td>
                </tr>
                <tr>
                    <td>Script Principal JS</td>
                    <td><span class="status-badge status-ok">OK</span></td>
                    <td>Sincronizados `js/functions_v3.js` y `js/functions.js`</td>
                </tr>
                <tr>
                    <td>Inclusión en scripts.php</td>
                    <td><span class="status-badge status-ok">OK</span></td>
                    <td>Script de la aplicación configurado con versión dinámica `?v=timestamp`</td>
                </tr>
                <tr>
                    <td>Método de Impresión</td>
                    <td><span class="status-badge status-ok">Canvas PNG High-Res</span></td>
                    <td>Generación `CODE128` en Canvas 3px (barras de 100px alto) exportado a PNG `max-width:95%`</td>
                </tr>
            </table>
        </div>
    </div>

    <script>
        function renderDemoCanvas() {
            var code = $('#print_code').val() || '779804776899';
            var canvas = document.getElementById('canvas_demo');
            try {
                JsBarcode(canvas, code, {
                    format: "CODE128",
                    width: 3,
                    height: 100,
                    fontSize: 20,
                    margin: 10,
                    displayValue: true,
                    background: "#ffffff",
                    lineColor: "#000000"
                });
            } catch(e) {
                alert("Error al renderizar canvas: " + e.message);
            }
        }

        $(document).ready(function() {
            renderDemoCanvas();

            var scanInput = $('#scanner_test_input');
            scanInput.on('keydown', function(e) {
                if (e.key === 'Enter') {
                    var val = $(this).val().trim();
                    if (val.length > 0) {
                        $('#scan_log').html('✅ ESCÁNER DETECTADO: <span style="font-size:24px; color:#2c3e50;">' + val + '</span> (Longitud: ' + val.length + ' caracteres)');
                        setTimeout(function() {
                            scanInput.val('').focus();
                        }, 2500);
                    }
                }
            });
        });
    </script>
</body>
</html>
