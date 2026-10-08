/**
 * Funciones JavaScript para el módulo mejorado de egresos
 * Archivo: egresos_mejorado.js
 * Versión: 2.0 (Compras Múltiples)
 */

// Arreglo temporal para guardar los insumos antes de procesar la compra
var listaInsumosTemporal = [];

// Event listener delegado para el botón de nuevo egreso
$(document).on('click', '#nuevoEgreso', function (e) {
    e.preventDefault();
    console.log("Abriendo modal de egreso mejorado v2.1...");
    listaInsumosTemporal = []; // Resetear lista

    $('.bodyModal').html(
        '<form action="" method="post" name="form_add_egreso" id="form_add_egreso" style="width: 550px; max-width: 95%; max-height: 85vh; overflow-y: auto; overflow-x: hidden; padding: 20px; background: #fff; border-radius: 10px;">' +
        '<h1><i class="fa fa-file-alt fa-w-12" style="font-size: 35pt;"></i> <br> Registrar Egreso</h1>' +
        '<input type="hidden" name="action" value="nuevoEgresoMejorado" required><br>' +

        // Campo Establecimiento (Común)
        '<label style="font-weight: bold; display: block; margin-bottom: 5px;">Establecimiento / Local de compra:</label>' +
        '<input type="text" name="establecimiento" id="establecimiento" placeholder="Ej: Supermercado El Pueblo, Ferretería, etc." style="width: 100%; padding: 10px; margin-bottom: 15px;"><br>' +

        '<label style="font-weight: bold; display: block; margin-bottom: 5px;">Tipo de Egreso:</label>' +
        '<select name="tipoEgreso" id="tipoEgreso" required style="width: 100%; padding: 10px; margin-bottom: 15px;">' +
        '<option value="">-- Seleccione --</option>' +
        '<option value="1">Gasto General (Un solo pago)</option>' +
        '<option value="2">Compra de Insumos (Varios ítems)</option>' +
        '</select><br>' +

        // Formulario para Gasto General
        '<div id="formGastoGeneral" style="display:none; border: 1px dashed #ccc; padding: 15px; margin-bottom: 15px;">' +
        '<h3 style="margin-bottom: 10px;">Detalle del Gasto</h3>' +
        '<label>Descripción:</label>' +
        '<input type="text" name="descEgreso" id="descEgreso" placeholder="Ej: Pago de luz, Alquiler" style="margin-bottom: 10px;">' +
        '<label>Monto Total (Gs.):</label>' +
        '<input type="number" name="cantEgreso" id="cantEgreso" placeholder="0" style="margin-bottom: 10px;" min="1">' +
        '</div>' +

        // Formulario para Insumos (Múltiples)
        '<div id="formInsumo" style="display:none; border: 1px dashed #3498db; padding: 15px; margin-bottom: 15px;">' +
        '<h3 style="color: #3498db; margin-bottom: 10px;">Agregar Insumos a la Lista</h3>' +
        '<div style="display: flex; gap: 10px; margin-bottom: 10px;">' +
        '<select id="id_insumo_sel" style="flex: 1; padding: 10px;"><option value="">-- Cargando --</option></select>' +
        '<button type="button" id="btnNuevoInsumoIn" class="btn_new" style="margin:0; padding: 5px 10px;">+ Crear</button>' +
        '</div>' +
        '<div style="display: flex; gap: 10px; margin-bottom: 10px;">' +
        '<div style="flex:1;">' +
        '<label style="font-size: 10pt;">Precio Unit.</label>' +
        '<input type="number" id="precioUnitario" placeholder="0" min="1">' +
        '</div>' +
        '<div style="flex:1;">' +
        '<label style="font-size: 10pt;">Cantidad</label>' +
        '<input type="number" id="cantidadUnidades" placeholder="0" min="1">' +
        '</div>' +
        '</div>' +
        '<button type="button" id="btnAgregarItem" class="btn_new" style="width:100%; background:#27ae60; margin: 10px 0;"><i class="fas fa-plus-circle"></i> Agregar a la lista</button>' +

        // Tabla de ítems agregados
        '<div id="listaItemsAgregados" style="margin-top: 15px; max-height: 200px; overflow-y: auto;">' +
        '<table style="font-size: 0.9em; width:100%;">' +
        '<thead style="background:#eee;"><tr><th>Insumo</th><th>Total</th><th></th></tr></thead>' +
        '<tbody id="tbodyItems"><tr><td colspan="3" style="text-align:center; padding:10px;">Lista vacía</td></tr></tbody>' +
        '</table>' +
        '</div>' +
        '<div style="text-align: right; margin-top: 10px; font-weight: bold; font-size: 1.2em;">Total: Gs. <span id="totalVentaInsumos">0</span></div>' +
        '</div>' +

        '<div class="alert alertAddProduct"></div>' +
        '<div style="display:flex; justify-content:space-between;">' +
        '<a href="#" class="btn_ok closeModal" onclick="coloseModal();"><i class="fas fa-ban"></i> Cancelar</a>' +
        '<button type="submit" class="btn_new"><i class="fas fa-save"></i> Registrar Todo</button>' +
        '</div>' +
        '</form>'
    );

    $('.modal').fadeIn();
});

