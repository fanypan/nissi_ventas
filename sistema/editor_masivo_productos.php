<?php
ensure_session();
include "../conexion.php";

if (!isset($_SESSION['active']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2)) {
    header('location: ../');
    exit;
}

// Cargar listas para los selects
$query_cat  = mysqli_query($conection, "SELECT * FROM categoria WHERE status = 1 ORDER BY nombre ASC");
$query_prov = mysqli_query($conection, "SELECT * FROM proveedor ORDER BY proveedor ASC");
$query_marca= mysqli_query($conection, "SELECT * FROM marca WHERE status = 1 ORDER BY nombre ASC");
$query_tam  = mysqli_query($conection, "SELECT * FROM tamano WHERE status = 1 ORDER BY nombre ASC");

$categorias = [];
while ($c = mysqli_fetch_assoc($query_cat)) $categorias[] = $c;
$proveedores = [];
while ($p = mysqli_fetch_assoc($query_prov)) $proveedores[] = $p;
$marcas = [];
while ($m = mysqli_fetch_assoc($query_marca)) $marcas[] = $m;
$tamanos = [];
while ($t = mysqli_fetch_assoc($query_tam)) $tamanos[] = $t;

$query_conf = mysqli_query($conection, "SELECT moneda FROM configuracion");
$moned = '$';
if ($query_conf && mysqli_num_rows($query_conf) > 0) {
    $conf = mysqli_fetch_assoc($query_conf);
    $moned = $conf['moneda'];
}

// Filtros
$filtro_cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$filtro_marca = isset($_GET['marca']) ? (int)$_GET['marca'] : 0;
$filtro_tam = isset($_GET['tam']) ? (int)$_GET['tam'] : 0;
$filtro_prov = isset($_GET['prov']) ? (int)$_GET['prov'] : 0;
$filtro_buscar = isset($_GET['q']) ? trim($_GET['q']) : '';
$pagina = isset($_GET['pag']) ? max(1, (int)$_GET['pag']) : 1;
$por_pagina = 50;

$where = " WHERE p.status = 1 ";
if ($filtro_cat > 0) $where .= " AND p.categoria_id = $filtro_cat ";
if ($filtro_marca > 0) $where .= " AND p.marca_id = $filtro_marca ";
if ($filtro_tam > 0) $where .= " AND p.tamano_id = $filtro_tam ";
if ($filtro_prov > 0) $where .= " AND p.proveedor = $filtro_prov ";
if ($filtro_buscar !== '') {
    $buscar_esc = mysqli_real_escape_string($conection, $filtro_buscar);
    $where .= " AND (p.descripcion LIKE '%$buscar_esc%' OR p.codigo LIKE '%$buscar_esc%' OR p.codigo_interno LIKE '%$buscar_esc%') ";
}

$q_total = mysqli_query($conection, "SELECT COUNT(*) as total FROM producto p $where");
$total_productos = mysqli_fetch_assoc($q_total)['total'];
$total_paginas = max(1, ceil($total_productos / $por_pagina));
$desde = ($pagina - 1) * $por_pagina;

