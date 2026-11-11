<?php
ensure_session();
if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2) {
    header("location: ./");
    exit;
}
include "../conexion.php";
// Cargar listas para filtros
$query_cat = mysqli_query($conection, "SELECT idcategoria, nombre FROM categoria WHERE status = 1 ORDER BY nombre ASC");
$query_marca = mysqli_query($conection, "SELECT idmarca, nombre FROM marca WHERE status = 1 ORDER BY nombre ASC");
$query_prov = mysqli_query($conection, "SELECT codproveedor, proveedor FROM proveedor WHERE status = 1 ORDER BY proveedor ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Reportes de Inventario</title>
</head>
<body>
	<?php include "includes/header.php"; ?>

<section id="container">
    <div class="title_page" style="margin-bottom: 15px;">
        <h1><i class="fas fa-boxes"></i> Reportes Avanzados de Inventario</h1>
    </div>

    <!-- TARJETAS KPI RESUMEN FINANCIERO -->
    <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
        <div style="flex: 1; min-width: 180px; background: #fff; border-left: 5px solid #3498db; border-radius: 6px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <div style="font-size: 12px; color: #7f8c8d; font-weight: bold; text-transform: uppercase;">Total Ítems</div>
            <div id="kpi_total_prods" style="font-size: 24px; font-weight: bold; color: #2c3e50; margin-top: 5px;">0</div>
        </div>
        <div style="flex: 1; min-width: 180px; background: #fff; border-left: 5px solid #2ecc71; border-radius: 6px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <div style="font-size: 12px; color: #7f8c8d; font-weight: bold; text-transform: uppercase;">Stock en Unidades</div>
            <div id="kpi_total_stock" style="font-size: 24px; font-weight: bold; color: #27ae60; margin-top: 5px;">0</div>
        </div>
        <div style="flex: 1; min-width: 200px; background: #fff; border-left: 5px solid #e74c3c; border-radius: 6px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <div style="font-size: 12px; color: #7f8c8d; font-weight: bold; text-transform: uppercase;">Inversión Total (Costo)</div>
            <div id="kpi_total_costo" style="font-size: 20px; font-weight: bold; color: #c0392b; margin-top: 5px;">Gs. 0</div>
        </div>
        <div style="flex: 1; min-width: 200px; background: #fff; border-left: 5px solid #f39c12; border-radius: 6px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <div style="font-size: 12px; color: #7f8c8d; font-weight: bold; text-transform: uppercase;">Valor Venta Total</div>
            <div id="kpi_total_venta" style="font-size: 20px; font-weight: bold; color: #d35400; margin-top: 5px;">Gs. 0</div>
        </div>
        <div style="flex: 1; min-width: 200px; background: #fff; border-left: 5px solid #9b59b6; border-radius: 6px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <div style="font-size: 12px; color: #7f8c8d; font-weight: bold; text-transform: uppercase;">Ganancia Estimada</div>
            <div id="kpi_total_ganancia" style="font-size: 20px; font-weight: bold; color: #8e44ad; margin-top: 5px;">Gs. 0</div>
        </div>
    </div>

    <!-- PANEL DE FILTROS -->
    <div style="background: #fff; padding: 15px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.08); margin-bottom: 20px;">
        <form id="form_filtros_reporte" onsubmit="event.preventDefault(); cargarReporteInventario();">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
                <div style="flex: 1; min-width: 160px;">
                    <label style="font-weight: bold; font-size: 12px; display: block; margin-bottom: 4px;">Categoría:</label>
                    <select id="rep_categoria" style="width: 100%; height: 35px; padding: 4px 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">Todas las Categorías</option>
                        <?php while ($cat = mysqli_fetch_assoc($query_cat)) { ?>
                            <option value="<?php echo $cat['idcategoria']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div style="flex: 1; min-width: 160px;">
                    <label style="font-weight: bold; font-size: 12px; display: block; margin-bottom: 4px;">Marca:</label>
                    <select id="rep_marca" style="width: 100%; height: 35px; padding: 4px 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">Todas las Marcas</option>
                        <?php while ($m = mysqli_fetch_assoc($query_marca)) { ?>
                            <option value="<?php echo $m['idmarca']; ?>"><?php echo htmlspecialchars($m['nombre']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div style="flex: 1; min-width: 160px;">
                    <label style="font-weight: bold; font-size: 12px; display: block; margin-bottom: 4px;">Estado de Stock:</label>
                    <select id="rep_stock" style="width: 100%; height: 35px; padding: 4px 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">Todos los Estados</option>
                        <option value="con_stock">En Stock ( > 0 )</option>
                        <option value="stock_bajo">Stock Bajo ( <= 5 )</option>
                        <option value="agotado">Agotados ( = 0 )</option>
                    </select>
                </div>
                <div style="flex: 1; min-width: 160px;">
                    <label style="font-weight: bold; font-size: 12px; display: block; margin-bottom: 4px;">Proveedor:</label>
                    <select id="rep_proveedor" style="width: 100%; height: 35px; padding: 4px 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">Todos los Proveedores</option>
                        <?php while ($pr = mysqli_fetch_assoc($query_prov)) { ?>
                            <option value="<?php echo $pr['codproveedor']; ?>"><?php echo htmlspecialchars($pr['proveedor']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div style="flex: 1.5; min-width: 200px;">
                    <label style="font-weight: bold; font-size: 12px; display: block; margin-bottom: 4px;">Búsqueda:</label>
                    <input type="text" id="rep_busqueda" placeholder="Buscar por código o producto..." style="width: 100%; height: 35px; padding: 4px 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 15px; justify-content: flex-end;">
                <button type="submit" class="btn_view" style="height: 35px; padding: 0 15px; display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <button type="button" class="btn_new" onclick="exportarExcelInventario();" style="background: #27ae60; border-color: #27ae60; height: 35px; padding: 0 15px; display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
                    <i class="fas fa-file-excel"></i> Exportar a Excel
                </button>
                <button type="button" class="btn_ok" onclick="imprimirReporteInventario();" style="background: #2980b9; border-color: #2980b9; height: 35px; padding: 0 15px; display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
                    <i class="fas fa-print"></i> Imprimir / PDF
                </button>
            </div>
        </form>
    </div>

    <!-- TABLA DETALLADA DE INVENTARIO -->
    <div id="contenedor_tabla_reporte" style="background: #fff; padding: 15px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.08); overflow-x: auto;">
        <table class="tbl_venta" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #2c3e50; color: #fff;">
                    <th style="padding: 10px; text-align: left;">Código</th>
                    <th style="padding: 10px; text-align: left;">Descripción / Producto</th>
                    <th style="padding: 10px; text-align: left;">Categoría</th>
                    <th style="padding: 10px; text-align: left;">Marca</th>
                    <th style="padding: 10px; text-align: left;">Tamaño</th>
                    <th style="padding: 10px; text-align: center;">Stock</th>
                    <th style="padding: 10px; text-align: right;">Costo Unit.</th>
                    <th style="padding: 10px; text-align: right;">Precio Unit.</th>
                    <th style="padding: 10px; text-align: right;">Inversión Costo</th>
                    <th style="padding: 10px; text-align: right;">Valor Venta Total</th>
                    <th style="padding: 10px; text-align: center;">Estado</th>
                </tr>
            </thead>
            <tbody id="tbody_reporte_inventario">
                <tr>
                    <td colspan="11" class="textcenter" style="padding: 20px;">Cargando inventario...</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<script type="text/javascript">
$(document).ready(function(){
    cargarReporteInventario();
});

function cargarReporteInventario() {
    var cat = $('#rep_categoria').val();
    var marca = $('#rep_marca').val();
    var stock = $('#rep_stock').val();
    var prov = $('#rep_proveedor').val();
    var busqueda = $('#rep_busqueda').val();

    $('#tbody_reporte_inventario').html('<tr><td colspan="11" class="textcenter" style="padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Cargando reporte...</td></tr>');

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        data: {
            action: 'reporteInventarioAvanzado',
            categoria_id: cat,
            marca_id: marca,
            estado_stock: stock,
            proveedor_id: prov,
            busqueda: busqueda
        },
        success: function(response) {
            try {
                var res = (typeof response === 'string') ? JSON.parse(response) : response;
                if (res.status === 'success') {
                    $('#kpi_total_prods').text(res.kpi.total_prods);
                    $('#kpi_total_stock').text(res.kpi.total_stock);
                    $('#kpi_total_costo').text(res.kpi.total_costo);
                    $('#kpi_total_venta').text(res.kpi.total_venta);
                    $('#kpi_total_ganancia').text(res.kpi.total_ganancia);

                    $('#tbody_reporte_inventario').html(res.html_tabla);
                } else {
                    $('#tbody_reporte_inventario').html('<tr><td colspan="11" class="textcenter" style="padding: 20px; color: red;">Error al cargar datos.</td></tr>');
                }
            } catch(e) {
                console.error("Error parseando respuesta reporte:", e);
                $('#tbody_reporte_inventario').html('<tr><td colspan="11" class="textcenter" style="padding: 20px; color: red;">Respuesta inválida del servidor.</td></tr>');
            }
        },
        error: function(xhr) {
            console.error("Error en petición ajax reporte:", xhr.status);
            $('#tbody_reporte_inventario').html('<tr><td colspan="11" class="textcenter" style="padding: 20px; color: red;">No se pudo conectar con el servidor.</td></tr>');
        }
    });
}

function exportarExcelInventario() {
    var cat = $('#rep_categoria').val();
    var marca = $('#rep_marca').val();
    var stock = $('#rep_stock').val();
    var prov = $('#rep_proveedor').val();
    var busqueda = $('#rep_busqueda').val();

    var url = 'exportar_reporte_inventario.php?categoria_id=' + encodeURIComponent(cat) +
              '&marca_id=' + encodeURIComponent(marca) +
              '&estado_stock=' + encodeURIComponent(stock) +
              '&proveedor_id=' + encodeURIComponent(prov) +
              '&busqueda=' + encodeURIComponent(busqueda);
    window.open(url, '_blank');
}

function imprimirReporteInventario() {
    var cat = $('#rep_categoria').find('option:selected').text();
    var marca = $('#rep_marca').find('option:selected').text();
    var stock = $('#rep_stock').find('option:selected').text();

    var printWin = window.open('', '_blank');
    var contenidoTabla = $('#contenedor_tabla_reporte').html();
    var kpis = '<div style="display:flex; gap:10px; margin-bottom:15px; font-size:12px;">' +
               '<div style="border:1px solid #ccc; padding:8px;"><strong>Productos:</strong> ' + $('#kpi_total_prods').text() + '</div>' +
               '<div style="border:1px solid #ccc; padding:8px;"><strong>Stock Total:</strong> ' + $('#kpi_total_stock').text() + '</div>' +
               '<div style="border:1px solid #ccc; padding:8px;"><strong>Inversión Costo:</strong> ' + $('#kpi_total_costo').text() + '</div>' +
               '<div style="border:1px solid #ccc; padding:8px;"><strong>Valor Venta:</strong> ' + $('#kpi_total_venta').text() + '</div>' +
               '<div style="border:1px solid #ccc; padding:8px;"><strong>Ganancia Est.:</strong> ' + $('#kpi_total_ganancia').text() + '</div>' +
               '</div>';

    printWin.document.write('<!DOCTYPE html><html><head><title>Reporte de Inventario</title><style>' +
        'body { font-family: Arial, sans-serif; margin: 20px; color: #000; } ' +
        'h2 { margin-bottom: 5px; } p { margin: 2px 0 15px 0; font-size: 12px; color: #555; } ' +
        'table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; } ' +
        'th, td { border: 1px solid #aaa; padding: 6px; } ' +
        'th { background: #2c3e50 !important; color: #fff !important; font-weight: bold; } ' +
        'tr:nth-child(even) { background: #f9f9f9; } ' +
        '.badge-status { padding: 2px 6px; border-radius: 3px; font-weight: bold; } ' +
        '</style></head><body>' +
        '<h2>REPORTE GENERAL DE INVENTARIO</h2>' +
        '<p>Filtros aplicados: Categoría (' + cat + ') | Marca (' + marca + ') | Estado (' + stock + ')</p>' +
        kpis +
        contenidoTabla +
        '<script>window.onload = function() { window.print(); };<\/script>' +
        '</body></html>');
    printWin.document.close();
}
</script>

<?php include "includes/footer.php"; ?>