// Cambiar tipo de egreso
$(document).on('change', '#tipoEgreso', function () {
    var tipo = $(this).val();
    if (tipo == '1') {
        $('#formGastoGeneral').slideDown();
        $('#formInsumo').slideUp();
        $('#descEgreso, #cantEgreso').attr('required', 'required');
    } else if (tipo == '2') {
        $('#formGastoGeneral').slideUp();
        $('#formInsumo').slideDown();
        $('#descEgreso, #cantEgreso').removeAttr('required');
        cargarListaInsumosSelect();
    } else {
        $('#formGastoGeneral, #formInsumo').slideUp();
    }
});

function cargarListaInsumosSelect() {
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        data: { action: 'listarInsumos' },
        success: function (response) {
            try {
                var insumos = JSON.parse(response);
                var options = '<option value="">-- Seleccione un insumo --</option>';
                if (insumos && insumos.length > 0) {
                    insumos.forEach(function (insumo) {
                        options += '<option value="' + insumo.id + '" data-precio="' + insumo.precio_referencia + '">' + insumo.nombre + ' (' + insumo.unidad_medida + ')</option>';
                    });
                }
                $('#id_insumo_sel').html(options);
            } catch (e) {
                $('#id_insumo_sel').html('<option value="">Error al cargar</option>');
            }
        }
    });
}

// Autocompletar precio al seleccionar insumo
$(document).on('change', '#id_insumo_sel', function () {
    var precio = $(this).find(':selected').data('precio');
    if (precio) $('#precioUnitario').val(precio);
});

// Agregar ítem a la lista temporal
$(document).on('click', '#btnAgregarItem', function () {
    var id_insumo = $('#id_insumo_sel').val();
    var nombre = $('#id_insumo_sel option:selected').text();
    var precio = parseFloat($('#precioUnitario').val()) || 0;
    var cantidad = parseFloat($('#cantidadUnidades').val()) || 0;

    if (!id_insumo || precio <= 0 || cantidad <= 0) {
        alert("Seleccione un insumo y especifique precio y cantidad mayor a cero.");
        return;
    }

    // Agregar al arreglo
    listaInsumosTemporal.push({
        id_insumo: id_insumo,
        nombre: nombre,
        precio_unitario: precio,
        cantidad_unidades: cantidad,
        total: precio * cantidad
    });

    // Limpiar campos de ítem
    $('#id_insumo_sel').val('');
    $('#precioUnitario').val('');
    $('#cantidadUnidades').val('');

    renderizarLista();
});

