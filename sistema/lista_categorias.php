<?php
nissi_ensure_session();
if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2) {
	header("location: ./");
}

include "../conexion.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Lista de Categorías</title>
</head>
<body>
	<?php include "includes/header.php"; ?>
	<section id="container">

		<h1><i class="fas fa-th-list"></i> Lista de Categorías</h1>
		<a href="#" class="btn_new" id="nuevoCategoria"><i class="fas fa-plus"></i> Crear Categoría</a>

		<form action="" method="post" class="form_search">
			<input type="text" name="busquedaCategoria" id="busquedaCategoria" placeholder="Buscar Categoría">
		</form>

		<div class="containerTable" id="listaCategorias">
			<!--CONTENIDO AJAX-->
		</div>
		<div class="paginador" id="paginadorCategorias">
			<!--CONTENIDO AJAX-->
		</div>
	</section>

	<?php include "includes/footer.php"; ?>

	<script>
		$(document).ready(function(){
            listaCategorias('', 1);
            
            $('#busquedaCategoria').keyup(function(){
                var busqueda = $(this).val();
                listaCategorias(busqueda, 1);
            });

            $('#nuevoCategoria').click(function(e){
                e.preventDefault();
                $.ajax({
                    url: 'ajax.php',
                    type: 'POST',
                    data: {action: 'editarCategoria', id: 0},
                    success: function(resp){
                        var data;
                        try{
                            data = JSON.parse(resp);
                        }catch(e){
                            console.error('Invalid JSON response (nuevoCategoria):', resp);
                            alert('Respuesta inválida del servidor. Revisa la consola para más detalles.');
                            return;
                        }
                        var marcasHtml = buildMarcasCheckboxes(data.todas_marcas || [], []);
                        var tamanosHtml = buildTamanosCheckboxes(data.todos_tamanos, []);
                        $('.bodyModal').html('<form action="" method="post" name="form_add_categoria" id="form_add_categoria" onsubmit="event.preventDefault(); crearCategoria();">' +
                            '<h1><i class="fas fa-plus-circle" style="font-size: 45pt;"></i> <br> Nueva Categoría</h1>' +
                            '<input type="text" name="nombre" id="nombreCat" placeholder="Nombre de la categoría" required><br>' +
                            marcasHtml +
                            tamanosHtml +
                            '<div class="alert alertAddCategoria"></div>' +
                            '<a href="#" class="btn_ok closeModal" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                            '<button type="submit" class="btn_new"><i class="fas fa-save"></i> Guardar</button>' +
                            '</form>');
                        $('.modal').fadeIn();
                    }
                });
            });
		});

        function buildMarcasCheckboxes(marcas, seleccionados) {
            if (!Array.isArray(seleccionados)) seleccionados = [];
            var html = '<div style="text-align:left; margin:10px 0;">';
            html += '<strong>Marcas de esta categoría:</strong><br>';
            html += '<input type="text" id="searchMarcaCheckbox" placeholder="Buscar marca en la lista..." style="margin-bottom: 5px; width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">';
            html += '<div id="marcasCheckboxList" style="max-height: 140px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 8px; background: #fafafa;">';
            if (marcas && marcas.length > 0) {
                marcas.forEach(function(marca){
                    var checked = (seleccionados.indexOf(parseInt(marca.idmarca)) > -1) ? 'checked' : '';
                    html += '<label class="marca-checkbox-item" style="display: block; padding: 4px 0; cursor: pointer; font-size: 14px;">';
                    html += '<input type="checkbox" name="marcas[]" value="' + marca.idmarca + '" ' + checked + ' style="margin-right: 6px;"> ';
                    html += marca.nombre;
                    html += '</label>';
                });
            } else {
                html += '<p style="color:#999; margin:0;">No hay marcas cargadas aún</p>';
            }
            html += '</div></div>';
            return html;
        }

        function buildTamanosCheckboxes(tamanos, seleccionados) {
            if (!Array.isArray(seleccionados)) seleccionados = [];
            var html = '<div style="text-align:left; margin:10px 0;">';
            html += '<strong>Tamaños de esta categoría:</strong><br>';
            html += '<div style="display:flex; gap:5px; margin: 6px 0 6px 0;">' +
                    '<input type="text" id="nuevoTamanoInline" placeholder="Crear y añadir nuevo tamaño (ej: A4, 500ml)..." style="flex:1; padding:6px; border:1px solid #3498db; border-radius:4px; font-size:13px; box-sizing:border-box;">' +
                    '<button type="button" class="btn_new" style="margin:0; padding:6px 12px; font-size:12px; height:auto; background:#2980b9; line-height:1; display:inline-flex; align-items:center; gap:3px;" onclick="agregarTamanoInline();"><i class="fas fa-plus"></i> Crear</button>' +
                    '</div>';
            html += '<input type="text" id="searchTamCheckbox" placeholder="Buscar tamaño en la lista..." style="margin-bottom: 5px; width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">';
            html += '<div id="tamanosCheckboxList" style="max-height: 140px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 8px; background: #fafafa;">';
            if (tamanos && tamanos.length > 0) {
                tamanos.forEach(function(tam){
                    var checked = (seleccionados.indexOf(parseInt(tam.idtamano)) > -1) ? 'checked' : '';
                    html += '<label class="tam-checkbox-item" style="display: block; padding: 4px 0; cursor: pointer; font-size: 14px;">';
                    html += '<input type="checkbox" name="tamanos[]" value="' + tam.idtamano + '" ' + checked + ' style="margin-right: 6px;"> ';
                    html += tam.nombre;
                    html += '</label>';
                });
            } else {
                html += '<p style="color:#999; margin:0;">No hay tamaños cargados aún</p>';
            }
            html += '</div></div>';
            return html;
        }

        function agregarTamanoInline() {
            var nombre = $('#nuevoTamanoInline').val().trim();
            if (nombre === '') {
                alert('Por favor ingresa un nombre para el nuevo tamaño');
                return;
            }
            $.ajax({
                url: 'ajax_categorias_marcas_tamanos.php',
                type: 'POST',
                data: { action: 'crearTamanoRapido', nombre: nombre },
                success: function(resp) {
                    var info;
                    try{
                        info = JSON.parse(resp);
                    }catch(e){
                        console.error('Invalid JSON response (crearTamanoRapido):', resp);
                        alert('Respuesta inválida del servidor. Revisa la consola para más detalles.');
                        return;
                    }
                    if (info.cod === '00') {
                        $('#tamanosCheckboxList p').remove();
                        var existingInput = $('#tamanosCheckboxList input[value="' + info.idtamano + '"]');
                        if (existingInput.length > 0) {
                            existingInput.prop('checked', true);
                        } else {
                            var newItem = '<label class="tam-checkbox-item" style="display: block; padding: 4px 0; cursor: pointer; font-size: 14px; background:#e8f8f5; border-radius:3px; padding-left:4px;">' +
                                '<input type="checkbox" name="tamanos[]" value="' + info.idtamano + '" checked style="margin-right: 6px;"> ' +
                                info.nombre + ' <span style="font-size:10px; color:#27ae60; font-weight:bold;">(Nuevo)</span>' +
                                '</label>';
                            $('#tamanosCheckboxList').prepend(newItem);
                        }
                        $('#nuevoTamanoInline').val('');
                    } else {
                        alert(info.msg);
                    }
                },
                error: function(err) {
                    alert('Error al crear el tamaño.');
                }
            });
        }

        $(document).on('keyup', '#searchMarcaCheckbox', function(){
            var val = $(this).val().toLowerCase();
            $('.marca-checkbox-item').each(function(){
                var text = $(this).text().toLowerCase();
                if(text.indexOf(val) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        $(document).on('keyup', '#searchTamCheckbox', function(){
            var val = $(this).val().toLowerCase();
            $('.tam-checkbox-item').each(function(){
                var text = $(this).text().toLowerCase();
                if(text.indexOf(val) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        function listaCategorias(busqueda, pagina){
            var action = 'listaCategorias';
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: {action: action, busqueda: busqueda, pagina: pagina},
                success: function(response){
                    var info;
                    try{
                        info = JSON.parse(response);
                    }catch(e){
                        console.error('Invalid JSON response (listaCategorias):', response);
                        $('#listaCategorias').html('<p style="color:red;">Respuesta inválida del servidor. Revisa la consola.</p>');
                        return;
                    }
                    $('#listaCategorias').html(info.tabla);
                    $('#paginadorCategorias').html(info.paginador);
                },
                error: function(error){
                    console.log(error);
                }
            });
        }

        function crearCategoria(){
            var parametros = $('#form_add_categoria').serialize() + '&action=crearCategoria';
            
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: parametros,
                success: function(response){
                    if(response != 'error'){
                        var info;
                        try{
                            info = JSON.parse(response);
                        }catch(e){
                            console.error('Invalid JSON response (crearCategoria):', response);
                            $('.alertAddCategoria').html('<p style="color: red;">Respuesta inválida del servidor. Revisa la consola.</p>');
                            return;
                        }
                        if(info.cod == '00'){
                            $('.alertAddCategoria').html('<p style="color: green;">'+info.msg+'</p>');
                            $('#nombreCat').val('');
                            listaCategorias('', 1);
                            setTimeout(function(){
                                coloseModal();
                            }, 1000);
                        }else{
                            $('.alertAddCategoria').html('<p style="color: red;">'+info.msg+'</p>');
                        }
                    }else{
                         $('.alertAddCategoria').html('<p style="color: red;">Error al guardar</p>');
                    }
                },
                error: function(error){
                    console.log(error);
                }
            });
        }

        function editar_categoria(id){
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: {action: 'editarCategoria', id: id},
                success: function(response){
                    var data;
                    try{
                        data = JSON.parse(response);
                    }catch(e){
                        console.error('Invalid JSON response (editar_categoria):', response);
                        alert('Respuesta inválida del servidor. Revisa la consola para más detalles.');
                        return;
                    }
                    var marcasHtml = buildMarcasCheckboxes(data.todas_marcas || [], data.marcas || []);
                    var tamanosHtml = buildTamanosCheckboxes(data.todos_tamanos, data.tamanos || []);
                    
                    $('.bodyModal').html('<form action="" method="post" name="form_edit_categoria" id="form_edit_categoria" onsubmit="event.preventDefault(); actualizarCategoria('+id+');">' +
                        '<h1><i class="fas fa-edit" style="font-size: 45pt;"></i> <br> Editar Categoría</h1>' +
                        '<input type="text" name="nombre" id="nombreCatEdit" value="'+data.categoria.nombre+'" placeholder="Nombre de la categoría" required><br>' +
                        marcasHtml +
                        tamanosHtml +
                        '<div class="alert alertAddCategoria"></div>' +
                        '<a href="#" class="btn_ok closeModal" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                        '<button type="submit" class="btn_new"><i class="fas fa-save"></i> Guardar Cambios</button>' +
                        '</form>');
                    $('.modal').fadeIn();
                }
            });
        }

        function actualizarCategoria(id){
            var parametros = $('#form_edit_categoria').serialize() + '&action=actualizarCategoria&id=' + id;
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: parametros,
                success: function(response){
                    if(response != 'error'){
                        var info;
                        try{
                            info = JSON.parse(response);
                        }catch(e){
                            console.error('Invalid JSON response (actualizarCategoria):', response);
                            $('.alertAddCategoria').html('<p style="color: red;">Respuesta inválida del servidor. Revisa la consola.</p>');
                            return;
                        }
                        if(info.cod == '00'){
                            $('.alertAddCategoria').html('<p style="color: green;">'+info.msg+'</p>');
                            listaCategorias('', 1);
                            setTimeout(function(){
                                coloseModal();
                            }, 1000);
                        }else{
                            $('.alertAddCategoria').html('<p style="color: red;">'+info.msg+'</p>');
                        }
                    }else{
                         $('.alertAddCategoria').html('<p style="color: red;">Error al guardar</p>');
                    }
                },
                error: function(error){
                    console.log(error);
                }
            });
        }
        
        function del_categoria(id){
            if(confirm('¿Está seguro de que desea eliminar esta categoría?')){
                var action = 'delCategoria';
                $.ajax({
                    url: 'ajax.php',
                    type: 'POST',
                    async: true,
                    data: {action: action, id: id},
                    success: function(response){
                        if(response == 'ok'){
                            listaCategorias('', 1);
                        }else{
                            alert('Error al eliminar');
                        }
                    },
                     error: function(error){
                        console.log(error);
                    }
                });
            }
        }
	</script>
</body>
</html>
