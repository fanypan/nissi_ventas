<?php
session_start();
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
	<title>Lista de Tamaños</title>
</head>
<body>
	<?php include "includes/header.php"; ?>
	<section id="container">

		<h1><i class="fas fa-ruler-combined"></i> Lista de Tamaños</h1>
		<a href="#" class="btn_new" id="nuevoTamano"><i class="fas fa-plus"></i> Crear Tamaño</a>

		<form action="" method="post" class="form_search">
			<input type="text" name="busquedaTamano" id="busquedaTamano" placeholder="Buscar Tamaño">
		</form>

		<div class="containerTable" id="listaTamanos">
			<!--CONTENIDO AJAX-->
		</div>
		<div class="paginador" id="paginadorTamanos">
			<!--CONTENIDO AJAX-->
		</div>
	</section>

	<?php include "includes/footer.php"; ?>

	<script>
		$(document).ready(function(){
            listaTamanos('', 1);

            $('#busquedaTamano').keyup(function(){
                var busqueda = $(this).val();
                listaTamanos(busqueda, 1);
            });

            $('#nuevoTamano').click(function(e){
                e.preventDefault();
                $('.bodyModal').html('<form action="" method="post" name="form_add_tamano" id="form_add_tamano" onsubmit="event.preventDefault(); crearTamano();">' +
                    '<h1><i class="fas fa-plus-circle" style="font-size: 45pt;"></i> <br> Nuevo Tamaño</h1>' +
                    '<input type="text" name="nombre" id="nombreTamano" placeholder="Ej: A4, Oficio, 500ml, N° 5" required><br>' +
                    '<div class="alert alertAddTamano"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-save"></i> Guardar</button>' +
                    '</form>');
                $('.modal').fadeIn();
            });
		});

        function listaTamanos(busqueda, pagina){
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: {action: 'listaTamanos', busqueda: busqueda, pagina: pagina},
                success: function(response){
                    var info = JSON.parse(response);
                    $('#listaTamanos').html(info.tabla);
                    $('#paginadorTamanos').html(info.paginador);
                },
                error: function(error){ console.log(error); }
            });
        }

        function crearTamano(){
            var parametros = $('#form_add_tamano').serialize() + '&action=crearTamano';
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: parametros,
                success: function(response){
                    if(response != 'error'){
                        var info = JSON.parse(response);
                        if(info.cod == '00'){
                            $('.alertAddTamano').html('<p style="color:green;">'+info.msg+'</p>');
                            listaTamanos('', 1);
                            setTimeout(function(){ coloseModal(); }, 1000);
                        }else{
                            $('.alertAddTamano').html('<p style="color:red;">'+info.msg+'</p>');
                        }
                    }else{
                        $('.alertAddTamano').html('<p style="color:red;">Error al guardar</p>');
                    }
                },
                error: function(error){ console.log(error); }
            });
        }

        function editar_tamano(id){
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: {action: 'editarTamano', id: id},
                success: function(response){
                    var data = JSON.parse(response);
                    $('.bodyModal').html('<form action="" method="post" name="form_edit_tamano" id="form_edit_tamano" onsubmit="event.preventDefault(); actualizarTamano('+id+');">' +
                        '<h1><i class="fas fa-edit" style="font-size: 45pt;"></i> <br> Editar Tamaño</h1>' +
                        '<input type="text" name="nombre" id="nombreTamanoEdit" value="'+data.tamano.nombre+'" placeholder="Ej: A4, Oficio, 500ml, N° 5" required><br>' +
                        '<div class="alert alertAddTamano"></div>' +
                        '<a href="#" class="btn_ok closeModal" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                        '<button type="submit" class="btn_new"><i class="fas fa-save"></i> Guardar Cambios</button>' +
                        '</form>');
                    $('.modal').fadeIn();
                }
            });
        }

        function actualizarTamano(id){
            var parametros = $('#form_edit_tamano').serialize() + '&action=actualizarTamano&id=' + id;
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: parametros,
                success: function(response){
                    if(response != 'error'){
                        var info = JSON.parse(response);
                        if(info.cod == '00'){
                            $('.alertAddTamano').html('<p style="color:green;">'+info.msg+'</p>');
                            listaTamanos('', 1);
                            setTimeout(function(){ coloseModal(); }, 1000);
                        }else{
                            $('.alertAddTamano').html('<p style="color:red;">'+info.msg+'</p>');
                        }
                    }else{
                        $('.alertAddTamano').html('<p style="color:red;">Error al guardar</p>');
                    }
                },
                error: function(error){ console.log(error); }
            });
        }

        function del_tamano(id){
            if(confirm('¿Está seguro de que desea eliminar este tamaño?')){
                $.ajax({
                    url: 'ajax.php',
                    type: 'POST',
                    async: true,
                    data: {action: 'delTamano', id: id},
                    success: function(response){
                        if(response == 'ok'){
                            listaTamanos('', 1);
                        }else{
                            alert('Error al eliminar');
                        }
                    },
                    error: function(error){ console.log(error); }
                });
            }
        }
	</script>
</body>
</html>
