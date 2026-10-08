<?php
session_start();
if ($_SESSION['rol'] != 1) {
	header("location: ./");
}

include "../conexion.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Lista de Motivos</title>
</head>
<body>
	<?php include "includes/header.php"; ?>
	<section id="container">

		<h1><i class="fas fa-list-alt"></i> Lista de Motivos de Ajuste</h1>
		<a href="#" class="btn_new" id="nuevoMotivo"><i class="fas fa-plus"></i> Crear Motivo</a>

		<form action="" method="post" class="form_search">
			<input type="text" name="busquedaMotivo" id="busquedaMotivo" placeholder="Buscar Motivo">
		</form>

		<div class="containerTable" id="listaMotivos">
			<!--CONTENIDO AJAX-->
		</div>
		<div class="paginador" id="paginadorMotivos">
			<!--CONTENIDO AJAX-->
		</div>
	</section>

	<?php include "includes/footer.php"; ?>

	<script>
		$(document).ready(function(){
            listaMotivos('', 1);
            
            $('#busquedaMotivo').keyup(function(){
                var busqueda = $(this).val();
                listaMotivos(busqueda, 1);
            });

            $('#nuevoMotivo').click(function(e){
                e.preventDefault();
                $('.bodyModal').html('<form action="" method="post" name="form_add_motivo" id="form_add_motivo" onsubmit="event.preventDefault(); crearMotivo();">' +
                    '<h1><i class="fas fa-plus-circle" style="font-size: 45pt;"></i> <br> Nuevo Motivo</h1>' +
                    '<input type="text" name="motivo" id="motivo" placeholder="Descripción del motivo" required><br>' +
                    '<div class="alert alertAddMotivo"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-save"></i> Guardar</button>' +
                    '</form>');
                $('.modal').fadeIn();
            });
		});

        function listaMotivos(busqueda, pagina){
            var action = 'listaMotivos';
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: {action: action, busqueda: busqueda, pagina: pagina},
                success: function(response){
                    var info = JSON.parse(response);
                    $('#listaMotivos').html(info.tabla);
                    $('#paginadorMotivos').html(info.paginador);
                },
                error: function(error){
                    console.log(error);
                }
            });
        }

        function crearMotivo(){
            var motivo = $('#motivo').val();
            var action = 'crearMotivo';
            
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: {action: action, motivo: motivo},
                success: function(response){
                    if(response != 'error'){
                        var info = JSON.parse(response);
                        if(info.cod == '00'){
                            $('.alertAddMotivo').html('<p style="color: green;">'+info.msg+'</p>');
                            $('#motivo').val('');
                            listaMotivos('', 1);
                        }else{
                            $('.alertAddMotivo').html('<p style="color: red;">'+info.msg+'</p>');
                        }
                    }else{
                         $('.alertAddMotivo').html('<p style="color: red;">Error al guardar</p>');
                    }
                },
                error: function(error){
                    console.log(error);
                }
            });
        }
        
        function del_motivo(id){
            var action = 'delMotivo';
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                async: true,
                data: {action: action, id: id},
                success: function(response){
                    if(response == 'ok'){
                        listaMotivos('', 1);
                    }else{
                        alert('Error al eliminar');
                    }
                },
                 error: function(error){
                    console.log(error);
                }
            });
        }
	</script>
</body>
</html>