function renderizarLista() {
    var html = '';
    var totalGeneral = 0;

    if (listaInsumosTemporal.length == 0) {
        html = '<tr><td colspan="3" style="text-align:center; padding:10px;">Lista vacía</td></tr>';
    } else {
        listaInsumosTemporal.forEach(function (item, index) {
            totalGeneral += item.total;
            html += '<tr>' +
                '<td style="padding: 5px;">' + item.nombre + '</td>' +
                '<td style="text-align:right; padding: 5px;">' + item.total.toLocaleString('es-PY') + '</td>' +
                '<td style="text-align:center;"><a href="#" onclick="quitarItem(' + index + ')" style="color:red;"><i class="fas fa-times-circle"></i></a></td>' +
                '</tr>';
        });
    }

    $('#tbodyItems').html(html);
    $('#totalVentaInsumos').text(totalGeneral.toLocaleString('es-PY'));
}

function quitarItem(index) {
    listaInsumosTemporal.splice(index, 1);
    renderizarLista();
}

// Registrar compra completa
$(document).on('submit', '#form_add_egreso', function (e) {
    e.preventDefault();
    $('.alertAddProduct').html('');

    var tipo = $('#tipoEgreso').val();
    var establecimiento = $('#establecimiento').val();
    var dataSend = {
        action: 'nuevoEgresoMejorado',
        tipoEgreso: tipo,
        establecimiento: establecimiento
    };

    if (tipo == '1') {
        dataSend.descEgreso = $('#descEgreso').val();
        dataSend.cantEgreso = $('#cantEgreso').val();
    } else if (tipo == '2') {
        if (listaInsumosTemporal.length == 0) {
            $('.alertAddProduct').html('<p style="color:red;">La lista de insumos está vacía.</p>');
            return;
        }
        dataSend.items = listaInsumosTemporal;
    }

    /*
    // Validar total vs caja (simplificado)
    var totalCaja = parseFloat($('#total_caja').val()) || 0;
    var totalGasto = 0;
    if (tipo == '1') totalGasto = parseFloat(dataSend.cantEgreso) || 0;
    else listaInsumosTemporal.forEach(i => totalGasto += i.total);

    if (totalGasto > totalCaja) {
        $('.alertAddProduct').html('<p style="color:red;">No hay dinero suficiente en caja.</p>');
        return;
    }
    */

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        data: dataSend,
        success: function (response) {
            try {
                var res = JSON.parse(response);
                if (res.cod == '00') {
                    $('.alertAddProduct').html('<p style="color:green;">' + res.msg + '</p>');
                    listaGastos('', 1, 10);
                    setTimeout(function () { coloseModal(); }, 1500);
                } else {
                    $('.alertAddProduct').html('<p style="color:red;">' + res.msg + '</p>');
                }
            } catch (e) {
                console.error(e);
                $('.alertAddProduct').html('<p style="color:red;">Error en la respuesta del servidor.</p>');
            }
        }
    });
});

// Modal para nuevo insumo (mantener funcionalidad anterior)
$(document).on('click', '#btnNuevoInsumoIn', function (e) {
    e.preventDefault();
    abrirModalNuevoInsumo();
});

function abrirModalNuevoInsumo() {
    $('#modalNuevoInsumo').remove();
    var modalHtml =
        '<div class="modal" id="modalNuevoInsumo" style="display:none; position:fixed; width:100%; height:100vh; background:rgba(0,0,0,0.8); z-index:99999; top:0; left:0;">' +
        '<div style="width:100%; height:100%; display:flex; justify-content:center; align-items:center;">' +
        '<form id="formNuevoInsumo" style="width:400px; padding:20px; background:#fff; border-radius:10px;">' +
        '<h2 style="color:#0e725d; text-align:center;">Crear Nuevo Insumo</h2><br>' +
        '<label>Nombre del Insumo:</label>' +
        '<input type="text" id="nombreInsumo" required><br>' +
        '<label>Unidad (Litros, Unid, etc):</label>' +
        '<input type="text" id="unidadMedida"><br>' +
        '<label>Precio Ref:</label>' +
        '<input type="number" id="precioReferencia" min="0"><br>' +
        '<div class="alert alertNuevoInsumo"></div>' +
        '<div style="display:flex; justify-content:space-between;">' +
        '<button type="button" class="btn_ok" onclick="cerrarModalNuevoInsumo()">Cerrar</button>' +
        '<button type="submit" class="btn_new">Guardar</button>' +
        '</div>' +
        '</form>' +
        '</div>' +
        '</div>';

    $('body').append(modalHtml);
    $('#modalNuevoInsumo').fadeIn();

    $('#formNuevoInsumo').submit(function (e) {
        e.preventDefault();
        var desc = $('#nombreInsumo').val();
        var uni = $('#unidadMedida').val();
        var pre = $('#precioReferencia').val();

        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { action: 'crearInsumo', nombreInsumo: desc, unidadMedida: uni, precioReferencia: pre },
            success: function (response) {
                try {
                    var res = JSON.parse(response);
                    if (res.cod == '00') {
                        $('.alertNuevoInsumo').html('<p style="color:green;">Creado!</p>');
                        setTimeout(function () {
                            cerrarModalNuevoInsumo();
                            cargarListaInsumosSelect(); // Recargar el select
                        }, 1000);
                    } else {
                        $('.alertNuevoInsumo').html('<p style="color:red;">' + res.msg + '</p>');
                    }
                } catch (e) { }
            }
        });
    });
}

