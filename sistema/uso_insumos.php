<?php
session_start();
include "../conexion.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php include "includes/scripts.php"; ?>
    <title>Uso de Insumos</title>
</head>
<body>
    <?php include "includes/header.php"; ?>
    <section id="container">
        <div class="title_page">
            <h1><i class="fas fa-utensils"></i> Registro de Uso de Insumos (Cocina)</h1>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            <!-- Panel Izquierdo: Buscador de Insumos (Estilo Ventas) -->
            <div style="flex: 1.5; min-width: 300px;">
                <div class="data_producto">
                    <label><i class="fas fa-search"></i> Buscar Insumo:</label>
                    <input type="text" name="busquedaInsumo" id="busquedaInsumo" placeholder="Escriba nombre del insumo móvil, materia prima..." style="width: 100%; padding: 10px; margin: 10px 0;">
                    <br>
                    <div class="containerTable" id="dataInsumo"></div>
                    <div class="paginador" id="paginadorInsumo"></div>
                </div>
            </div>

            <!-- Panel Derecho: Formulario de Registro -->
            <div style="flex: 1; min-width: 300px; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                <div class="datos_venta" style="box-shadow: none; padding: 0;">
                    <h3 style="margin-bottom: 15px; border-bottom: 2px solid #3498db; padding-bottom: 5px;">Detalles del Consumo</h3>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid #3498db;">
                        <label style="color: #7f8c8d; font-weight: bold;">Insumo Seleccionado:</label>
                        <div id="info_insumo_sel" style="font-size: 1.2em; color: #2c3e50; margin: 5px 0;">-</div>
                        <label style="color: #7f8c8d; font-size: 0.9em;">Stock Actual:</label>
                        <div id="stock_info" style="font-weight: bold; color: #e67e22;"><span id="stock_insumo_uso">0.00</span> <span id="unidad_insumo_uso"></span></div>
                    </div>

                    <form action="" method="post" name="form_uso_insumo" id="form_uso_insumo" onsubmit="event.preventDefault(); registrarConsumoInsumo();">
                        <input type="hidden" name="id_insumo_uso" id="id_insumo_uso" required>
                        
                        <div style="margin-bottom: 15px;">
                            <label>Cantidad a usar:</label>
                            <input type="number" step="any" name="cantidad_uso" id="cantidad_uso" placeholder="0.00" required min="0.01" style="width: 100%; padding: 10px; font-size: 1.1em; border: 1px solid #ccc; border-radius: 5px;">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label>Observaciones / Plato:</label>
                            <textarea name="obs_uso" id="obs_uso" placeholder="Ej: Para 10 porciones de postre" style="width: 100%; padding: 10px; height: 80px; border: 1px solid #ccc; border-radius: 5px; resize: none;"></textarea>
                        </div>

                        <button type="submit" class="btn_save" style="width: 100%; padding: 12px; font-size: 1.1em; margin: 0; background: #2ecc71;">
                            <i class="fas fa-check"></i> Registrar Consumo
                        </button>
                        
                        <div class="alert alertAddProduct" id="alertUso" style="margin-top: 15px;"></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="containerTable" style="margin-top: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 10px; background: #34495e; color: #fff; padding: 10px; border-radius: 8px;">
                <h3 style="margin: 0;"><i class="fas fa-history"></i> Historial de Consumos Recientes</h3>
                <div style="display: flex; gap: 10px; align-items: flex-end;">
                    <div>
                        <label style="font-size: 0.8em; display: block;">Desde:</label>
                        <input type="date" id="fecha_de_uso" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" style="padding: 5px; border-radius: 4px; border: none;">
                    </div>
                    <div>
                        <label style="font-size: 0.8em; display: block;">Hasta:</label>
                        <input type="date" id="fecha_a_uso" value="<?php echo date('Y-m-d'); ?>" style="padding: 5px; border-radius: 4px; border: none;">
                    </div>
                    <button type="button" class="btn_view" onclick="listarHistorialConsumo(1)" style="margin: 0; padding: 7px 15px; background: #3498db; color: #fff; border: none; border-radius: 4px;"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <table class="tbl_venta">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Insumo</th>
                        <th>Cantidad</th>
                        <th>Usuario</th>
                        <th>Observaciones</th>
                        <th style="width: 100px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="historialConsumo">
                    <!-- AJAX carga aquí -->
                </tbody>
            </table>
            <div class="paginador" id="paginadorConsumo"></div>
        </div>
    </section>

    <script type="text/javascript" src="js/insumos.js"></script>
    <script type="text/javascript" src="js/egresos_mejorado.js?v=1.2"></script>
    <script>
        $(document).ready(function() {
            listarHistorialConsumo(1);
            serchForDetalleInsumoUso('', 1);
        });

        $('#busquedaInsumo').keyup(function() {
            let valor = $(this).val();
            serchForDetalleInsumoUso(valor, 1);
        });

        function serchForDetalleInsumoUso(busqueda, pagina) {
            $.ajax({
                url: 'action/data_insumo.php',
                type: "POST",
                data: { pagina: pagina, busquedaInsumo: busqueda },
                success: function(response) {
                    if (response != 'error') {
                        let info = JSON.parse(response);
                        $('#dataInsumo').html(info.detalle);
                        $('#paginadorInsumo').html(info.totales);
                    } else {
                        $('#dataInsumo').html('<p class="textcenter">No se encontraron insumos.</p>');
                        $('#paginadorInsumo').html('');
                    }
                }
            });
        }

        // Manejar paginación del buscador
        $("body").on("click", "#paginadorInsumo li a", function(e) {
            e.preventDefault();
            let valorhref = $(this).attr("href");
            let valorBuscar = $("#busquedaInsumo").val();
            serchForDetalleInsumoUso(valorBuscar, valorhref);
        });

        function seleccionarInsumoUso(id) {
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                data: { action: 'infoInsumo', id_insumo: id },
                success: function(response) {
                    let info = JSON.parse(response);
                    if (info.cod == '00') {
                        $('#id_insumo_uso').val(info.data.id);
                        $('#info_insumo_sel').html('<strong>' + info.data.nombre + '</strong>');
                        $('#stock_insumo_uso').html(parseFloat(info.data.stock).toString());
                        $('#unidad_insumo_uso').html(info.data.unidad_medida);
                        
                        // Foco en cantidad
                        $('#cantidad_uso').focus();
                    }
                }
            });
        }
    </script>
</body>
</html>
