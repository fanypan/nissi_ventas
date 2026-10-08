// Funciones para el control de insumos y consumos

function cargarInsumosSelect() {
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: 'listarInsumos' },
        success: function (response) {
            if (response != 'error') {
                let info = JSON.parse(response);
                let options = '<option value="">Seleccione un insumo</option>';
                info.forEach(insumo => {
                    options += `<option value="${insumo.id}" data-stock="${insumo.stock}" data-unidad="${insumo.unidad_medida}">${insumo.nombre} (${insumo.unidad_medida})</option>`;
                });
                $('#id_insumo_uso').html(options);

                // Evento para el buscador interno
                $('#buscar_insumo_uso').on('keyup', function () {
                    var value = $(this).val().toLowerCase();
                    $("#id_insumo_uso option").filter(function () {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                    });
                });
            }
        },
        error: function (error) {
            console.log(error);
        }
    });
}

function obtenerInfoInsumo(id) {
    if (!id) {
        $('#stock_insumo_uso').html('0.00');
        $('#unidad_insumo_uso').html('-');
        return;
    }
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: 'infoInsumo', id_insumo: id },
        success: function (response) {
            let info = JSON.parse(response);
            if (info.cod == '00') {
                // Formatear para quitar .00 si es entero
                let stock_formateado = parseFloat(info.data.stock).toString();
                $('#stock_insumo_uso').html(stock_formateado);
                $('#unidad_insumo_uso').html(info.data.unidad_medida);

                // Animación sutil de actualización
                $('#stock_insumo_uso').css('color', '#3498db');
                setTimeout(() => { $('#stock_insumo_uso').css('color', '#2c3e50'); }, 500);
            }
        },
        error: function (error) {
            console.log(error);
        }
    });
}

function registrarConsumoInsumo() {
    let id_insumo = $('#id_insumo_uso').val();
    let cantidad = $('#cantidad_uso').val();
    let obs = $('#obs_uso').val();

    if (id_insumo == '' || cantidad <= 0) {
        $('#alertUso').html('<p style="color:red;">Seleccione un insumo y cantidad válida.</p>').fadeIn();
        return;
    }

    $.ajax({
        url: 'ajax_insumos.php',
        type: 'POST',
        async: true,
        data: {
            action: 'registrarConsumo',
            id_insumo: id_insumo,
            cantidad: cantidad,
            observaciones: obs
        },
        success: function (response) {
            let info = JSON.parse(response);
            if (info.cod == '00') {
                $('#alertUso').html('<p style="color:green;">' + info.msg + '</p>').fadeIn();
                $('#form_uso_insumo')[0].reset();
                $('#stock_insumo_uso').html('-');
                listarHistorialConsumo(1);
            } else {
                $('#alertUso').html('<p style="color:red;">' + info.msg + '</p>').fadeIn();
            }
            setTimeout(() => { $('#alertUso').fadeOut(); }, 3000);
        },
        error: function (error) {
            console.log(error);
        }
    });
}

function listarHistorialConsumo(pagina) {
    let f_de = $('#fecha_de_uso').val();
    let f_a = $('#fecha_a_uso').val();

    $.ajax({
        url: 'ajax_insumos.php',
        type: 'POST',
        async: true,
        data: {
            action: 'listarConsumos',
            pagina: pagina,
            f_de: f_de,
            f_a: f_a
        },
        success: function (response) {
            if (response != 'error') {
                let info = JSON.parse(response);
                $('#historialConsumo').html(info.tabla);
                // El paginador se podría implementar similar a otros módulos si crece mucho
            }
        },
        error: function (error) {
            console.log(error);
        }
    });
}

// Funciones para Editar/Eliminar Consumo (Admin)
function infoEditarConsumo(id) {
    $.ajax({
        url: 'ajax_insumos.php',
        type: 'POST',
        data: { action: 'infoConsumo', id: id },
        success: function (response) {
            let info = JSON.parse(response);
            if (info.cod == '00') {
                $('.bodyModal').html(`
                    <form action="" method="post" name="form_edit_consumo" id="form_edit_consumo" style="width: 400px; padding: 20px; background: #fff; border-radius: 10px;">
                        <h1><i class="fas fa-edit" style="font-size: 35pt;"></i> <br> Editar Consumo</h1><br>
                        <input type="hidden" name="action" value="actualizarConsumo">
                        <input type="hidden" name="idConsumo" value="${info.data.id}">
                        <label>Observaciones:</label>
                        <input type="text" name="observaciones" value="${info.data.observaciones}" required><br>
                        <p style="color: #666; font-size: 0.9em; margin-top: 10px;">Nota: Solo se pueden editar las observaciones. Para corregir la cantidad, debe eliminarlo y registrarlo nuevamente.</p>
                        <div class="alert alertAddProduct"></div>
                        <div style="display:flex; justify-content:space-between; margin-top: 15px;">
                            <button type="button" class="btn_ok" onclick="coloseModal();"><i class="fas fa-ban"></i> Cancelar</button>
                            <button type="submit" class="btn_save"><i class="fas fa-save"></i> Guardar</button>
                        </div>
                    </form>
                `);
                $('.modal').fadeIn();
            }
        }
    });
}

$(document).on('submit', '#form_edit_consumo', function (e) {
    e.preventDefault();
    $.ajax({
        url: 'ajax_insumos.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function (response) {
            let res = JSON.parse(response);
            if (res.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + res.msg + '</p>');
                listarHistorialConsumo(1);
                setTimeout(() => { coloseModal(); }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + res.msg + '</p>');
            }
        }
    });
});

function infoAnularConsumo(id) {
    $('.bodyModal').html(`
        <div style="text-align:center; padding: 20px; background: #fff; border-radius: 10px; width: 400px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 50pt; color: #e74c3c;"></i>
            <h2 style="margin-top:15px;">¿Anular Consumo?</h2>
            <p style="margin:10px 0;">El stock se devolverá automáticamente al inventario.</p>
            <div style="display:flex; justify-content:center; gap: 15px; margin-top: 20px;">
                <button type="button" class="btn_ok" onclick="coloseModal();">No, Cancelar</button>
                <button type="button" class="btn_save" style="background:#e74c3c;" onclick="anularConsumo(${id})">Sí, Eliminar</button>
            </div>
        </div>
    `);
    $('.modal').fadeIn();
}

function anularConsumo(id) {
    $.ajax({
        url: 'ajax_insumos.php',
        type: 'POST',
        data: { action: 'anularConsumo', id: id },
        success: function (response) {
            let res = JSON.parse(response);
            if (res.cod == '00') {
                coloseModal();
                listarHistorialConsumo(1);
            } else {
                alert(res.msg);
            }
        }
    });
}