function cerrarModalNuevoInsumo() {
    $('#modalNuevoInsumo').fadeOut(function () { $(this).remove(); });
}

// EDITAR EGRESO
function infoEditarEgreso(id) {
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        data: { action: 'infoEgreso', nofactura: id },
        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);
                $('.bodyModal').html(
                    '<form action="" method="post" name="form_edit_egreso" id="form_edit_egreso" style="width: 450px; padding: 20px; background: #fff; border-radius: 10px;">' +
                    '<h1><i class="fas fa-edit" style="font-size: 35pt;"></i> <br> Editar Egreso</h1><br>' +
                    '<input type="hidden" name="action" value="actualizarEgreso">' +
                    '<input type="hidden" name="idEgreso" value="' + info.id + '">' +
                    '<label>Descripción:</label>' +
                    '<input type="text" name="descEgreso" value="' + info.descripcion + '" required><br>' +
                    '<label>Establecimiento:</label>' +
                    '<input type="text" name="establecimiento" value="' + info.establecimiento + '"><br>' +
                    '<label>Cantidad (Gs.):</label>' +
                    '<input type="number" name="cantEgreso" value="' + info.cantidad + '" required><br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<div style="display:flex; justify-content:space-between;">' +
                    '<button type="button" class="btn_ok" onclick="coloseModal();"><i class="fas fa-ban"></i> Cancelar</button>' +
                    '<button type="submit" class="btn_save"><i class="fas fa-save"></i> Guardar Cambios</button>' +
                    '</div>' +
                    '</form>'
                );
                $('.modal').fadeIn();
            }
        }
    });
}

$(document).on('submit', '#form_edit_egreso', function (e) {
    e.preventDefault();
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function (response) {
            var res = JSON.parse(response);
            if (res.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + res.msg + '</p>');
                listaGastosFiltrados();
                setTimeout(function () { coloseModal(); }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + res.msg + '</p>');
            }
        }
    });
});

// ELIMINAR EGRESO
function infoAnularEgreso(id) {
    $('.bodyModal').html(
        '<div style="text-align:center; padding: 20px; background: #fff; border-radius: 10px; width: 400px;">' +
        '<i class="fas fa-exclamation-triangle" style="font-size: 50pt; color: #e74c3c;"></i>' +
        '<h2 style="margin-top:15px;">¿Anular Egreso?</h2>' +
        '<p style="margin:10px 0;">¿Está seguro de eliminar este registro? <br> Si es una compra de insumos, el stock se restará automáticamente.</p>' +
        '<div style="display:flex; justify-content:center; gap: 15px; margin-top: 20px;">' +
        '<button type="button" class="btn_ok" onclick="coloseModal();">No, Cancelar</button>' +
        '<button type="button" class="btn_save" style="background:#e74c3c;" onclick="anularEgreso(' + id + ')">Sí, Eliminar</button>' +
        '</div>' +
        '</div>'
    );
    $('.modal').fadeIn();
}

