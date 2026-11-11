<?php
ensure_session();
include "../conexion.php";

// Solo admins y supervisores
if (!isset($_SESSION['active']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2)) {
    header('location: ../');
    exit;
}

// Cargar listas para los selects
$query_cat  = mysqli_query($conection, "SELECT * FROM categoria WHERE status = 1 ORDER BY nombre ASC");
$query_prov = mysqli_query($conection, "SELECT * FROM proveedor ORDER BY proveedor ASC");
$query_marca= mysqli_query($conection, "SELECT * FROM marca WHERE status = 1 ORDER BY nombre ASC");
$query_tam  = mysqli_query($conection, "SELECT * FROM tamano WHERE status = 1 ORDER BY nombre ASC");

$query_conf = mysqli_query($conection, "SELECT moneda FROM configuracion");
$moned = '$';
if ($query_conf && mysqli_num_rows($query_conf) > 0) {
    $conf  = mysqli_fetch_assoc($query_conf);
    $moned = $conf['moneda'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">
    <title>Carga Masiva de Productos</title>
    <?php include "includes/scripts.php"; ?>
    <!-- Tesseract.js para OCR en el navegador -->
    <script src="https://unpkg.com/tesseract.js@5/dist/tesseract.min.js"></script>
    <!-- Mammoth.js para leer archivos .docx de Word en el navegador -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>
    <!-- SheetJS para leer y procesar planillas de Excel (.xlsx / .csv) en el navegador -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <style>
        /* ===== CARGA MASIVA ===== */
        .cm-wrapper { padding: 15px 20px; }

        .cm-header {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 18px;
            border-bottom: 2px solid #286581; padding-bottom: 10px;
        }
        .cm-header h1 { font-size: 22px; color: #286581; margin: 0; }
        .cm-badge {
            background: #286581; color: #fff; border-radius: 50%;
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }

        /* Panel config */
        .cm-config-panel {
            background: #fff; border: 1px solid #d0dde6; border-radius: 8px;
            padding: 16px 20px; margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(40,101,129,.08);
        }
        .cm-config-panel h3 {
            font-size: 14px; color: #286581; margin-bottom: 12px;
            letter-spacing: .5px; text-transform: uppercase;
            display: flex; align-items: center; gap: 7px;
        }
        .cm-config-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }
        .cm-config-grid > div {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .cm-config-grid label {
            display: block; font-size: 12px; color: #555; font-weight: bold;
            margin-bottom: 4px; text-transform: uppercase; letter-spacing: .5px;
        }
        .cm-config-grid select {
            width: 100%; padding: 7px 10px; border: 1px solid #b0c4cc;
            border-radius: 5px; font-size: 13px; background: #f8fbfd; color: #333;
            transition: border-color .2s;
        }
        .cm-config-grid select:focus { border-color: #286581; outline: none; background: #fff; }
        .cm-config-grid select.required-field { border-left: 3px solid #e74c3c; }

        /* ===== PANEL OCR ===== */
        .cm-ocr-panel {
            background: #fff; border: 1px solid #d0dde6; border-radius: 8px;
            padding: 16px 20px; margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(40,101,129,.08);
        }
        .cm-ocr-panel h3 {
            font-size: 14px; color: #1a6e3c; margin-bottom: 12px;
            text-transform: uppercase; letter-spacing: .5px;
            display: flex; align-items: center; gap: 7px;
        }

        /* Zona drag & drop */
        .ocr-dropzone {
            border: 2.5px dashed #a0c4b8;
            border-radius: 10px;
            padding: 28px 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
            background: #f4fcf8;
            position: relative;
        }
        .ocr-dropzone:hover, .ocr-dropzone.dragover {
            border-color: #27ae60; background: #eafaf1;
        }
        .ocr-dropzone input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .ocr-dropzone .dz-icon { font-size: 36px; color: #27ae60; margin-bottom: 8px; }
        .ocr-dropzone .dz-text { font-size: 14px; color: #555; }
        .ocr-dropzone .dz-sub  { font-size: 12px; color: #888; margin-top: 4px; }

        /* Preview y resultado OCR */
        .ocr-main-area {
            display: flex; gap: 18px; flex-wrap: wrap; margin-top: 16px;
        }
        .ocr-preview-box {
            flex: 0 0 auto; max-width: 320px;
        }
        .ocr-preview-box img {
            max-width: 100%; border-radius: 6px;
            border: 1px solid #cce; box-shadow: 0 2px 8px rgba(0,0,0,.12);
        }
        .ocr-text-box {
            flex: 1; min-width: 200px;
        }
        .ocr-text-box label {
            font-size: 12px; font-weight: bold; color: #555;
            text-transform: uppercase; letter-spacing: .5px; display: block; margin-bottom: 6px;
        }
        #ocr-raw-text {
            width: 100%; min-height: 160px; max-height: 260px;
            border: 1px solid #b0c4cc; border-radius: 5px;
            padding: 8px 10px; font-size: 13px; font-family: monospace;
            resize: vertical; background: #f8fbfd; color: #333;
        }
        #ocr-raw-text:focus { border-color: #286581; outline: none; }

        /* Barra de progreso OCR */
        .ocr-progress-wrap {
            display: none; margin-top: 10px;
        }
        .ocr-progress-wrap.active { display: block; }
        .ocr-progress-bar-bg {
            background: #e0ece8; border-radius: 20px; height: 8px; overflow: hidden;
        }
        .ocr-progress-bar {
            background: linear-gradient(90deg, #27ae60, #1abc9c);
            height: 100%; width: 0%; border-radius: 20px;
            transition: width .3s ease;
        }
        .ocr-progress-label {
            font-size: 12px; color: #555; margin-top: 4px; text-align: center;
        }

        /* Botones OCR */
        .ocr-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px; }

        .btn-ocr-scan {
            background: #27ae60; color: #fff; border: none; border-radius: 5px;
            padding: 8px 18px; font-size: 13px; cursor: pointer;
            display: flex; align-items: center; gap: 6px;
            transition: background .2s; font-family: arial; letter-spacing: .5px;
        }
        .btn-ocr-scan:hover { background: #219a52; }
        .btn-ocr-scan:disabled { background: #9bb6c2; cursor: not-allowed; }

        .btn-ocr-parse {
            background: #286581; color: #fff; border: none; border-radius: 5px;
            padding: 8px 18px; font-size: 13px; cursor: pointer;
            display: flex; align-items: center; gap: 6px;
            transition: background .2s; font-family: arial; letter-spacing: .5px;
        }
        .btn-ocr-parse:hover { background: #1d4d63; }
        .btn-ocr-parse:disabled { background: #9bb6c2; cursor: not-allowed; }

        /* Info tip */
        .cm-info-tip {
            background: #eaf4f8; border-left: 3px solid #286581;
            padding: 8px 12px; border-radius: 0 5px 5px 0;
            font-size: 12px; color: #555; margin-bottom: 14px;
        }
        .cm-info-tip strong { color: #286581; }

        .ocr-tip {
            background: #f0faf4; border-left: 3px solid #27ae60;
            padding: 8px 12px; border-radius: 0 5px 5px 0;
            font-size: 12px; color: #555; margin-bottom: 14px;
        }
        .ocr-tip strong { color: #1a6e3c; }

        /* Tabla de productos */
        .cm-table-section {
            background: #fff; border: 1px solid #d0dde6; border-radius: 8px;
            padding: 16px 20px; margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(40,101,129,.08);
        }
        .cm-table-section h3 {
            font-size: 14px; color: #286581; margin-bottom: 12px;
            text-transform: uppercase; letter-spacing: .5px;
            display: flex; align-items: center; gap: 7px;
        }
        .cm-toolbar {
            display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; align-items: center;
        }
        .btn-cm-add {
            background: #27ae60; color: #fff; border: none; border-radius: 5px;
            padding: 8px 16px; font-size: 13px; cursor: pointer;
            display: flex; align-items: center; gap: 6px;
            transition: background .2s; font-family: arial; letter-spacing: .5px;
        }
        .btn-cm-add:hover { background: #219a52; }
        .btn-cm-clear {
            background: #e67e22; color: #fff; border: none; border-radius: 5px;
            padding: 8px 16px; font-size: 13px; cursor: pointer;
            display: flex; align-items: center; gap: 6px;
            transition: background .2s; font-family: arial; letter-spacing: .5px;
        }
        .btn-cm-clear:hover { background: #d35400; }
        .cm-row-count { margin-left: auto; font-size: 12px; color: #666; font-style: italic; }

        #cm-tabla { width: 100%; border-collapse: collapse; font-size: 13px; }
        #cm-tabla thead tr { background: #286581; color: #fff; }
        #cm-tabla thead th {
            padding: 9px 10px; text-align: left;
            font-size: 12px; font-weight: bold; letter-spacing: .5px;
        }
        #cm-tabla thead th:last-child { width: 50px; text-align: center; }
        #cm-tabla tbody tr {
            border-bottom: 1px solid #e8eff3; transition: background .15s;
        }
        #cm-tabla tbody tr:nth-child(even) { background: #f5fafc; }
        #cm-tabla tbody tr:hover { background: #eaf4f8; }
        #cm-tabla td { padding: 5px 6px; vertical-align: middle; }

        .cm-input {
            width: 100%; padding: 5px 8px; border: 1px solid #ccc; border-radius: 4px;
            font-size: 13px; font-family: arial; background: #fff;
            transition: border-color .2s, box-shadow .2s;
        }
        .cm-input:focus { border-color: #286581; outline: none; box-shadow: 0 0 0 2px rgba(40,101,129,.15); }
        .cm-input.error-field { border-color: #e74c3c; background: #fdf2f2; }
        /* Resaltar filas que vienen del OCR */
        .cm-input.ocr-filled { border-color: #27ae60; background: #f0faf4; }
        .cm-input-num  { text-align: right; width: 95px; }
        .cm-input-desc { min-width: 160px; }
        .cm-input-cod  { min-width: 130px; }
        .cm-row-num    { text-align: center; font-size: 11px; color: #999; font-weight: bold; width: 30px; }

        .btn-del-row {
            background: none; border: none; color: #e74c3c; cursor: pointer;
            font-size: 16px; padding: 4px 8px; border-radius: 4px;
            transition: background .15s;
            display: flex; align-items: center; justify-content: center; width: 100%;
        }
        .btn-del-row:hover { background: #fde8e8; }

        /* Resultados */
        #cm-resultado {
            display: none; background: #fff; border: 1px solid #d0dde6; border-radius: 8px;
            padding: 16px 20px; margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(40,101,129,.08);
        }
        #cm-resultado h3 { font-size: 14px; margin-bottom: 12px; color: #286581; text-transform: uppercase; letter-spacing: .5px; }
        .cm-resumen-grid { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 12px; }
        .cm-resumen-card { border-radius: 8px; padding: 12px 20px; text-align: center; min-width: 120px; }
        .cm-resumen-card.success { background: #d5f5e3; color: #1a6e3c; border: 1px solid #a9dfbf; }
        .cm-resumen-card.danger  { background: #fde8e8; color: #922b21; border: 1px solid #f1948a; }
        .cm-resumen-card .card-num { font-size: 28px; font-weight: bold; }
        .cm-resumen-card .card-lbl { font-size: 12px; }
        #cm-log-list {
            max-height: 200px; overflow-y: auto; border: 1px solid #e0e0e0;
            border-radius: 5px; padding: 8px;
        }
        #cm-log-list li {
            font-size: 12px; padding: 3px 0;
            border-bottom: 1px solid #f0f0f0; list-style: none;
        }
        #cm-log-list li:last-child { border-bottom: none; }
        #cm-log-list .log-ok  { color: #1a6e3c; }
        #cm-log-list .log-err { color: #922b21; }

        /* Footer */
        .cm-footer { display: flex; justify-content: flex-end; gap: 12px; flex-wrap: wrap; }
        .btn-cm-save {
            background: #286581; color: #fff; border: none; border-radius: 6px;
            padding: 10px 28px; font-size: 14px; cursor: pointer;
            display: flex; align-items: center; gap: 8px;
            transition: background .2s, transform .1s; font-family: arial; letter-spacing: .5px; font-weight: bold;
        }
        .btn-cm-save:hover { background: #1d4d63; transform: translateY(-1px); }
        .btn-cm-save:active { transform: translateY(0); }
        .btn-cm-save:disabled { background: #9bb6c2; cursor: not-allowed; transform: none; }
        .cm-spinner { display: none; align-items: center; gap: 8px; font-size: 13px; color: #286581; }
        .cm-spinner.active { display: flex; }
        .spinner-icon {
            width: 18px; height: 18px; border: 2px solid #286581;
            border-top-color: transparent; border-radius: 50%;
            animation: cm-spin .7s linear infinite;
        }
        @keyframes cm-spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>
    <section id="container">
        <div class="cm-wrapper">

            <!-- ENCABEZADO -->
            <div class="cm-header">
                <div class="cm-badge"><i class="fas fa-list-alt"></i></div>
                <h1>Carga Masiva de Productos</h1>
            </div>

            <!-- PANEL CONFIGURACIÓN GLOBAL -->
            <div class="cm-config-panel">
                <h3><i class="fas fa-sliders-h"></i> Configuración Global
                    <small style="font-size:11px;font-weight:normal;color:#888;margin-left:8px;">(se aplica a todos los productos de la lista)</small>
                </h3>
                <div class="cm-config-grid">
                    <div>
                        <label><i class="fas fa-building"></i> Proveedor <span style="color:#e74c3c;">*</span></label>
                        <div style="display: flex; gap: 5px;">
                            <select id="cm-proveedor" class="required-field" style="flex:1;">
                                <option value="">-- Seleccionar Proveedor --</option>
                                <?php while ($p = mysqli_fetch_assoc($query_prov)): ?>
                                    <option value="<?= $p['codproveedor'] ?>" data-nombre="<?= htmlspecialchars($p['proveedor']) ?>">
                                        <?= htmlspecialchars($p['proveedor']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button type="button" class="btn-cm-add" style="padding: 0 10px; font-size: 14px;" onclick="nuevoProveedorRapido()" title="Crear nuevo Proveedor"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div>
                        <label><i class="fas fa-th-list"></i> Categoría <span style="color:#e74c3c;">*</span></label>
                        <div style="display: flex; gap: 5px;">
                            <select id="cm-categoria" class="required-field" style="flex:1;">
                                <option value="">-- Seleccionar Categoría --</option>
                                <?php while ($c = mysqli_fetch_assoc($query_cat)): ?>
                                    <option value="<?= $c['idcategoria'] ?>">
                                        <?= htmlspecialchars($c['nombre']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button type="button" class="btn-cm-add" style="padding: 0 10px; font-size: 14px;" onclick="nuevaCategoriaRapida()" title="Crear nueva Categoría"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div>
                        <label><i class="fas fa-tag"></i> Marca <small style="color:#888;">(opcional)</small></label>
                        <div style="display: flex; gap: 5px;">
                            <select id="cm-marca" style="flex:1;">
                                <option value="">-- Sin Marca --</option>
                                <?php while ($m = mysqli_fetch_assoc($query_marca)): ?>
                                    <option value="<?= $m['idmarca'] ?>">
                                        <?= htmlspecialchars($m['nombre']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button type="button" class="btn-cm-add" style="padding: 0 10px; font-size: 14px;" onclick="nuevaMarcaRapida()" title="Crear nueva Marca"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div>
                        <label><i class="fas fa-ruler-combined"></i> Tamaño <small style="color:#888;">(opcional)</small></label>
                        <div style="display: flex; gap: 5px;">
                            <select id="cm-tamano" style="flex:1;">
                                <option value="">-- Sin Tamaño --</option>
                                <?php while ($t = mysqli_fetch_assoc($query_tam)): ?>
                                    <option value="<?= $t['idtamano'] ?>">
                                        <?= htmlspecialchars($t['nombre']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button type="button" class="btn-cm-add" style="padding: 0 10px; font-size: 14px;" onclick="nuevoTamanoRapido()" title="Crear nuevo Tamaño"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANEL PLANTILLA EXCEL OFICIAL -->
            <div class="cm-ocr-panel" style="border-color:#27ae60; background:#f4fcf8;">
                <h3 style="color:#1a6e3c;"><i class="fas fa-file-excel"></i> Carga Masiva mediante Plantilla Excel (.xlsx / .csv)</h3>

                <div class="ocr-tip" style="background:#eafaf1; border-color:#27ae60;">
                    <strong>📊 La forma más recomendada para tus clientes:</strong> 
                    Descargá la plantilla estándar de Excel, completá los productos y subila. Se cargan al instante con 100% de precisión.
                </div>

                <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; margin-top:10px;">
                    <button class="btn-cm-add" onclick="descargarPlantillaExcel()" style="background:#1f7244;">
                        <i class="fas fa-download"></i> Descargar Plantilla Excel Estándar
                    </button>
                    
                    <div style="flex:1; min-width:240px;">
                        <input type="file" id="excel-file-input" accept=".xlsx, .xls, .csv" onchange="subirPlantillaExcel(this)" style="padding:6px; background:#fff; border:1px solid #a0c4b8; border-radius:5px; width:100%;">
                    </div>
                </div>
            </div>

            <!-- PANEL COPIAR Y PEGAR DESDE WORD / EXCEL -->
            <div class="cm-ocr-panel" style="border-color:#286581;">
                <h3 style="color:#286581;"><i class="fas fa-file-word"></i> Cargar o Pegar desde Word / Excel / Texto</h3>

                <div class="cm-info-tip">
                    <strong>📄 Método 100% Exacto:</strong> Copiá la lista o tabla desde Word/Excel y pegala acá abajo. 
                    También podés escribirla directamente. El sistema detecta automáticamente cada columna por tabulación o espacios.
                </div>

                <div class="ocr-text-box">
                    <div style="margin-bottom: 10px;">
                        <label><i class="fas fa-file-upload"></i> Subir archivo de Word (.docx) o Texto (.csv / .txt)</label>
                        <input type="file" id="word-file-input" accept=".docx,.doc,.txt,.csv" onchange="onWordFileSelected(this)" style="padding:6px; background:#fff; border:1px solid #b0c4cc; border-radius:5px; width:100%;">
                    </div>

                    <label><i class="fas fa-paste"></i> O Pegá tu lista aquí (Nombre [Tab/Espacio] Cantidad [Tab/Espacio] Costo [Tab/Espacio] Precio)</label>
                    <textarea id="word-raw-text" style="width:100%; min-height:140px; max-height:240px; border:1px solid #b0c4cc; border-radius:5px; padding:10px; font-family:monospace; font-size:13px; background:#f8fbfd;" placeholder="Ejemplo copiado de Word o Excel:&#10;Bincha Doctora	15	4000	5000&#10;Antifaz Negro	174	3500	5000&#10;Globo Numero 1	26	5000	10000"></textarea>

                    <div class="ocr-actions">
                        <button class="btn-ocr-parse" onclick="parsearTextoWord()">
                            <i class="fas fa-table"></i> Procesar Texto a Tabla
                        </button>
                    </div>
                </div>
            </div>

            <!-- PANEL OCR / CARGA DE FOTO -->
            <div class="cm-ocr-panel">
                <h3><i class="fas fa-camera"></i> Cargar Lista desde Foto (OCR)</h3>

                <div class="ocr-tip">
                    <strong>📷 Cómo funciona:</strong>
                    Subí la foto de tu planilla escrita a mano o impresa → el sistema la lee automáticamente y completa la tabla.
                </div>

                <!-- Zona de carga -->
                <div class="ocr-dropzone" id="ocr-dropzone">
                    <input type="file" id="ocr-file-input" accept="image/*,.doc,.docx,.xls,.xlsx,.csv,.txt" onchange="onFileSelected(this)">
                    <div class="dz-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="dz-text">Arrastrá un archivo o foto aquí (<strong>Word, Excel, Imagen, TXT</strong>)</div>
                    <div class="dz-sub">Formatos: .DOCX, .XLSX, .CSV, .TXT, JPG, PNG</div>
                </div>

                <!-- Área principal: imagen + texto OCR -->
                <div class="ocr-main-area" id="ocr-main-area" style="display:none;">
                    <div class="ocr-preview-box">
                        <img id="ocr-preview-img" src="" alt="Vista previa">
                    </div>
                    <div class="ocr-text-box">
                        <label><i class="fas fa-file-alt"></i> Texto reconocido (podés corregirlo)</label>
                        <textarea id="ocr-raw-text" placeholder="Aquí aparecerá el texto leído de la imagen..."></textarea>

                        <!-- Barra de progreso -->
                        <div class="ocr-progress-wrap" id="ocr-progress-wrap">
                            <div class="ocr-progress-bar-bg">
                                <div class="ocr-progress-bar" id="ocr-progress-bar"></div>
                            </div>
                            <div class="ocr-progress-label" id="ocr-progress-label">Iniciando OCR...</div>
                        </div>

                        <div class="ocr-actions">
                            <button class="btn-ocr-scan" id="btn-ocr-scan" onclick="escanearImagen()">
                                <i class="fas fa-search"></i> Leer Imagen
                            </button>
                            <button class="btn-ocr-parse" id="btn-ocr-parse" onclick="parsearTextoOCR()" disabled>
                                <i class="fas fa-magic"></i> Procesar → Tabla
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA DE PRODUCTOS -->
            <div class="cm-table-section">
                <h3><i class="fas fa-table"></i> Lista de Productos</h3>
                <div class="cm-info-tip">
                    <strong>Instrucciones:</strong> Completá o revisá la tabla. Si el <strong>Código de Barras</strong> queda vacío, se genera automáticamente.
                    <strong>Nombre</strong> y <strong>Precio</strong> son obligatorios por fila.
                    Las celdas <span style="color:#27ae60;font-weight:bold;">verdes</span> fueron cargadas desde la foto.
                </div>
                <div class="cm-toolbar">
                    <button class="btn-cm-add" onclick="agregarFila()">
                        <i class="fas fa-plus"></i> Agregar Fila
                    </button>
                    <button class="btn-cm-clear" onclick="limpiarTabla()">
                        <i class="fas fa-trash-alt"></i> Limpiar Todo
                    </button>
                    <span class="cm-row-count" id="cm-row-count">0 filas</span>
                </div>
                <div style="overflow-x: auto;">
                    <table id="cm-tabla">
                        <thead>
                            <tr>
                                <th style="width:32px;">#</th>
                                <th>Nombre / Descripción <span style="color:#f0c040;">*</span></th>
                                <th>Código de Barras</th>
                                <th>Código Interno</th>
                                <th>Cantidad (Stock)</th>
                                <th>Costo (<?= $moned ?>)</th>
                                <th>Precio (<?= $moned ?>) <span style="color:#f0c040;">*</span></th>
                                <th style="text-align:center;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cm-tbody"><!-- Filas dinámicas --></tbody>
                    </table>
                </div>
            </div>

            <!-- RESULTADOS -->
            <div id="cm-resultado">
                <h3><i class="fas fa-clipboard-check"></i> Resultado de la Carga</h3>
                <div class="cm-resumen-grid">
                    <div class="cm-resumen-card success">
                        <div class="card-num" id="cm-total-ok">0</div>
                        <div class="card-lbl">Productos Registrados</div>
                    </div>
                    <div class="cm-resumen-card danger">
                        <div class="card-num" id="cm-total-err">0</div>
                        <div class="card-lbl">Con Errores</div>
                    </div>
                </div>
                <ul id="cm-log-list"></ul>
            </div>

            <!-- FOOTER -->
            <div class="cm-footer">
                <div class="cm-spinner" id="cm-spinner">
                    <div class="spinner-icon"></div>
                    <span>Guardando productos...</span>
                </div>
                <button class="btn-cm-save" id="btn-guardar-todo" onclick="guardarTodo()">
                    <i class="fas fa-save"></i> Guardar Todo
                </button>
            </div>

        </div><!-- /.cm-wrapper -->
    </section>

    <?php include "includes/footer.php"; ?>

    <script>
    // ================================================================
    //  VARIABLES GLOBALES
    // ================================================================
    var filaCounter  = 0;
    var ocrImageData = null; // data URL de la imagen cargada

    // ================================================================
    //  INICIALIZACIÓN
    // ================================================================
    $(document).ready(function() {
        for (var i = 0; i < 5; i++) agregarFila();

        // Drag & drop
        var dz = document.getElementById('ocr-dropzone');
        dz.addEventListener('dragover', function(e) { e.preventDefault(); dz.classList.add('dragover'); });
        dz.addEventListener('dragleave', function()  { dz.classList.remove('dragover'); });
        dz.addEventListener('drop', function(e) {
            e.preventDefault();
            dz.classList.remove('dragover');
            var f = e.dataTransfer.files[0];
            if (f && f.type.startsWith('image/')) cargarImagen(f);
        });
    });

    // ================================================================
    //  PLANTILLA EXCEL - DESCARGA Y LECTURA (.XLSX / .CSV)
    // ================================================================
    function descargarPlantillaExcel() {
        if (typeof XLSX === 'undefined') {
            alert('La librería XLSX aún está cargando. Por favor aguardá 2 segundos.');
            return;
        }

        // Estructura de ejemplo con encabezados oficiales
        var datosPlantilla = [
            ["Descripcion", "CodigoBarras", "CodigoInterno", "Cantidad", "Costo", "Precio"],
            ["Globo Numero 1 Fuccia", "", "GL-001", 26, 5000, 10000],
            ["Bincha Doctora", "", "BN-002", 15, 4000, 5000],
            ["Antifaz Negro Mujer", "", "", 174, 3500, 5000],
            ["Cinta para Moño 1cm Rosa", "", "", 8, 4000, 5000]
        ];

        var ws = XLSX.utils.aoa_to_sheet(datosPlantilla);
        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Productos");

        // Ancho automático de columnas
        ws['!cols'] = [
            { wch: 30 }, // Descripcion
            { wch: 18 }, // CodigoBarras
            { wch: 15 }, // CodigoInterno
            { wch: 10 }, // Cantidad
            { wch: 12 }, // Costo
            { wch: 12 }  // Precio
        ];

        XLSX.writeFile(wb, "Plantilla_Carga_Masiva_Productos.xlsx");
    }

    function subirPlantillaExcel(input) {
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];

        if (typeof XLSX === 'undefined') {
            alert('La librería XLSX aún está cargando. Por favor reintentá en un instante.');
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, { type: 'array' });

                // Tomar la primera hoja
                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];
                var json = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

                if (!json || json.length < 2) {
                    alert('El archivo Excel parece estar vacío.');
                    return;
                }

                var productos = [];
                // Recorrer filas (saltando encabezados en fila 0 si los hay)
                for (var i = 0; i < json.length; i++) {
                    var fila = json[i];
                    if (!fila || fila.length === 0) continue;

                    // Si es la fila de encabezados, ignorarla
                    var col0 = String(fila[0] || '').trim();
                    if (/^(descripcion|descripción|nombre|producto)/i.test(col0)) continue;

                    var desc = String(fila[0] || '').trim();
                    if (!desc) continue;

                    var cod  = String(fila[1] || '').trim();
                    var cint = String(fila[2] || '').trim();
                    var cant = parseInt(fila[3]) || 0;
                    var cost = parseFloat(fila[4]) || 0;
                    var prec = parseFloat(fila[5]) || 0;

                    // Si solo tiene 3 columnas numéricas (Desc, Cant, Precio)
                    if (fila.length === 3 && typeof fila[1] === 'number') {
                        cant = parseInt(fila[1]) || 0;
                        prec = parseFloat(fila[2]) || 0;
                        cod = ''; cint = ''; cost = 0;
                    }

                    productos.push({
                        nombre: desc,
                        codigo: cod,
                        codigo_interno: cint,
                        cantidad: cant,
                        costo: cost,
                        precio: prec
                    });
                }

                if (productos.length === 0) {
                    alert('No se encontraron filas con productos válidos en el archivo Excel.');
                    return;
                }

                // Cargar a la grilla
                document.getElementById('cm-tbody').innerHTML = '';
                filaCounter = 0;

                productos.forEach(function(prod) {
                    agregarFilaOCR(prod);
                });

                actualizarNumeros();
                document.getElementById('cm-tabla').scrollIntoView({ behavior: 'smooth' });
                alert('✔ Se cargaron ' + productos.length + ' productos desde el archivo Excel. Revisá y presioná "Guardar Todo".');

            } catch (err) {
                alert('Error al leer la planilla de Excel: ' + err.message);
            }
        };

        reader.readAsArrayBuffer(file);
    }

    // ================================================================
    //  OCR - CARGA DE IMAGEN
    // ================================================================
    function onFileSelected(input) {
        if (input.files && input.files[0]) {
            procesarArchivoSeleccionado(input.files[0]);
        }
    }

    function onWordFileSelected(input) {
        if (input.files && input.files[0]) {
            procesarArchivoSeleccionado(input.files[0]);
        }
    }

    function procesarArchivoSeleccionado(file) {
        var ext = file.name.split('.').pop().toLowerCase();
        if (file.type.startsWith('image/')) {
            cargarImagen(file);
        } else if (ext === 'docx' || ext === 'doc') {
            // Leer archivo .docx con Mammoth
            var reader = new FileReader();
            reader.onload = function(loadEvent) {
                var arrayBuffer = loadEvent.target.result;
                if (typeof mammoth !== 'undefined') {
                    mammoth.extractRawText({arrayBuffer: arrayBuffer})
                        .then(function(result) {
                            document.getElementById('word-raw-text').value = result.value;
                            document.getElementById('word-raw-text').scrollIntoView({ behavior: 'smooth' });
                            parsearTextoWord();
                        })
                        .catch(function(err) {
                            alert('Error al leer el archivo Word: ' + err.message);
                        });
                } else {
                    alert('La librería de Word está cargando. Por favor intentá de nuevo en 2 segundos.');
                }
            };
            reader.readAsArrayBuffer(file);
        } else {
            // Leer archivo de texto / csv / txt
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('word-raw-text').value = e.target.result;
                document.getElementById('word-raw-text').scrollIntoView({ behavior: 'smooth' });
                parsearTextoWord();
            };
            reader.readAsText(file);
        }
    }

    function cargarImagen(file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            ocrImageData = e.target.result;
            document.getElementById('ocr-preview-img').src = ocrImageData;
            document.getElementById('ocr-main-area').style.display = 'flex';
            document.getElementById('ocr-raw-text').value = '';
            document.getElementById('btn-ocr-parse').disabled = true;
            // Auto-escanear
            escanearImagen();
        };
        reader.readAsDataURL(file);
    }

    // ================================================================
    //  OCR - ESCANEAR CON TESSERACT
    // ================================================================
    function escanearImagen() {
        if (!ocrImageData) { alert('Primero cargá una imagen.'); return; }

        var btnScan  = document.getElementById('btn-ocr-scan');
        var progWrap = document.getElementById('ocr-progress-wrap');
        var progBar  = document.getElementById('ocr-progress-bar');
        var progLbl  = document.getElementById('ocr-progress-label');
        var textarea = document.getElementById('ocr-raw-text');

        btnScan.disabled = true;
        btnScan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Leyendo...';
        progWrap.classList.add('active');
        progBar.style.width  = '0%';
        progLbl.textContent  = 'Iniciando OCR...';
        textarea.value       = '';

        Tesseract.recognize(
            ocrImageData,
            'spa',  // Español
            {
                logger: function(m) {
                    if (m.status === 'recognizing text') {
                        var pct = Math.round(m.progress * 100);
                        progBar.style.width = pct + '%';
                        progLbl.textContent = 'Reconociendo texto: ' + pct + '%';
                    } else {
                        progLbl.textContent = m.status;
                    }
                }
            }
        ).then(function(result) {
            textarea.value = result.data.text;
            progBar.style.width  = '100%';
            progLbl.textContent  = '✔ Texto reconocido. Revisá y presioná "Procesar → Tabla".';
            btnScan.disabled     = false;
            btnScan.innerHTML    = '<i class="fas fa-search"></i> Leer imagen';
            document.getElementById('btn-ocr-parse').disabled = false;
        }).catch(function(err) {
            progLbl.textContent  = '✘ Error al leer la imagen: ' + err.message;
            btnScan.disabled     = false;
            btnScan.innerHTML    = '<i class="fas fa-search"></i> Leer imagen';
        });
    }

    // ================================================================
    //  PARSEAR TEXTO DE WORD / EXCEL → TABLA
    // ================================================================
    function parsearTextoWord() {
        var texto = document.getElementById('word-raw-text').value.trim();
        if (!texto) { alert('Por favor pegá o escribí un texto primero.'); return; }

        var lineas = texto.split('\n').map(function(l) { return l.trim(); }).filter(function(l) { return l.length > 0; });
        var productos = [];

        // ALGORITMO ROBUSTO DE AGRUPAMIENTO VERTICAL O HORIZONTAL
        // Si hay líneas de texto aisladas seguidas de líneas puramente numéricas:
        var i = 0;
        while (i < lineas.length) {
            var linea = lineas[i];

            // Saltear líneas de títulos irrelevantes
            if (/^(inventario|globo\s+tamaño|lista\s+de|tabla|hoja|página)/i.test(linea) && !/^(número|numero|letra|bincha|antifaz|globo\s+\d)/i.test(linea)) {
                i++;
                continue;
            }

            // Si la línea contiene texto (no es solo número)
            var esSoloNumero = !isNaN(parseFloat(linea.replace(/\./g,'').replace(/,/g,''))) && /^\d+$/.test(linea.replace(/\./g,'').replace(/,/g,''));

            if (!esSoloNumero || /^(número|numero)\s*\d+/i.test(linea)) {
                var nombre = linea;
                var cant = 0, cost = 0, prec = 0;
                var nums = [];

                // Mirar las siguientes 3-4 líneas para ver si son números
                var k = i + 1;
                while (k < lineas.length && k <= i + 3) {
                    var sig = lineas[k].replace(/\./g, '').replace(/,/g, '');
                    if (!isNaN(parseFloat(sig)) && /^\d+$/.test(sig)) {
                        nums.push({ val: parseFloat(sig), index: k });
                        k++;
                    } else {
                        break;
                    }
                }

                if (nums.length > 0) {
                    if (nums.length >= 3) {
                        cant = nums[0].val;
                        cost = nums[1].val;
                        prec = nums[2].val;
                        i = nums[2].index + 1;
                    } else if (nums.length === 2) {
                        cant = nums[0].val;
                        prec = nums[1].val;
                        i = nums[1].index + 1;
                    } else {
                        prec = nums[0].val;
                        i = nums[0].index + 1;
                    }

                    productos.push({
                        nombre: nombre,
                        cantidad: cant,
                        costo: cost,
                        precio: prec,
                        codigo: ''
                    });
                    continue;
                }
            }

            // Si es una línea horizontal tabulada o separada por espacios
            var partes = linea.split(/\t|;|\s{2,}/).map(function(p) { return p.trim(); }).filter(function(p) { return p.length > 0; });
            if (partes.length >= 2) {
                var nomH = partes[0];
                var cantH = 0, costH = 0, precH = 0;
                var numsH = [];
                for (var pIdx = 1; pIdx < partes.length; pIdx++) {
                    var valH = parseFloat(partes[pIdx].replace(/\./g, '').replace(/,/g, ''));
                    if (!isNaN(valH)) numsH.push(valH);
                }
                if (numsH.length >= 3) { cantH = numsH[0]; costH = numsH[1]; precH = numsH[2]; }
                else if (numsH.length === 2) { cantH = numsH[0]; precH = numsH[1]; }
                else if (numsH.length === 1) { precH = numsH[0]; }

                if (nomH) {
                    productos.push({
                        nombre: nomH,
                        cantidad: cantH,
                        costo: costH,
                        precio: precH,
                        codigo: ''
                    });
                }
            }

            i++;
        }

        // 2. SI NO ERA VERTICAL, PROBAR PARSEO HORIZONTAL (Tabulado/CSV/Espacios)
        if (!esVertical || productos.length === 0) {
            lineas.forEach(function(linea) {
                if (/^(nombre|descripcion|descripción|cantidad|costo|precio|codigo|código|stock)/i.test(linea)) return;

                var partes = [];
                if (linea.indexOf('\t') !== -1) {
                    partes = linea.split('\t');
                } else if (linea.indexOf(';') !== -1) {
                    partes = linea.split(';');
                } else if (linea.indexOf(',') !== -1 && !/^\d+,\d+$/.test(linea)) {
                    partes = linea.split(',');
                } else {
                    partes = linea.split(/\s{2,}/);
                }

                partes = partes.map(function(p) { return p.trim(); }).filter(function(p) { return p.length > 0; });
                if (partes.length === 0) return;

                var nombre = partes[0] || '';
                var cant = 0, cost = 0, prec = 0, cod = '';

                var nums = [];
                for (var k = 1; k < partes.length; k++) {
                    var raw = partes[k].replace(/\./g, '').replace(/,/g, '');
                    var val = parseFloat(raw);
                    if (!isNaN(val)) {
                        nums.push(val);
                    } else if (!cod && partes[k].length >= 3) {
                        cod = partes[k];
                    }
                }

                if (nums.length >= 3) {
                    cant = nums[0];
                    cost = nums[1];
                    prec = nums[2];
                } else if (nums.length === 2) {
                    cant = nums[0];
                    prec = nums[1];
                } else if (nums.length === 1) {
                    prec = nums[0];
                }

                if (nombre) {
                    productos.push({
                        nombre: nombre,
                        cantidad: cant,
                        costo: cost,
                        precio: prec,
                        codigo: cod
                    });
                }
            });
        }

        if (productos.length === 0) {
            alert('No se detectaron productos válidos. Asegurate de que el texto contenga el nombre y sus valores numéricos.');
            return;
        }

        document.getElementById('cm-tbody').innerHTML = '';
        filaCounter = 0;

        productos.forEach(function(prod) {
            agregarFilaOCR(prod);
        });

        actualizarNumeros();
        document.getElementById('cm-tabla').scrollIntoView({ behavior: 'smooth' });
        alert('✔ Se detectaron ' + productos.length + ' productos correctamente. Revisá los datos antes de guardar.');
    }

    // ================================================================
    //  OCR - PARSEAR TEXTO → TABLA
    //  Formato esperado: columnas separadas por espacios/tabulaciones
    //  Columnas de la planilla: Nombre | Cantidad | Costo | Codigo | Precio
    // ================================================================
    function parsearTextoOCR() {
        var texto = document.getElementById('ocr-raw-text').value.trim();
        if (!texto) { alert('No hay texto para procesar.'); return; }

        var lineas = texto.split('\n');
        var productos = [];

        lineas.forEach(function(linea) {
            linea = linea.trim();
            if (!linea) return;

            // Ignorar líneas que son encabezados o títulos (sin números)
            if (/^(nombre|color|cantidad|costo|codigo|precio|globo|tamaño|tamano|asa|import|lista|total)/i.test(linea)) return;
            // Ignorar líneas muy cortas
            if (linea.length < 3) return;

            // Intentar extraer números de la línea
            // Patrón: buscar todos los números (pueden tener puntos o comas como separador de miles)
            var nums = [];
            var numMatches = linea.match(/[\d][.\d,]*/g) || [];
            numMatches.forEach(function(n) {
                // Limpiar: "15.000" → 15000, "18,000" → 18000
                var limpio = n.replace(/\./g, '').replace(/,/g, '');
                var val = parseInt(limpio);
                if (!isNaN(val) && val > 0) nums.push(val);
            });

            // Extraer el nombre/descripción: todo lo que es texto (no número) al inicio
            var nombre = linea.replace(/[\d][.\d,]*/g, '').replace(/[|]/g, '').trim();
            // Quitar caracteres raros al inicio y final
            nombre = nombre.replace(/^[^a-záéíóúüñA-ZÁÉÍÓÚÜÑ]+/, '').trim();
            nombre = nombre.replace(/\s+/g, ' ').trim();

            if (!nombre || nombre.length < 2) return;

            // Asignar números a columnas según la foto:
            // Col 1 = Cantidad, Col 2 = Costo, Col 3 = Código (vacío en foto), Col 4 = Precio
            // Si hay 4+ números: [0]=cant, [1]=costo, [2]=codigo, [3]=precio
            // Si hay 3 números: [0]=cant, [1]=costo, [2]=precio
            // Si hay 2 números: [0]=cant, [1]=precio
            // Si hay 1 número: es el precio
            var cantidad = 0, costo = 0, codigo = '', precio = 0;

            if (nums.length >= 4) {
                cantidad = nums[0];
                costo    = nums[1];
                // nums[2] podría ser código — ignorar si es muy grande (precio)
                precio   = nums[3];
            } else if (nums.length === 3) {
                cantidad = nums[0];
                costo    = nums[1];
                precio   = nums[2];
            } else if (nums.length === 2) {
                cantidad = nums[0];
                precio   = nums[1];
            } else if (nums.length === 1) {
                precio = nums[0];
            }

            // Validación mínima: si precio es 0 y costo > 0, usar costo como precio
            if (precio <= 0 && costo > 0) precio = costo;

            productos.push({
                nombre:   nombre,
                cantidad: cantidad,
                costo:    costo,
                precio:   precio,
                codigo:   codigo
            });
        });

        if (productos.length === 0) {
            alert('No se pudo extraer ningún producto del texto. Verificá que el texto reconocido sea correcto y ajustalo manualmente.');
            return;
        }

        // Limpiar tabla e insertar productos OCR
        document.getElementById('cm-tbody').innerHTML = '';
        filaCounter = 0;

        productos.forEach(function(prod) {
            agregarFilaOCR(prod);
        });

        actualizarNumeros();
        // Scroll a la tabla
        document.getElementById('cm-tabla').scrollIntoView({ behavior: 'smooth' });

        alert('✔ Se cargaron ' + productos.length + ' productos desde la foto. Revisá los datos antes de guardar.');
    }

    // ================================================================
    //  AGREGAR FILA (manual)
    // ================================================================
    function agregarFila() {
        filaCounter++;
        var n     = filaCounter;
        var tbody = document.getElementById('cm-tbody');
        var tr    = document.createElement('tr');
        tr.id     = 'fila-' + n;
        tr.innerHTML = buildFilaHTML(n, '', '', '', 0, 0, 0, false);
        tbody.appendChild(tr);
        actualizarNumeros();
        var d = document.getElementById('desc-' + n);
        if (d) d.focus();
    }

    // ================================================================
    //  AGREGAR FILA DESDE OCR (con datos pre-cargados)
    // ================================================================
    function agregarFilaOCR(prod) {
        filaCounter++;
        var n     = filaCounter;
        var tbody = document.getElementById('cm-tbody');
        var tr    = document.createElement('tr');
        tr.id     = 'fila-' + n;
        tr.innerHTML = buildFilaHTML(
            n,
            prod.nombre,
            prod.codigo || '',
            '',
            prod.cantidad,
            prod.costo,
            prod.precio,
            true   // ← marcado como OCR
        );
        tbody.appendChild(tr);
    }

    // ================================================================
    //  CONSTRUIR HTML DE UNA FILA
    // ================================================================
    function buildFilaHTML(n, desc, cod, cint, cant, costo, prec, isOcr) {
        var cls = isOcr ? ' ocr-filled' : '';
        return (
            '<td class="cm-row-num">' + '?' + '</td>' +
            '<td><input class="cm-input cm-input-desc' + cls + '" type="text"   id="desc-' + n + '" value="' + escHtml(desc) + '" placeholder="Ej: Rosa, Naranja..." /></td>' +
            '<td><input class="cm-input cm-input-cod'  + cls + '" type="text"   id="cod-'  + n + '" value="' + escHtml(cod)  + '" placeholder="Auto-generado" /></td>' +
            '<td><input class="cm-input cm-input-cod"            type="text"   id="cint-' + n + '" value="' + escHtml(cint) + '" placeholder="Opcional" /></td>' +
            '<td><input class="cm-input cm-input-num'  + cls + '" type="number" id="cant-' + n + '" value="' + cant + '" min="0" /></td>' +
            '<td><input class="cm-input cm-input-num'  + cls + '" type="number" id="cost-' + n + '" value="' + costo + '" min="0" step="0.01" /></td>' +
            '<td><input class="cm-input cm-input-num'  + cls + '" type="number" id="prec-' + n + '" value="' + prec + '" min="0" step="0.01" /></td>' +
            '<td><button class="btn-del-row" onclick="eliminarFila(' + n + ')" title="Eliminar fila"><i class="fas fa-times"></i></button></td>'
        );
    }

    function escHtml(str) {
        return String(str || '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // ================================================================
    //  UTILIDADES DE TABLA
    // ================================================================
    function eliminarFila(n) {
        var fila = document.getElementById('fila-' + n);
        if (fila) fila.remove();
        actualizarNumeros();
    }

    function actualizarNumeros() {
        var filas = document.querySelectorAll('#cm-tbody tr');
        filas.forEach(function(tr, idx) {
            var c = tr.querySelector('.cm-row-num');
            if (c) c.textContent = idx + 1;
        });
        document.getElementById('cm-row-count').textContent =
            filas.length + ' fila' + (filas.length !== 1 ? 's' : '');
    }

    function limpiarTabla() {
        if (!confirm('¿Limpiar toda la tabla?')) return;
        document.getElementById('cm-tbody').innerHTML = '';
        filaCounter = 0;
        actualizarNumeros();
        document.getElementById('cm-resultado').style.display = 'none';
        for (var i = 0; i < 5; i++) agregarFila();
    }

    // ================================================================
    //  GUARDAR TODO → AJAX
    // ================================================================
    function guardarTodo() {
        var provSel  = document.getElementById('cm-proveedor');
        var provId   = provSel.value;
        var provNom  = provSel.options[provSel.selectedIndex].dataset.nombre || '';
        var catId    = document.getElementById('cm-categoria').value;
        var marcaId  = document.getElementById('cm-marca').value;
        var tamanoId = document.getElementById('cm-tamano').value;

        if (!provId) { alert('Seleccioná un Proveedor.'); document.getElementById('cm-proveedor').focus(); return; }
        if (!catId)  { alert('Seleccioná una Categoría.'); document.getElementById('cm-categoria').focus(); return; }

        var filas   = document.querySelectorAll('#cm-tbody tr');
        var productos = [];
        var hayErr  = false;

        document.querySelectorAll('.cm-input.error-field').forEach(function(el) {
            el.classList.remove('error-field');
        });

        filas.forEach(function(tr) {
            var id   = tr.id.replace('fila-', '');
            var desc = document.getElementById('desc-' + id);
            var cod  = document.getElementById('cod-'  + id);
            var cint = document.getElementById('cint-' + id);
            var cant = document.getElementById('cant-' + id);
            var cost = document.getElementById('cost-' + id);
            var prec = document.getElementById('prec-' + id);

            // Saltear filas vacías
            if (!desc.value.trim() && !cod.value.trim() && (!prec.value || parseFloat(prec.value) === 0)) return;

            var ok = true;
            if (!desc.value.trim()) { desc.classList.add('error-field'); ok = false; hayErr = true; }
            if (!prec.value || parseFloat(prec.value) <= 0) { prec.classList.add('error-field'); ok = false; hayErr = true; }

            if (ok) {
                productos.push({
                    descripcion:      desc.value.trim(),
                    codigo:           cod.value.trim(),
                    codigo_interno:   cint.value.trim(),
                    cantidad:         parseInt(cant.value) || 0,
                    costo:            parseFloat(cost.value) || 0,
                    precio:           parseFloat(prec.value),
                    proveedor_id:     provId,
                    proveedor_nombre: provNom,
                    categoria_id:     catId,
                    marca_id:         marcaId,
                    tamano_id:        tamanoId
                });
            }
        });

        if (hayErr)            { alert('Hay campos con errores (rojo). Corregí antes de guardar.'); return; }
        if (productos.length === 0) { alert('No hay productos para guardar.'); return; }
        if (!confirm('¿Guardás ' + productos.length + ' producto(s)?')) return;

        var btn     = document.getElementById('btn-guardar-todo');
        var spinner = document.getElementById('cm-spinner');
        btn.disabled = true;
        spinner.classList.add('active');

        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { action: 'cargaMasivaProductos', productos: JSON.stringify(productos) },
            success: function(resp) {
                btn.disabled = false;
                spinner.classList.remove('active');
                try {
                    mostrarResultados(JSON.parse(resp));
                } catch(e) {
                    alert('Error inesperado: ' + resp);
                }
            },
            error: function(xhr, s, err) {
                btn.disabled = false;
                spinner.classList.remove('active');
                alert('Error de comunicación: ' + err);
            }
        });
    }

    function mostrarResultados(data) {
        var ok  = data.ok  || 0;
        var err = data.err || 0;
        var log = data.log || [];

        document.getElementById('cm-total-ok').textContent  = ok;
        document.getElementById('cm-total-err').textContent = err;

        var ul = document.getElementById('cm-log-list');
        ul.innerHTML = '';
        log.forEach(function(e) {
            var li = document.createElement('li');
            li.className  = e.status === 'ok' ? 'log-ok' : 'log-err';
            li.textContent = (e.status === 'ok' ? '✔ ' : '✘ ') + e.msg;
            ul.appendChild(li);
        });

        var res = document.getElementById('cm-resultado');
        res.style.display = 'block';
        res.scrollIntoView({ behavior: 'smooth' });

        if (ok > 0) {
            document.querySelectorAll('#cm-tbody tr').forEach(function(tr) {
                var id   = tr.id.replace('fila-', '');
                var desc = document.getElementById('desc-' + id);
                if (desc && desc.value.trim() && !tr.querySelector('.error-field')) tr.remove();
            });
            actualizarNumeros();
            var rest = document.querySelectorAll('#cm-tbody tr').length;
            if (rest < 3) for (var i = rest; i < 3; i++) agregarFila();
        }
    }

    function nuevaCategoriaRapida() {
        var nombre = prompt("Ingrese el nombre de la nueva categoría:");
        if (nombre && nombre.trim() !== "") {
            $.ajax({
                url: 'ajax_categorias_marcas_tamanos.php',
                type: 'POST',
                data: { action: 'crearCategoriaRapido', nombre: nombre },
                success: function(resp) {
                    var data = JSON.parse(resp);
                    if (data.cod === '00') {
                        var sel = document.getElementById('cm-categoria');
                        var opt = document.createElement('option');
                        opt.value = data.idcategoria;
                        opt.text = data.nombre;
                        sel.add(opt);
                        sel.value = data.idcategoria;
                    } else {
                        alert(data.msg);
                    }
                },
                error: function(xhr, s, err) { alert('Error: ' + err); }
            });
        }
    }

    function nuevaMarcaRapida() {
        var nombre = prompt("Ingrese el nombre de la nueva marca:");
        if (nombre && nombre.trim() !== "") {
            $.ajax({
                url: 'ajax_categorias_marcas_tamanos.php',
                type: 'POST',
                data: { action: 'crearMarcaRapido', nombre: nombre },
                success: function(resp) {
                    var data = JSON.parse(resp);
                    if (data.cod === '00') {
                        var sel = document.getElementById('cm-marca');
                        var opt = document.createElement('option');
                        opt.value = data.idmarca;
                        opt.text = data.nombre;
                        sel.add(opt);
                        sel.value = data.idmarca;
                    } else {
                        alert(data.msg);
                    }
                },
                error: function(xhr, s, err) { alert('Error: ' + err); }
            });
        }
    }

    function nuevoTamanoRapido() {
        var nombre = prompt("Ingrese el nombre del nuevo tamaño:");
        if (nombre && nombre.trim() !== "") {
            $.ajax({
                url: 'ajax_categorias_marcas_tamanos.php',
                type: 'POST',
                data: { action: 'crearTamanoRapido', nombre: nombre },
                success: function(resp) {
                    var data = JSON.parse(resp);
                    if (data.cod === '00') {
                        var sel = document.getElementById('cm-tamano');
                        var opt = document.createElement('option');
                        opt.value = data.idtamano;
                        opt.text = data.nombre;
                        sel.add(opt);
                        sel.value = data.idtamano;
                    } else {
                        alert(data.msg);
                    }
                },
                error: function(xhr, s, err) { alert('Error: ' + err); }
            });
        }
    }

    function nuevoProveedorRapido() {
        var nombre = prompt("Ingrese el nombre del nuevo proveedor:");
        if (nombre && nombre.trim() !== "") {
            $.ajax({
                url: 'ajax_categorias_marcas_tamanos.php',
                type: 'POST',
                data: { action: 'crearProveedorRapido', nombre: nombre },
                success: function(resp) {
                    try {
                        var data = JSON.parse(resp);
                        if (data.cod === '00') {
                            var sel = document.getElementById('cm-proveedor');
                            var opt = document.createElement('option');
                            opt.value = data.codproveedor;
                            opt.text = data.nombre;
                            opt.dataset.nombre = data.nombre;
                            sel.add(opt);
                            sel.value = data.codproveedor;
                            alert('✔ Proveedor "' + data.nombre + '" creado y seleccionado.');
                        } else {
                            alert(data.msg);
                        }
                    } catch(e) {
                        alert('Error inesperado: ' + resp);
                    }
                },
                error: function(xhr, s, err) { alert('Error: ' + err); }
            });
        }
    }
    </script>
</body>
</html>