$query = mysqli_query($conection, "
    SELECT p.codproducto, p.descripcion, p.codigo, p.codigo_interno, p.existencia,
           p.precio, p.costo, p.categoria_id, p.marca_id, p.tamano_id, p.proveedor,
           IFNULL(c.nombre, '') as categoria_nombre,
           IFNULL(m.nombre, '') as marca_nombre,
           IFNULL(t.nombre, '') as tamano_nombre,
           IFNULL(pr.proveedor, '') as proveedor_nombre
    FROM producto p
    LEFT JOIN categoria c ON p.categoria_id = c.idcategoria
    LEFT JOIN marca m ON p.marca_id = m.idmarca
    LEFT JOIN tamano t ON p.tamano_id = t.idtamano
    LEFT JOIN proveedor pr ON p.proveedor = pr.codproveedor
    $where
    ORDER BY p.descripcion ASC
    LIMIT $desde, $por_pagina
");

$productos = [];
while ($row = mysqli_fetch_assoc($query)) {
    $productos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">
    <title>Editor Masivo de Productos</title>
    <?php include "includes/scripts.php"; ?>
    <style>
        .em-wrapper { padding: 15px 20px; }

        .em-header {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 18px;
            border-bottom: 2px solid #2980b9; padding-bottom: 10px;
        }
        .em-header h1 { font-size: 22px; color: #2980b9; margin: 0; }
        .em-badge {
            background: #2980b9; color: #fff; border-radius: 50%;
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }

        /* Stats */
        .em-stats {
            display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;
        }
        .em-stat-card {
            flex: 1; min-width: 140px; border-radius: 10px; padding: 16px 20px;
            text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,.08); color: #fff;
        }
        .em-stat-card.blue   { background: linear-gradient(135deg, #3498db, #2980b9); }
        .em-stat-card.green  { background: linear-gradient(135deg, #27ae60, #219a52); }
        .em-stat-card.orange { background: linear-gradient(135deg, #e67e22, #d35400); }
        .em-stat-card .stat-num { font-size: 28px; font-weight: bold; }
        .em-stat-card .stat-lbl { font-size: 11px; letter-spacing: .5px; margin-top: 4px; opacity: .9; }

        /* Filters */
        .em-filters {
            background: #fff; border: 1px solid #d0dde6; border-radius: 8px;
            padding: 14px 18px; margin-bottom: 16px;
            box-shadow: 0 2px 6px rgba(0,0,0,.05);
            display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;
        }
        .em-filters .filter-group { display: flex; flex-direction: column; }
        .em-filters label {
            font-size: 10px; font-weight: bold; color: #555;
            text-transform: uppercase; letter-spacing: .5px; margin-bottom: 3px;
        }
        .em-filters select, .em-filters input[type="text"] {
            padding: 6px 10px; border: 1px solid #bbb; border-radius: 5px; font-size: 13px;
            min-width: 140px;
        }
        .em-filters .btn-filtrar {
            background: #2980b9; color: #fff; border: none; border-radius: 5px;
            padding: 7px 18px; font-size: 13px; cursor: pointer;
            display: flex; align-items: center; gap: 6px;
        }
        .em-filters .btn-filtrar:hover { background: #2471a3; }
        .em-filters .btn-limpiar {
            background: #95a5a6; color: #fff; border: none; border-radius: 5px;
            padding: 7px 14px; font-size: 13px; cursor: pointer;
        }
        .em-filters .btn-limpiar:hover { background: #7f8c8d; }

        /* Table */
        .em-table-wrap {
            background: #fff; border: 1px solid #d0dde6; border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,.05); overflow-x: auto;
        }
        #em-tabla { width: 100%; border-collapse: collapse; font-size: 13px; }
        #em-tabla thead tr { background: #2c3e50; color: #fff; }
        #em-tabla thead th {
            padding: 9px 8px; text-align: left; font-size: 11px;
            font-weight: bold; letter-spacing: .5px; text-transform: uppercase;
            position: sticky; top: 0; z-index: 2;
        }
        #em-tabla tbody tr { border-bottom: 1px solid #eee; transition: background .15s; }
        #em-tabla tbody tr:nth-child(even) { background: #f8fafb; }
        #em-tabla tbody tr:hover { background: #eaf4fb; }
        #em-tabla tbody tr.row-modified { background: #fef9e7; }
        #em-tabla tbody tr.row-saved { animation: em-saved .6s; }
        #em-tabla td { padding: 4px 5px; vertical-align: middle; }

        @keyframes em-saved {
            0% { background: #d5f5e3; } 100% { background: transparent; }
        }

        /* Editable inputs */
        .em-edit {
            width: 100%; padding: 4px 6px; border: 1px solid transparent; border-radius: 4px;
            font-size: 13px; font-family: arial; background: transparent;
            transition: border-color .2s, background .2s;
        }
        .em-edit:hover { border-color: #bbb; background: #fff; }
        .em-edit:focus { border-color: #2980b9; background: #fff; outline: none; box-shadow: 0 0 0 2px rgba(41,128,185,.15); }
        .em-edit.modified { border-color: #e67e22; background: #fef9e7; }
        .em-edit-num { text-align: right; width: 85px; }
        .em-edit-desc { min-width: 180px; }
        .em-edit-cod { min-width: 110px; }

        .em-select {
            width: 100%; padding: 4px 6px; border: 1px solid transparent; border-radius: 4px;
            font-size: 12px; background: transparent; cursor: pointer;
            transition: border-color .2s, background .2s;
        }
        .em-select:hover { border-color: #bbb; background: #fff; }
        .em-select:focus { border-color: #2980b9; background: #fff; outline: none; }
        .em-select.modified { border-color: #e67e22; background: #fef9e7; }

        /* Row actions */
        .btn-save-row {
            background: #27ae60; color: #fff; border: none; border-radius: 4px;
            padding: 4px 10px; font-size: 11px; cursor: pointer;
            display: none; align-items: center; gap: 4px;
            transition: background .2s;
        }
        .btn-save-row:hover { background: #219a52; }
        .btn-save-row.visible { display: inline-flex; }

        .btn-del-prod {
            background: none; border: none; color: #e74c3c; cursor: pointer;
            font-size: 14px; padding: 4px 6px; border-radius: 4px;
            transition: background .15s;
        }
        .btn-del-prod:hover { background: #fde8e8; }

        .em-id-cell {
            font-size: 11px; color: #888; text-align: center; font-weight: bold;
        }

        /* Pagination */
        .em-pagination {
            display: flex; justify-content: center; align-items: center; gap: 6px;
            padding: 16px; flex-wrap: wrap;
        }
        .em-pagination a, .em-pagination span {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 32px; height: 32px; border-radius: 5px;
            font-size: 13px; text-decoration: none; padding: 0 10px;
        }
        .em-pagination a {
            background: #fff; color: #2980b9; border: 1px solid #d0dde6;
            transition: background .15s;
        }
        .em-pagination a:hover { background: #eaf4fb; }
        .em-pagination .current {
            background: #2980b9; color: #fff; border: 1px solid #2980b9; font-weight: bold;
        }
        .em-pagination .info {
            font-size: 12px; color: #888; margin: 0 8px;
        }

        /* Toast */
        .em-toast {
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            padding: 14px 22px; border-radius: 8px; color: #fff;
            font-size: 14px; box-shadow: 0 4px 16px rgba(0,0,0,.2);
            display: none; animation: em-toast-in .3s;
        }
        .em-toast.success { background: #27ae60; }
        .em-toast.error { background: #e74c3c; }
        @keyframes em-toast-in { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }

        /* Counter */
        .em-cambios-counter {
            position: fixed; bottom: 20px; right: 20px; z-index: 9998;
            background: #e67e22; color: #fff; border-radius: 30px;
            padding: 10px 22px; font-size: 14px; font-weight: bold;
            box-shadow: 0 4px 16px rgba(0,0,0,.2);
            display: none; align-items: center; gap: 10px;
            cursor: pointer; transition: background .2s;
        }
        .em-cambios-counter:hover { background: #d35400; }
        .em-cambios-counter.active { display: flex; }

        @media (max-width: 768px) {
            .em-stats { flex-direction: column; }
            #em-tabla { font-size: 11px; }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>
    <section id="container">
        <div class="em-wrapper">

            <div class="em-toast" id="em-toast"></div>

            <!-- Floating save counter -->
            <div class="em-cambios-counter" id="em-cambios-counter" onclick="guardarTodosCambios()">
                <i class="fas fa-save"></i>
                <span id="em-cambios-num">0</span> cambios pendientes — Click para guardar
            </div>

            <!-- HEADER -->
            <div class="em-header">
                <div class="em-badge"><i class="fas fa-edit"></i></div>
                <h1>Editor Masivo de Productos</h1>
            </div>

            <!-- STATS -->
            <div class="em-stats">
                <div class="em-stat-card blue">
                    <div class="stat-num"><?= number_format($total_productos, 0, ',', '.') ?></div>
                    <div class="stat-lbl">Productos Encontrados</div>
                </div>
                <div class="em-stat-card green">
                    <div class="stat-num"><?= $pagina ?> / <?= $total_paginas ?></div>
                    <div class="stat-lbl">Página Actual</div>
                </div>
                <div class="em-stat-card orange">
                    <div class="stat-num"><?= $por_pagina ?></div>
                    <div class="stat-lbl">Por Página</div>
                </div>
            </div>

            <!-- FILTERS -->
            <form class="em-filters" method="GET">
                <div class="filter-group">
                    <label><i class="fas fa-search"></i> Buscar</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($filtro_buscar) ?>" placeholder="Nombre, código...">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-th-list"></i> Categoría</label>
                    <select name="cat">
                        <option value="0">-- Todas --</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['idcategoria'] ?>" <?= $filtro_cat == $c['idcategoria'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-tag"></i> Marca</label>
                    <select name="marca">
                        <option value="0">-- Todas --</option>
                        <?php foreach ($marcas as $m): ?>
                            <option value="<?= $m['idmarca'] ?>" <?= $filtro_marca == $m['idmarca'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-ruler-combined"></i> Tamaño</label>
                    <select name="tam">
                        <option value="0">-- Todos --</option>
                        <?php foreach ($tamanos as $t): ?>
                            <option value="<?= $t['idtamano'] ?>" <?= $filtro_tam == $t['idtamano'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-building"></i> Proveedor</label>
                    <select name="prov">
                        <option value="0">-- Todos --</option>
                        <?php foreach ($proveedores as $p): ?>
                            <option value="<?= $p['codproveedor'] ?>" <?= $filtro_prov == $p['codproveedor'] ? 'selected' : '' ?>><?= htmlspecialchars($p['proveedor']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-filtrar"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="editor_masivo_productos.php" class="btn-limpiar" style="text-decoration:none;">Limpiar</a>
            </form>

            <!-- TABLE -->
            <div class="em-table-wrap">
                <table id="em-tabla">
                    <thead>
                        <tr>
                            <th style="width:40px;">ID</th>
                            <th>Descripción</th>
                            <th>Cód. Barras</th>
                            <th>Cód. Interno</th>
                            <th style="width:70px;">Stock</th>
                            <th style="width:85px;">Costo</th>
                            <th style="width:85px;">Precio</th>
                            <th>Categoría</th>
                            <th>Marca</th>
                            <th>Tamaño</th>
                            <th style="width:90px; text-align:center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($productos) === 0): ?>
                        <tr><td colspan="11" style="text-align:center; padding:30px; color:#888;">No se encontraron productos con esos filtros.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($productos as $prod): ?>
                        <tr id="row-<?= $prod['codproducto'] ?>" data-id="<?= $prod['codproducto'] ?>">
                            <td class="em-id-cell"><?= $prod['codproducto'] ?></td>
                            <td><input class="em-edit em-edit-desc" data-field="descripcion" data-original="<?= htmlspecialchars($prod['descripcion']) ?>" value="<?= htmlspecialchars($prod['descripcion']) ?>" onchange="marcarCambio(this)"></td>
                            <td><input class="em-edit em-edit-cod" data-field="codigo" data-original="<?= htmlspecialchars($prod['codigo'] ?? '') ?>" value="<?= htmlspecialchars($prod['codigo'] ?? '') ?>" onchange="marcarCambio(this)"></td>
                            <td><input class="em-edit" data-field="codigo_interno" data-original="<?= htmlspecialchars($prod['codigo_interno'] ?? '') ?>" value="<?= htmlspecialchars($prod['codigo_interno'] ?? '') ?>" style="width:90px;" onchange="marcarCambio(this)"></td>
                            <td><input class="em-edit em-edit-num" type="number" data-field="existencia" data-original="<?= $prod['existencia'] ?>" value="<?= $prod['existencia'] ?>" min="0" onchange="marcarCambio(this)"></td>
                            <td><input class="em-edit em-edit-num" type="number" data-field="costo" data-original="<?= $prod['costo'] ?>" value="<?= $prod['costo'] ?>" min="0" step="0.01" onchange="marcarCambio(this)"></td>
                            <td><input class="em-edit em-edit-num" type="number" data-field="precio" data-original="<?= $prod['precio'] ?>" value="<?= $prod['precio'] ?>" min="0" step="0.01" onchange="marcarCambio(this)"></td>
                            <td>
                                <select class="em-select" data-field="categoria_id" data-original="<?= $prod['categoria_id'] ?? '' ?>" onchange="marcarCambio(this)">
                                    <option value="">Sin categoría</option>
                                    <?php foreach ($categorias as $c): ?>
                                        <option value="<?= $c['idcategoria'] ?>" <?= $prod['categoria_id'] == $c['idcategoria'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select class="em-select" data-field="marca_id" data-original="<?= $prod['marca_id'] ?? '' ?>" onchange="marcarCambio(this)">
                                    <option value="">Sin marca</option>
                                    <?php foreach ($marcas as $m): ?>
                                        <option value="<?= $m['idmarca'] ?>" <?= $prod['marca_id'] == $m['idmarca'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select class="em-select" data-field="tamano_id" data-original="<?= $prod['tamano_id'] ?? '' ?>" onchange="marcarCambio(this)">
                                    <option value="">Sin tamaño</option>
                                    <?php foreach ($tamanos as $t): ?>
                                        <option value="<?= $t['idtamano'] ?>" <?= $prod['tamano_id'] == $t['idtamano'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="text-align:center; white-space:nowrap;">
                                <button class="btn-save-row" id="btn-save-<?= $prod['codproducto'] ?>" onclick="guardarFila(<?= $prod['codproducto'] ?>)" title="Guardar cambios">
                                    <i class="fas fa-check"></i> Guardar
                                </button>
                                <button class="btn-del-prod" onclick="eliminarProducto(<?= $prod['codproducto'] ?>)" title="Eliminar producto">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if ($total_paginas > 1): ?>
            <div class="em-pagination">
                <?php
                $params = $_GET;
                if ($pagina > 1) {
                    $params['pag'] = 1;
                    echo '<a href="?' . http_build_query($params) . '"><i class="fas fa-angle-double-left"></i></a>';
                    $params['pag'] = $pagina - 1;
                    echo '<a href="?' . http_build_query($params) . '"><i class="fas fa-angle-left"></i></a>';
                }

                $rango = 3;
                $inicio = max(1, $pagina - $rango);
                $fin = min($total_paginas, $pagina + $rango);

                for ($i = $inicio; $i <= $fin; $i++) {
                    $params['pag'] = $i;
                    if ($i == $pagina) {
                        echo '<span class="current">' . $i . '</span>';
                    } else {
                        echo '<a href="?' . http_build_query($params) . '">' . $i . '</a>';
                    }
                }

                if ($pagina < $total_paginas) {
                    $params['pag'] = $pagina + 1;
                    echo '<a href="?' . http_build_query($params) . '"><i class="fas fa-angle-right"></i></a>';
                    $params['pag'] = $total_paginas;
                    echo '<a href="?' . http_build_query($params) . '"><i class="fas fa-angle-double-right"></i></a>';
                }
                ?>
                <span class="info"><?= number_format($total_productos, 0, ',', '.') ?> productos</span>
            </div>
            <?php endif; ?>

        </div>
    </section>

    <?php include "includes/footer.php"; ?>

    <script>
    var cambiosPendientes = {};

    // ================================================================
    //  MARCAR CAMBIO EN UN CAMPO
    // ================================================================
    function marcarCambio(el) {
        var row = el.closest('tr');
        var id = row.dataset.id;
        var field = el.dataset.field;
        var original = el.dataset.original;
        var valor = el.value;

        // Check if value changed from original
        if (valor !== original) {
            el.classList.add('modified');
            row.classList.add('row-modified');
            if (!cambiosPendientes[id]) cambiosPendientes[id] = {};
            cambiosPendientes[id][field] = valor;
        } else {
            el.classList.remove('modified');
            delete (cambiosPendientes[id] || {})[field];
            if (cambiosPendientes[id] && Object.keys(cambiosPendientes[id]).length === 0) {
                delete cambiosPendientes[id];
                row.classList.remove('row-modified');
            }
        }

        // Show/hide save button
        var btn = document.getElementById('btn-save-' + id);
        if (cambiosPendientes[id] && Object.keys(cambiosPendientes[id]).length > 0) {
            btn.classList.add('visible');
        } else {
            btn.classList.remove('visible');
        }

        actualizarContadorCambios();
    }

    // ================================================================
    //  COUNTER
    // ================================================================
    function actualizarContadorCambios() {
        var total = Object.keys(cambiosPendientes).length;
        var counter = document.getElementById('em-cambios-counter');
        var num = document.getElementById('em-cambios-num');
        num.textContent = total;
        if (total > 0) {
            counter.classList.add('active');
        } else {
            counter.classList.remove('active');
        }
    }

    // ================================================================
    //  GUARDAR UNA FILA
    // ================================================================
    function guardarFila(id) {
        if (!cambiosPendientes[id]) return;

        var datos = cambiosPendientes[id];
        datos.action = 'editarProductoMasivo';
        datos.codproducto = id;

        $.ajax({
            url: 'ajax_categorias_marcas_tamanos.php',
            type: 'POST',
            data: datos,
            success: function(resp) {
                try {
                    var data = JSON.parse(resp);
                    if (data.cod === '00') {
                        showToast('✔ Producto ID ' + id + ' actualizado', 'success');
                        // Update originals
                        var row = document.getElementById('row-' + id);
                        row.querySelectorAll('.em-edit, .em-select').forEach(function(el) {
                            var field = el.dataset.field;
                            if (datos[field] !== undefined) {
                                el.dataset.original = el.value;
                                el.classList.remove('modified');
                            }
                        });
                        row.classList.remove('row-modified');
                        row.classList.add('row-saved');
                        setTimeout(function() { row.classList.remove('row-saved'); }, 600);
                        delete cambiosPendientes[id];
                        document.getElementById('btn-save-' + id).classList.remove('visible');
                        actualizarContadorCambios();
                    } else {
                        showToast('✘ Error: ' + data.msg, 'error');
                    }
                } catch(e) {
                    showToast('Error inesperado', 'error');
                }
            },
            error: function() { showToast('Error de comunicación', 'error'); }
        });
    }

    // ================================================================
    //  GUARDAR TODOS LOS CAMBIOS PENDIENTES
    // ================================================================
    function guardarTodosCambios() {
        var ids = Object.keys(cambiosPendientes);
        if (ids.length === 0) return;

        var total = ids.length;
        var procesados = 0;
        var exitosos = 0;

        ids.forEach(function(id) {
            var datos = Object.assign({}, cambiosPendientes[id]);
            datos.action = 'editarProductoMasivo';
            datos.codproducto = id;

            $.ajax({
                url: 'ajax_categorias_marcas_tamanos.php',
                type: 'POST',
                data: datos,
                success: function(resp) {
                    try {
                        var data = JSON.parse(resp);
                        if (data.cod === '00') {
                            exitosos++;
                            var row = document.getElementById('row-' + id);
                            if (row) {
                                row.querySelectorAll('.em-edit, .em-select').forEach(function(el) {
                                    el.dataset.original = el.value;
                                    el.classList.remove('modified');
                                });
                                row.classList.remove('row-modified');
                                row.classList.add('row-saved');
                                setTimeout(function() { row.classList.remove('row-saved'); }, 600);
                            }
                            delete cambiosPendientes[id];
                            var btn = document.getElementById('btn-save-' + id);
                            if (btn) btn.classList.remove('visible');
                        }
                    } catch(e) {}
                    procesados++;
                    if (procesados >= total) {
                        showToast('✔ ' + exitosos + ' de ' + total + ' productos actualizados', 'success');
                        actualizarContadorCambios();
                    }
                },
                error: function() {
                    procesados++;
                    if (procesados >= total) {
                        showToast('✔ ' + exitosos + ' de ' + total + ' productos actualizados', exitosos > 0 ? 'success' : 'error');
                        actualizarContadorCambios();
                    }
                }
            });
        });
    }

    // ================================================================
    //  ELIMINAR PRODUCTO
    // ================================================================
    function eliminarProducto(id) {
        if (!confirm('¿Eliminar el producto ID ' + id + '?\n\nSe eliminará permanentemente de la base de datos.')) return;

        $.ajax({
            url: 'ajax_categorias_marcas_tamanos.php',
            type: 'POST',
            data: { action: 'eliminarProductoMasivo', codproducto: id },
            success: function(resp) {
                try {
                    var data = JSON.parse(resp);
                    if (data.cod === '00') {
                        showToast('✔ Producto eliminado', 'success');
                        var row = document.getElementById('row-' + id);
                        if (row) {
                            row.style.transition = 'opacity .4s';
                            row.style.opacity = '0';
                            setTimeout(function() { row.remove(); }, 400);
                        }
                        delete cambiosPendientes[id];
                        actualizarContadorCambios();
                    } else {
                        showToast('✘ ' + data.msg, 'error');
                    }
                } catch(e) {
                    showToast('Error inesperado', 'error');
                }
            },
            error: function() { showToast('Error de comunicación', 'error'); }
        });
    }

    // ================================================================
    //  TOAST
    // ================================================================
    function showToast(msg, tipo) {
        var toast = document.getElementById('em-toast');
        toast.textContent = msg;
        toast.className = 'em-toast ' + (tipo || 'success');
        toast.style.display = 'block';
        setTimeout(function() { toast.style.display = 'none'; }, 3500);
    }

    // Warn before leaving with unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (Object.keys(cambiosPendientes).length > 0) {
            e.preventDefault();
            e.returnValue = 'Tenés cambios sin guardar. ¿Seguro querés salir?';
        }
    });
    </script>
</body>
</html>