function anularEgreso(id) {
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        data: { action: 'anularEgreso', noFactura: id },
        success: function (response) {
            if (response != 'error') {
                var res = JSON.parse(response);
                if (res.res == 'ok') {
                    coloseModal();
                    listaGastosFiltrados();
                } else {
                    alert(res.msg || "Error al eliminar");
                }
            } else {
                alert("Error en el servidor");
            }
        }
    });
}

// ============================================
// EDITAR INSUMO
// ============================================
function infoEditarInsumo(id) {
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        data: { action: 'infoInsumo', id_insumo: id },
        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);
                if (info.cod == '00') {
                    var d = info.data;
                    $('.bodyModal').html(
                        '<form action="" method="post" name="form_edit_insumo" id="form_edit_insumo" style="width: 420px; padding: 20px; background: #fff; border-radius: 10px;">' +
                        '<h1><i class="fas fa-box-open" style="font-size: 35pt;"></i> <br> Editar Insumo</h1><br>' +
                        '<input type="hidden" name="action" value="editarInsumo">' +
                        '<input type="hidden" name="idInsumo" value="' + d.id + '">' +
                        '<label>Nombre del Insumo:</label>' +
                        '<input type="text" name="nombreInsumo" value="' + d.nombre + '" required><br>' +
                        '<label>Unidad de Medida:</label>' +
                        '<input type="text" name="unidadMedida" value="' + d.unidad_medida + '"><br>' +
                        '<label>Precio de Referencia:</label>' +
                        '<input type="number" name="precioReferencia" value="' + d.precio_referencia + '" min="0" step="any"><br>' +
                        '<div class="alert alertAddProduct"></div>' +
                        '<div style="display:flex; justify-content:space-between; margin-top:15px;">' +
                        '<button type="button" class="btn_ok" onclick="coloseModal();"><i class="fas fa-ban"></i> Cancelar</button>' +
                        '<button type="submit" class="btn_save"><i class="fas fa-save"></i> Guardar</button>' +
                        '</div>' +
                        '</form>'
                    );
                    $('.modal').fadeIn();
                }
            }
        }
    });
}

$(document).on('submit', '#form_edit_insumo', function (e) {
    e.preventDefault();
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function (response) {
            var res = JSON.parse(response);
            if (res.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + res.msg + '</p>');
                setTimeout(function () {
                    coloseModal();
                    if (typeof listaGastosFiltrados === 'function') listaGastosFiltrados();
                    if (typeof serchForDetalleInsumoUso === 'function') serchForDetalleInsumoUso('', 1);
                }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + res.msg + '</p>');
            }
        }
    });
});

// ============================================
// ELIMINAR INSUMO
// ============================================
function confirmarEliminarInsumo(id, nombre) {
    $('.bodyModal').html(
        '<div style="text-align:center; padding: 20px; background: #fff; border-radius: 10px; width: 400px;">' +
        '<i class="fas fa-exclamation-triangle" style="font-size: 50pt; color: #e74c3c;"></i>' +
        '<h2 style="margin-top:15px;">¿Eliminar Insumo?</h2>' +
        '<p style="margin:10px 0;"><strong>' + nombre + '</strong><br>Esta acción desactivará el insumo del sistema.</p>' +
        '<div style="display:flex; justify-content:center; gap: 15px; margin-top: 20px;">' +
        '<button type="button" class="btn_ok" onclick="coloseModal();">No, Cancelar</button>' +
        '<button type="button" class="btn_save" style="background:#e74c3c;" onclick="eliminarInsumo(' + id + ')">Sí, Eliminar</button>' +
        '</div>' +
        '</div>'
    );
    $('.modal').fadeIn();
}

function eliminarInsumo(id) {
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        data: { action: 'eliminarInsumo', idInsumo: id },
        success: function (response) {
            var res = JSON.parse(response);
            if (res.cod == '00') {
                coloseModal();
                if (typeof listaGastosFiltrados === 'function') listaGastosFiltrados();
                if (typeof serchForDetalleInsumoUso === 'function') serchForDetalleInsumoUso('', 1);
            } else {
                alert(res.msg || 'Error al eliminar');
            }
        }
    });
}
