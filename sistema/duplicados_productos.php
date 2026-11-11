<?php
ensure_session();
include "../conexion.php";

// Solo admins y supervisores
if (!isset($_SESSION['active']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2)) {
    header('location: ../');
    exit;
}

// Consultar duplicados exactos (mismo nombre + categoría + marca + tamaño)
$query_dup = mysqli_query($conection, "
    SELECT LOWER(TRIM(p.descripcion)) as desc_norm,
           IFNULL(p.categoria_id, 0) as cat_id,
           IFNULL(p.marca_id, 0) as mar_id,
           IFNULL(p.tamano_id, 0) as tam_id,
           IFNULL(c.nombre, 'Sin categoría') as cat_nombre,
           IFNULL(m.nombre, 'Sin marca') as mar_nombre,
           IFNULL(t.nombre, 'Sin tamaño') as tam_nombre,
           COUNT(*) as cantidad,
           GROUP_CONCAT(p.codproducto ORDER BY p.codproducto SEPARATOR ',') as ids
    FROM producto p
    LEFT JOIN categoria c ON p.categoria_id = c.idcategoria
    LEFT JOIN marca m ON p.marca_id = m.idmarca
    LEFT JOIN tamano t ON p.tamano_id = t.idtamano
    WHERE p.status = 1
    GROUP BY LOWER(TRIM(p.descripcion)), IFNULL(p.categoria_id, 0), IFNULL(p.marca_id, 0), IFNULL(p.tamano_id, 0)
    HAVING COUNT(*) > 1
    ORDER BY COUNT(*) DESC
");

$grupos = [];
$total_filas_afectadas = 0;
$total_grupos = 0;

while ($row = mysqli_fetch_assoc($query_dup)) {
    $ids = explode(',', $row['ids']);
    $productos = [];
    foreach ($ids as $id) {
        $qp = mysqli_query($conection, "
            SELECT p.codproducto, p.descripcion, p.codigo, p.codigo_interno, p.existencia, 
                   p.precio, p.costo, p.categoria_id, p.marca_id, p.tamano_id, p.proveedor, p.foto,
                   IFNULL(c.nombre, 'Sin categoría') as categoria_nombre,
                   IFNULL(m.nombre, 'Sin marca') as marca_nombre,
                   IFNULL(t.nombre, 'Sin tamaño') as tamano_nombre,
                   IFNULL(pr.proveedor, 'Sin proveedor') as proveedor_nombre
            FROM producto p
            LEFT JOIN categoria c ON p.categoria_id = c.idcategoria
            LEFT JOIN marca m ON p.marca_id = m.idmarca
            LEFT JOIN tamano t ON p.tamano_id = t.idtamano
            LEFT JOIN proveedor pr ON p.proveedor = pr.codproveedor
            WHERE p.codproducto = " . intval($id) . "
        ");
        if ($qp && mysqli_num_rows($qp) > 0) {
            $productos[] = mysqli_fetch_assoc($qp);
        }
    }
    $grupos[] = [
        'nombre' => $row['desc_norm'],
        'cantidad' => $row['cantidad'],
        'productos' => $productos,
        'categoria' => $row['cat_nombre'],
        'marca' => $row['mar_nombre'],
        'tamano' => $row['tam_nombre']
    ];
    $total_filas_afectadas += $row['cantidad'];
    $total_grupos++;
}

$sobrantes = $total_filas_afectadas - $total_grupos; // filas que se pueden eliminar

$query_conf = mysqli_query($conection, "SELECT moneda FROM configuracion");
$moned = '$';
if ($query_conf && mysqli_num_rows($query_conf) > 0) {
    $conf = mysqli_fetch_assoc($query_conf);
    $moned = $conf['moneda'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">
    <title>Gestión de Duplicados</title>
    <?php include "includes/scripts.php"; ?>
    <style>
        /* ===== DUPLICADOS MODULE ===== */
        .dup-wrapper { padding: 15px 20px; }

        .dup-header {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 18px;
            border-bottom: 2px solid #c0392b; padding-bottom: 10px;
        }
        .dup-header h1 { font-size: 22px; color: #c0392b; margin: 0; }
        .dup-badge {
            background: #c0392b; color: #fff; border-radius: 50%;
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }

        /* Stats cards */
        .dup-stats {
            display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;
        }
        .dup-stat-card {
            flex: 1; min-width: 160px; border-radius: 10px; padding: 18px 22px;
            text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }
        .dup-stat-card.red    { background: linear-gradient(135deg, #e74c3c, #c0392b); color: #fff; }
        .dup-stat-card.orange { background: linear-gradient(135deg, #e67e22, #d35400); color: #fff; }
        .dup-stat-card.blue   { background: linear-gradient(135deg, #3498db, #2980b9); color: #fff; }
        .dup-stat-card.green  { background: linear-gradient(135deg, #27ae60, #219a52); color: #fff; }
        .dup-stat-card .stat-num { font-size: 32px; font-weight: bold; }
        .dup-stat-card .stat-lbl { font-size: 12px; letter-spacing: .5px; margin-top: 4px; opacity: .9; }

        /* Toolbar */
        .dup-toolbar {
            display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; align-items: center;
            background: #fff; border: 1px solid #d0dde6; border-radius: 8px;
            padding: 12px 16px; box-shadow: 0 2px 6px rgba(0,0,0,.05);
        }
        .dup-toolbar label { font-size: 12px; color: #555; font-weight: bold; text-transform: uppercase; }
        .dup-toolbar select {
            padding: 6px 10px; border: 1px solid #bbb; border-radius: 5px; font-size: 13px;
        }
        .dup-toolbar input[type="text"] {
            padding: 6px 10px; border: 1px solid #bbb; border-radius: 5px; font-size: 13px;
            min-width: 200px;
        }

        .btn-fusionar-todos {
            background: #c0392b; color: #fff; border: none; border-radius: 6px;
            padding: 9px 20px; font-size: 13px; cursor: pointer;
            display: flex; align-items: center; gap: 7px;
            transition: background .2s; font-family: arial; letter-spacing: .5px; font-weight: bold;
            margin-left: auto;
        }
        .btn-fusionar-todos:hover { background: #a93226; }
        .btn-fusionar-todos:disabled { background: #bbb; cursor: not-allowed; }

        /* Group card */
        .dup-group {
            background: #fff; border: 1px solid #d0dde6; border-radius: 8px;
            margin-bottom: 16px; box-shadow: 0 2px 6px rgba(0,0,0,.05);
            overflow: hidden;
        }
        .dup-group.fusionado { opacity: .4; pointer-events: none; }
        .dup-group-header {
            background: #f8f9fa; border-bottom: 1px solid #e0e0e0;
            padding: 12px 16px; display: flex; align-items: center; gap: 12px;
            cursor: pointer; transition: background .15s;
        }
        .dup-group-header:hover { background: #eef3f7; }
        .dup-group-header .group-icon {
            background: #e74c3c; color: #fff; border-radius: 50%;
            width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: bold; flex-shrink: 0;
        }
        .dup-group-header .group-name {
            font-size: 14px; font-weight: bold; color: #333; flex: 1;
        }
        .dup-group-header .group-count {
            background: #e74c3c; color: #fff; border-radius: 12px;
            padding: 2px 10px; font-size: 12px; font-weight: bold;
        }
        .dup-group-header .group-toggle {
            color: #888; font-size: 14px; transition: transform .2s;
        }
        .dup-group-header.collapsed .group-toggle { transform: rotate(-90deg); }

        .dup-group-body { padding: 0; }
        .dup-group-body.collapsed { display: none; }

        /* Product table inside group */
        .dup-prod-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .dup-prod-table thead tr { background: #34495e; color: #fff; }
        .dup-prod-table thead th {
            padding: 8px 10px; text-align: left; font-size: 11px;
            font-weight: bold; letter-spacing: .5px; text-transform: uppercase;
        }
        .dup-prod-table tbody tr { border-bottom: 1px solid #eee; transition: background .15s; }
        .dup-prod-table tbody tr:hover { background: #f5f8fa; }
        .dup-prod-table tbody tr.original-row { background: #eafaf1; }
        .dup-prod-table tbody tr.original-row:hover { background: #d5f5e3; }
        .dup-prod-table tbody tr.selected-row { background: #fde8e8; }
        .dup-prod-table tbody tr.selected-row:hover { background: #f9d0d0; }
        .dup-prod-table td { padding: 8px 10px; vertical-align: middle; }

        /* Checkboxes */
        .dup-check { width: 18px; height: 18px; cursor: pointer; accent-color: #e74c3c; }
        .dup-check-all { width: 16px; height: 16px; cursor: pointer; accent-color: #e74c3c; }
        .dup-sel-count {
            background: #e74c3c; color: #fff; border-radius: 12px;
            padding: 2px 10px; font-size: 12px; font-weight: bold;
            display: none;
        }
        .dup-sel-count.active { display: inline-block; }

        .badge-original {
            background: #27ae60; color: #fff; border-radius: 4px;
            padding: 2px 8px; font-size: 10px; font-weight: bold; letter-spacing: .5px;
        }
        .badge-duplicado {
            background: #e74c3c; color: #fff; border-radius: 4px;
            padding: 2px 8px; font-size: 10px; font-weight: bold; letter-spacing: .5px;
        }

        /* Group actions */
        .dup-group-actions {
            padding: 10px 16px; background: #f8f9fa; border-top: 1px solid #e0e0e0;
            display: flex; gap: 8px; align-items: center; flex-wrap: wrap;
        }
        .btn-fusionar {
            background: #27ae60; color: #fff; border: none; border-radius: 5px;
            padding: 7px 16px; font-size: 12px; cursor: pointer;
            display: flex; align-items: center; gap: 6px;
            transition: background .2s; font-family: arial;
        }
        .btn-fusionar:hover { background: #219a52; }
        .btn-eliminar-dup {
            background: #e74c3c; color: #fff; border: none; border-radius: 5px;
            padding: 7px 16px; font-size: 12px; cursor: pointer;
            display: flex; align-items: center; gap: 6px;
            transition: background .2s; font-family: arial;
        }
        .btn-eliminar-dup:hover { background: #c0392b; }
        .btn-eliminar-dup:disabled { background: #bbb; cursor: not-allowed; }
        .btn-eliminar-sel {
            background: #8e44ad; color: #fff; border: none; border-radius: 5px;
            padding: 7px 16px; font-size: 12px; cursor: pointer;
            display: flex; align-items: center; gap: 6px;
            transition: background .2s; font-family: arial;
        }
        .btn-eliminar-sel:hover { background: #732d91; }
        .btn-eliminar-sel:disabled { background: #bbb; cursor: not-allowed; }
        .dup-action-info {
            font-size: 11px; color: #888; margin-left: auto; font-style: italic;
        }

        /* Spinner/progress */
        .dup-spinner { display: none; align-items: center; gap: 8px; font-size: 13px; color: #c0392b; }
        .dup-spinner.active { display: flex; }
        .dup-spin-icon {
            width: 18px; height: 18px; border: 2px solid #c0392b;
            border-top-color: transparent; border-radius: 50%;
            animation: dup-spin .7s linear infinite;
        }
        @keyframes dup-spin { to { transform: rotate(360deg); } }

        /* Result toast */
        .dup-toast {
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            padding: 14px 22px; border-radius: 8px; color: #fff;
            font-size: 14px; box-shadow: 0 4px 16px rgba(0,0,0,.2);
            display: none; animation: dup-toast-in .3s;
        }
        .dup-toast.success { background: #27ae60; }
        .dup-toast.error   { background: #e74c3c; }
        @keyframes dup-toast-in { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        /* Empty state */
        .dup-empty {
            text-align: center; padding: 60px 20px; color: #888;
        }
        .dup-empty i { font-size: 48px; color: #27ae60; margin-bottom: 12px; }
        .dup-empty h2 { color: #27ae60; margin-bottom: 8px; }

        /* Responsive */
        @media (max-width: 768px) {
            .dup-stats { flex-direction: column; }
            .dup-prod-table { font-size: 11px; }
            .dup-prod-table td, .dup-prod-table th { padding: 5px 6px; }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>
    <section id="container">
        <div class="dup-wrapper">

            <!-- Toast notification -->
            <div class="dup-toast" id="dup-toast"></div>

            <!-- HEADER -->
            <div class="dup-header">
                <div class="dup-badge"><i class="fas fa-clone"></i></div>
                <h1>Gestión de Productos Duplicados</h1>
            </div>

            <?php if ($total_grupos === 0): ?>
                <!-- NO DUPLICATES -->
                <div class="dup-empty">
                    <i class="fas fa-check-circle"></i>
                    <h2>¡Sin Duplicados!</h2>
                    <p>No se encontraron productos con nombres duplicados. Todo está en orden.</p>
                </div>
            <?php else: ?>

            <!-- STATS -->
            <div class="dup-stats">
                <div class="dup-stat-card red">
                    <div class="stat-num" id="stat-grupos"><?= $total_grupos ?></div>
                    <div class="stat-lbl">Grupos Duplicados</div>
                </div>
                <div class="dup-stat-card orange">
                    <div class="stat-num" id="stat-filas"><?= $total_filas_afectadas ?></div>
                    <div class="stat-lbl">Filas Afectadas</div>
                </div>
                <div class="dup-stat-card blue">
                    <div class="stat-num" id="stat-sobrantes"><?= $sobrantes ?></div>
                    <div class="stat-lbl">Productos a Eliminar</div>
                </div>
                <div class="dup-stat-card green">
                    <div class="stat-num">4,343</div>
                    <div class="stat-lbl">Total Productos Activos</div>
                </div>
            </div>

            <!-- TOOLBAR -->
            <div class="dup-toolbar">
                <div>
                    <label>Buscar:</label>
                    <input type="text" id="dup-buscar" placeholder="Filtrar por nombre..." oninput="filtrarGrupos()">
                </div>
                <div>
                    <label>Repeticiones:</label>
                    <select id="dup-filtro-cant" onchange="filtrarGrupos()">
                        <option value="0">Todos</option>
                        <option value="5">5+ repeticiones</option>
                        <option value="3">3+ repeticiones</option>
                        <option value="2">Solo 2 repeticiones</option>
                    </select>
                </div>
                <button class="btn-fusionar-todos" onclick="fusionarTodos()">
                    <i class="fas fa-compress-arrows-alt"></i> Fusionar Todos (Sumar Stocks y Eliminar)
                </button>
            </div>

            <!-- GROUPS -->
            <div id="dup-groups-container">
            <?php foreach ($grupos as $idx => $grupo): ?>
                <div class="dup-group" id="grupo-<?= $idx ?>" data-nombre="<?= htmlspecialchars($grupo['nombre']) ?>" data-cantidad="<?= $grupo['cantidad'] ?>">
                    <!-- Group Header -->
                    <div class="dup-group-header" onclick="toggleGrupo(<?= $idx ?>)">
                        <div class="group-icon"><?= $grupo['cantidad'] ?></div>
                        <div class="group-name">
                            <?= htmlspecialchars(ucfirst($grupo['nombre'])) ?>
                            <span style="font-size:11px; font-weight:normal; color:#666; margin-left:8px;">
                                <i class="fas fa-th-list" style="color:#3498db;"></i> <?= htmlspecialchars($grupo['categoria']) ?>
                                · <i class="fas fa-tag" style="color:#e67e22;"></i> <?= htmlspecialchars($grupo['marca']) ?>
                                · <i class="fas fa-ruler-combined" style="color:#9b59b6;"></i> <?= htmlspecialchars($grupo['tamano']) ?>
                            </span>
                        </div>
                        <span class="group-count"><?= $grupo['cantidad'] ?> copias</span>
                        <i class="fas fa-chevron-down group-toggle"></i>
                    </div>

                    <!-- Group Body -->
                    <div class="dup-group-body collapsed" id="grupo-body-<?= $idx ?>">
                        <table class="dup-prod-table">
                            <thead>
                                <tr>
                                    <th style="width:30px;"><input type="checkbox" class="dup-check-all" data-grupo="<?= $idx ?>" onclick="toggleSeleccionGrupo(<?= $idx ?>)" title="Seleccionar todos los duplicados"></th>
                                    <th style="width:30px;"></th>
                                    <th>ID</th>
                                    <th>Descripción</th>
                                    <th>Código Barras</th>
                                    <th>Stock</th>
                                    <th>Costo (<?= $moned ?>)</th>
                                    <th>Precio (<?= $moned ?>)</th>
                                    <th>Categoría</th>
                                    <th>Marca</th>
                                    <th>Proveedor</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($grupo['productos'] as $pidx => $prod): ?>
                                <tr class="<?= $pidx === 0 ? 'original-row' : '' ?>" id="prod-row-<?= $prod['codproducto'] ?>" data-grupo="<?= $idx ?>">
                                    <td>
                                        <?php if ($pidx === 0): ?>
                                            <!-- Original: no checkbox -->
                                        <?php else: ?>
                                            <input type="checkbox" class="dup-check" data-id="<?= $prod['codproducto'] ?>" data-grupo="<?= $idx ?>" data-stock="<?= $prod['existencia'] ?>" onclick="actualizarSeleccion(<?= $idx ?>)">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($pidx === 0): ?>
                                            <span class="badge-original">ORIG</span>
                                        <?php else: ?>
                                            <span class="badge-duplicado">DUP</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= $prod['codproducto'] ?></strong></td>
                                    <td><?= htmlspecialchars($prod['descripcion']) ?></td>
                                    <td><?= htmlspecialchars($prod['codigo'] ?: '-') ?></td>
                                    <td style="text-align:right; font-weight:bold;"><?= $prod['existencia'] ?></td>
                                    <td style="text-align:right;"><?= number_format($prod['costo'], 0, ',', '.') ?></td>
                                    <td style="text-align:right; font-weight:bold;"><?= number_format($prod['precio'], 0, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($prod['categoria_nombre']) ?></td>
                                    <td><?= htmlspecialchars($prod['marca_nombre']) ?></td>
                                    <td><?= htmlspecialchars($prod['proveedor_nombre']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Group Actions -->
                        <div class="dup-group-actions">
                            <?php
                                $ids_arr = array_map(function($p) { return $p['codproducto']; }, $grupo['productos']);
                                $original_id = $ids_arr[0];
                                $dup_ids = array_slice($ids_arr, 1);
                                $stock_total = array_sum(array_map(function($p) { return $p['existencia']; }, $grupo['productos']));
                            ?>
                            <button class="btn-eliminar-sel" id="btn-elim-sel-<?= $idx ?>" onclick="eliminarSeleccionados(<?= $idx ?>, <?= $original_id ?>)" disabled>
                                <i class="fas fa-trash-alt"></i> <span>Eliminar Seleccionados</span>
                            </button>
                            <span class="dup-sel-count" id="sel-count-<?= $idx ?>">0</span>
                            <button class="btn-fusionar" onclick="fusionarGrupo(<?= $original_id ?>, [<?= implode(',', $dup_ids) ?>], <?= $idx ?>)">
                                <i class="fas fa-compress-arrows-alt"></i> Fusionar Todos (Sumar Stock: <?= $stock_total ?> → ID <?= $original_id ?>)
                            </button>
                            <button class="btn-eliminar-dup" onclick="eliminarDuplicados([<?= implode(',', $dup_ids) ?>], <?= $idx ?>)">
                                <i class="fas fa-trash-alt"></i> Eliminar Todos los Duplicados
                            </button>
                            <span class="dup-action-info">
                                Se conserva ID <?= $original_id ?> · <?= count($dup_ids) ?> duplicado(s)
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>

            <?php endif; ?>

        </div><!-- /.dup-wrapper -->
    </section>

    <?php include "includes/footer.php"; ?>

    <script>
    // ================================================================
    //  TOGGLE GROUP
    // ================================================================
    function toggleGrupo(idx) {
        var body = document.getElementById('grupo-body-' + idx);
        var header = body.previousElementSibling;
        body.classList.toggle('collapsed');
        header.classList.toggle('collapsed');
    }

    // ================================================================
    //  FILTER GROUPS
    // ================================================================
    function filtrarGrupos() {
        var buscar = document.getElementById('dup-buscar').value.toLowerCase();
        var filtroCant = parseInt(document.getElementById('dup-filtro-cant').value) || 0;
        var grupos = document.querySelectorAll('.dup-group');

        grupos.forEach(function(g) {
            var nombre = g.dataset.nombre;
            var cant = parseInt(g.dataset.cantidad);
            var show = true;

            if (buscar && nombre.indexOf(buscar) === -1) show = false;
            if (filtroCant === 2 && cant !== 2) show = false;
            if (filtroCant > 2 && cant < filtroCant) show = false;

            g.style.display = show ? '' : 'none';
        });
    }

    // ================================================================
    //  TOAST NOTIFICATION
    // ================================================================
    function showToast(msg, tipo) {
        var toast = document.getElementById('dup-toast');
        toast.textContent = msg;
        toast.className = 'dup-toast ' + (tipo || 'success');
        toast.style.display = 'block';
        setTimeout(function() { toast.style.display = 'none'; }, 4000);
    }

    // ================================================================
    //  CHECKBOX: SELECCIONAR TODOS LOS DUPLICADOS DE UN GRUPO
    // ================================================================
    function toggleSeleccionGrupo(grupoIdx) {
        var checkAll = document.querySelector('.dup-check-all[data-grupo="' + grupoIdx + '"]');
        var checks = document.querySelectorAll('.dup-check[data-grupo="' + grupoIdx + '"]');
        checks.forEach(function(cb) {
            cb.checked = checkAll.checked;
            var row = cb.closest('tr');
            if (cb.checked) {
                row.classList.add('selected-row');
            } else {
                row.classList.remove('selected-row');
            }
        });
        actualizarSeleccion(grupoIdx);
    }

    // ================================================================
    //  CHECKBOX: ACTUALIZAR CONTEO Y BOTÓN
    // ================================================================
    function actualizarSeleccion(grupoIdx) {
        var checks = document.querySelectorAll('.dup-check[data-grupo="' + grupoIdx + '"]');
        var seleccionados = [];
        checks.forEach(function(cb) {
            var row = cb.closest('tr');
            if (cb.checked) {
                seleccionados.push(parseInt(cb.dataset.id));
                row.classList.add('selected-row');
            } else {
                row.classList.remove('selected-row');
            }
        });

        var btn = document.getElementById('btn-elim-sel-' + grupoIdx);
        var countBadge = document.getElementById('sel-count-' + grupoIdx);
        
        if (seleccionados.length > 0) {
            btn.disabled = false;
            btn.querySelector('span').textContent = 'Eliminar Seleccionados (' + seleccionados.length + ')';
            countBadge.textContent = seleccionados.length + ' sel.';
            countBadge.classList.add('active');
        } else {
            btn.disabled = true;
            btn.querySelector('span').textContent = 'Eliminar Seleccionados';
            countBadge.classList.remove('active');
        }

        // Update check-all state
        var checkAll = document.querySelector('.dup-check-all[data-grupo="' + grupoIdx + '"]');
        if (checkAll) {
            checkAll.checked = seleccionados.length === checks.length && checks.length > 0;
        }
    }

    // ================================================================
    //  ELIMINAR SOLO LOS SELECCIONADOS
    // ================================================================
    function eliminarSeleccionados(grupoIdx, originalId) {
        var checks = document.querySelectorAll('.dup-check[data-grupo="' + grupoIdx + '"]:checked');
        if (checks.length === 0) { alert('No hay productos seleccionados.'); return; }

        var ids = [];
        var stockTotal = 0;
        checks.forEach(function(cb) {
            ids.push(parseInt(cb.dataset.id));
            stockTotal += parseInt(cb.dataset.stock) || 0;
        });

        var msg = '¿Eliminar ' + ids.length + ' producto(s) seleccionado(s)?\n\n';
        if (stockTotal > 0) {
            msg += 'Los productos seleccionados tienen un stock total de ' + stockTotal + '.\n';
            msg += '¿Querés sumar ese stock al producto original (ID ' + originalId + ') antes de eliminarlos?\n\n';
            msg += 'Aceptar = Sumar stock y eliminar\nCancelar = No hacer nada';
            
            if (!confirm(msg)) return;

            // Ask if they want to sum stock
            var sumarStock = confirm('¿Sumar el stock (' + stockTotal + ') al producto original antes de eliminar?\n\nAceptar = SÍ, sumar stock\nCancelar = NO, solo eliminar');
        } else {
            msg += 'Se eliminarán permanentemente de la base de datos.';
            if (!confirm(msg)) return;
            var sumarStock = false;
        }

        $.ajax({
            url: 'ajax_categorias_marcas_tamanos.php',
            type: 'POST',
            data: {
                action: 'fusionarDuplicados',
                original_id: sumarStock ? originalId : 0,
                duplicado_ids: JSON.stringify(ids),
                sumar_stock: sumarStock ? 1 : 0
            },
            success: function(resp) {
                try {
                    var data = JSON.parse(resp);
                    if (data.cod === '00') {
                        showToast('✔ ' + data.msg, 'success');
                        // Remove deleted rows from the table
                        ids.forEach(function(id) {
                            var row = document.getElementById('prod-row-' + id);
                            if (row) row.remove();
                        });
                        // Check if only original remains
                        var remaining = document.querySelectorAll('.dup-check[data-grupo="' + grupoIdx + '"]');
                        if (remaining.length === 0) {
                            var grupo = document.getElementById('grupo-' + grupoIdx);
                            grupo.classList.add('fusionado');
                            setTimeout(function() { grupo.style.display = 'none'; }, 1000);
                        }
                        actualizarSeleccion(grupoIdx);
                        actualizarContadores(ids.length);
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
    //  FUSIONAR GRUPO (sumar stock + eliminar duplicados)
    // ================================================================
    function fusionarGrupo(originalId, dupIds, grupoIdx) {
        if (!confirm('¿Fusionar este grupo?\n\nSe sumará el stock de los duplicados al producto original (ID ' + originalId + ') y se ELIMINARÁN los ' + dupIds.length + ' duplicado(s) de la base de datos.')) return;

        $.ajax({
            url: 'ajax_categorias_marcas_tamanos.php',
            type: 'POST',
            data: {
                action: 'fusionarDuplicados',
                original_id: originalId,
                duplicado_ids: JSON.stringify(dupIds),
                sumar_stock: 1
            },
            success: function(resp) {
                try {
                    var data = JSON.parse(resp);
                    if (data.cod === '00') {
                        showToast('✔ ' + data.msg, 'success');
                        var grupo = document.getElementById('grupo-' + grupoIdx);
                        grupo.classList.add('fusionado');
                        setTimeout(function() { grupo.style.display = 'none'; }, 1000);
                        actualizarContadores(dupIds.length);
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
    //  ELIMINAR DUPLICADOS (sin sumar stock)
    // ================================================================
    function eliminarDuplicados(dupIds, grupoIdx) {
        if (!confirm('¿Eliminar ' + dupIds.length + ' duplicado(s) SIN sumar stock?\n\nLos productos duplicados se borrarán permanentemente.')) return;

        $.ajax({
            url: 'ajax_categorias_marcas_tamanos.php',
            type: 'POST',
            data: {
                action: 'fusionarDuplicados',
                original_id: 0,
                duplicado_ids: JSON.stringify(dupIds),
                sumar_stock: 0
            },
            success: function(resp) {
                try {
                    var data = JSON.parse(resp);
                    if (data.cod === '00') {
                        showToast('✔ ' + data.msg, 'success');
                        var grupo = document.getElementById('grupo-' + grupoIdx);
                        grupo.classList.add('fusionado');
                        setTimeout(function() { grupo.style.display = 'none'; }, 1000);
                        actualizarContadores(dupIds.length);
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
    //  FUSIONAR TODOS
    // ================================================================
    function fusionarTodos() {
        var grupos = document.querySelectorAll('.dup-group:not(.fusionado)');
        var visibles = [];
        grupos.forEach(function(g) {
            if (g.style.display !== 'none') visibles.push(g);
        });

        if (visibles.length === 0) {
            alert('No hay grupos para fusionar.');
            return;
        }

        if (!confirm('⚠️ ATENCIÓN: Vas a fusionar ' + visibles.length + ' grupo(s) de duplicados.\n\nPor cada grupo:\n• Se suma el stock al producto original (ID más bajo)\n• Se ELIMINAN los duplicados de la base de datos\n\n¿Continuar?')) return;

        var btn = document.querySelector('.btn-fusionar-todos');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

        var total = visibles.length;
        var procesados = 0;
        var exitosos = 0;

        visibles.forEach(function(g) {
            var fusionarBtn = g.querySelector('.btn-fusionar');
            if (!fusionarBtn) { procesados++; return; }

            // Extract onclick parameters
            var onclickStr = fusionarBtn.getAttribute('onclick');
            var match = onclickStr.match(/fusionarGrupo\((\d+),\s*\[([^\]]*)\],\s*(\d+)\)/);
            if (!match) { procesados++; return; }

            var origId = parseInt(match[1]);
            var dupIdsStr = match[2];
            var grupoIdx = parseInt(match[3]);
            var dupIds = dupIdsStr.split(',').map(function(s) { return parseInt(s.trim()); });

            $.ajax({
                url: 'ajax_categorias_marcas_tamanos.php',
                type: 'POST',
                data: {
                    action: 'fusionarDuplicados',
                    original_id: origId,
                    duplicado_ids: JSON.stringify(dupIds),
                    sumar_stock: 1
                },
                success: function(resp) {
                    try {
                        var data = JSON.parse(resp);
                        if (data.cod === '00') {
                            exitosos++;
                            g.classList.add('fusionado');
                            setTimeout(function() { g.style.display = 'none'; }, 500);
                        }
                    } catch(e) {}
                    procesados++;
                    checkComplete();
                },
                error: function() {
                    procesados++;
                    checkComplete();
                }
            });
        });

        function checkComplete() {
            if (procesados >= total) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-compress-arrows-alt"></i> Fusionar Todos (Sumar Stocks y Eliminar)';
                showToast('✔ Se fusionaron ' + exitosos + ' de ' + total + ' grupos correctamente.', 'success');
                // Recargar después de 2s para actualizar contadores
                setTimeout(function() { location.reload(); }, 2500);
            }
        }
    }

    // ================================================================
    //  UPDATE COUNTERS
    // ================================================================
    function actualizarContadores(eliminados) {
        var grupos = document.getElementById('stat-grupos');
        var filas = document.getElementById('stat-filas');
        var sobrantes = document.getElementById('stat-sobrantes');

        if (grupos) grupos.textContent = Math.max(0, parseInt(grupos.textContent) - 1);
        if (filas) filas.textContent = Math.max(0, parseInt(filas.textContent) - eliminados - 1);
        if (sobrantes) sobrantes.textContent = Math.max(0, parseInt(sobrantes.textContent) - eliminados);
    }
    </script>
</body>
</html>
