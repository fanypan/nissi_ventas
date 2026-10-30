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
	<title>Lista de Marcas</title>
</head>
<body>
	<?php include "includes/header.php"; ?>
	<section id="container">

		<h1><i class="fas fa-tag"></i> Lista de Marcas</h1>
		<a href="#" class="btn_new" id="nuevoMarca"><i class="fas fa-plus"></i> Crear Marca</a>

		<form action="" method="post" class="form_search">
			<input type="text" name="busquedaMarca" id="busquedaMarca" placeholder="Buscar Marca">
		</form>

		<div class="containerTable" id="listaMarcas">
			<!--CONTENIDO AJAX-->
		</div>
		<div class="paginador" id="paginadorMarcas">
			<!--CONTENIDO AJAX-->
		</div>
	</section>

	<?php include "includes/footer.php"; ?>

	<script>
		$(document).ready(function(){
            listaMarcas('', 1);

            $('#busquedaMarca').keyup(function(){
                var busqueda = $(this).val();
                listaMarcas(busqueda, 1);
            });

            $('#nuevoMarca').click(function(e){
                e.preventDefault();
                $('.bodyModal').html('<form action="" method="post" name="form_add_marca" id="form_add_marca" onsubmit="event.preventDefault(); crearMarca();">' +
                    '<h1><i class="fas fa-plus-circle" style="font-size: 45pt;"></i> <br> Nueva Marca</h1>' +
                    '<input type="text" name="nombre" id="nombreMarca" placeholder="Nombre de la marca" required><br>' +
                    '<div class="alert alertAddMarca"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-save"></i> Guardar</button>' +
                    '</form>');
                $('.modal').fadeIn();
            });
		});

        function listaMarcas(busqueda, pagina){
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: {action: 'listaMarcas', busqueda: busqueda, pagina: pagina},
                success: function(response){
                    var info = JSON.parse(response);
                    $('#listaMarcas').html(info.tabla);
                    $('#paginadorMarcas').html(info.paginador);
                },
                error: function(error){ console.log(error); }
            });
        }

        function crearMarca(){
            var parametros = $('#form_add_marca').serialize() + '&action=crearMarca';
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: parametros,
                success: function(response){
                    if(response != 'error'){
                        var info = JSON.parse(response);
                        if(info.cod == '00'){
                            $('.alertAddMarca').html('<p style="color:green;">'+info.msg+'</p>');
                            listaMarcas('', 1);
                            setTimeout(function(){ coloseModal(); }, 1000);
                        }else{
                            $('.alertAddMarca').html('<p style="color:red;">'+info.msg+'</p>');
                        }
                    }else{
                        $('.alertAddMarca').html('<p style="color:red;">Error al guardar</p>');
                    }
                },
                error: function(error){ console.log(error); }
            });
        }

        function editar_marca(id){
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: {action: 'editarMarca', id: id},
                success: function(response){
                    var data = JSON.parse(response);
                    $('.bodyModal').html('<form action="" method="post" name="form_edit_marca" id="form_edit_marca" onsubmit="event.preventDefault(); actualizarMarca('+id+');">' +
                        '<h1><i class="fas fa-edit" style="font-size: 45pt;"></i> <br> Editar Marca</h1>' +
                        '<input type="text" name="nombre" id="nombreMarcaEdit" value="'+data.marca.nombre+'" placeholder="Nombre de la marca" required><br>' +
                        '<div class="alert alertAddMarca"></div>' +
                        '<a href="#" class="btn_ok closeModal" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                        '<button type="submit" class="btn_new"><i class="fas fa-save"></i> Guardar Cambios</button>' +
                        '</form>');
                    $('.modal').fadeIn();
                }
            });
        }

        function actualizarMarca(id){
            var parametros = $('#form_edit_marca').serialize() + '&action=actualizarMarca&id=' + id;
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: parametros,
                success: function(response){
                    if(response != 'error'){
                        var info = JSON.parse(response);
                        if(info.cod == '00'){
                            $('.alertAddMarca').html('<p style="color:green;">'+info.msg+'</p>');
                            listaMarcas('', 1);
                            setTimeout(function(){ coloseModal(); }, 1000);
                        }else{
                            $('.alertAddMarca').html('<p style="color:red;">'+info.msg+'</p>');
                        }
                    }else{
                        $('.alertAddMarca').html('<p style="color:red;">Error al guardar</p>');
                    }
                },
                error: function(error){ console.log(error); }
            });
        }

        function del_marca(id){
            if(confirm('¿Está seguro de que desea eliminar esta marca?')){
                $.ajax({
                    url: 'ajax.php',
                    type: 'POST',
                    async: true,
                    data: {action: 'delMarca', id: id},
                    success: function(response){
                        if(response == 'ok'){
                            listaMarcas('', 1);
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
