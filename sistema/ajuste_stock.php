<?php
session_start();
include "../conexion.php";

// Solo Administrador puede acceder
if ($_SESSION['rol'] != 1) {
	header('location: index.php');
	exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="utf-8">
	<?php include "includes/scripts.php"; ?>
	<title>Ajuste de Stock</title>
</head>

<body>
	<?php
	include "includes/header.php";
	?>

	<section id="container">
		<div class="title_page">
			<h1><i class="fas fa-exchange-alt"></i> Ajuste de Stock</h1>
		</div>

		<div class="datos_ajuste">
			<div class="header_ajuste">
				<label><i class="fas fa-search"></i> Buscar Producto:</label>
				<input type="text" name="txt_search_product_simple" id="txt_search_product_simple" placeholder="Escriba nombre o código..." autocomplete="off" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
				<div id="results_product_simple" style="margin-top: 10px; background: #fff;"></div>
				<div id="pagination_product_simple" class="paginador"></div>
			</div>

			<form method="post" name="form_ajuste" id="form_ajuste" onsubmit="event.preventDefault(); procesarAjuste();">
				<div class="body_ajuste">
					<div class="wd40">
						<label>Producto:</label>
						<div id="info_producto_ajuste">-</div>
						<input type="hidden" name="producto_id_ajuste" id="producto_id_ajuste" required>
					</div>
					<div class="wd20">
						<label>Stock Actual:</label>
						<div id="stock_actual_ajuste">-</div>
					</div>
					<div class="wd20">
						<label>Cantidad a Ajustar:</label>
						<input type="number" name="cantidad_ajuste" id="cantidad_ajuste" placeholder="0" required min="1">
					</div>
					<div class="wd20">
						<label>Tipo de Ajuste:</label>
						<select name="tipo_ajuste" id="tipo_ajuste" required>
							<option value="1">Entrada (+)</option>
							<option value="2">Salida (-)</option>
						</select>
					</div>
					<div class="wd40">
						<label>Motivo:</label>
						<select name="motivo_ajuste" id="motivo_ajuste" required>
							<option value="">Seleccione Motivo</option>
						</select>
					</div>
					<div class="wd60">
						<label>Nota (Opcional):</label>
						<input type="text" name="nota_ajuste" id="nota_ajuste" placeholder="Detalle adicional">
					</div>
				</div>
				<div class="actions_ajuste">
					<button type="submit" class="btn_save"><i class="far fa-save"></i> Guardar Ajuste</button>
				</div>
			</form>
		</div>

		<!-- Historial de Ajustes -->
		<div class="containerTable">
			<h3><i class="fas fa-history"></i> Historial de Ajustes Recientes</h3>
			<div class="search_historial" style="margin-bottom: 10px;">
				<input type="text" name="busqueda_historial" id="busqueda_historial" placeholder="Buscar por producto o motivo">
			</div>
			<table class="tbl_venta">
				<thead>
					<tr>
						<th>ID</th>
						<th>Fecha</th>
						<th>Producto</th>
						<th>Tipo</th>
						<th>Cantidad</th>
						<th>Motivo</th>
						<th>Usuario</th>
					</tr>
				</thead>
				<tbody id="historialAjustes">
					<!-- AJAX carga aquí -->
				</tbody>
			</table>
			<div class="paginador_ajuste" style="margin-top: 10px; text-align: center;"></div>
		</div>
	</section>

	<script type="text/javascript">
		$(document).ready(function() {
			// Cargar los motivos al iniciar
			cargarMotivosSelect();
			listaHistorialAjustes('', 1);
		});

		// --- Funciones para Buscador Simplificado ---
		$('#txt_search_product_simple').keyup(function(e) {
			e.preventDefault();
			let valorBusqueda = $(this).val();
			if (valorBusqueda != "") {
				buscarProductoSimpleAjuste(valorBusqueda, 1);
			} else {
				$('#results_product_simple').html('');
				$('#pagination_product_simple').html('');
			}
		});

		function buscarProductoSimpleAjuste(busqueda, pagina) {
			$.ajax({
				url: 'action/data_producto_ajuste.php?v=' + new Date().getTime(),
				type: "POST",
				data: { pagina: pagina, busquedaProd: busqueda },
				success: function(response) {
					if (response != 'error') {
						let info = JSON.parse(response);
						$('#results_product_simple').html(info.detalle);
						$('#pagination_product_simple').html(info.totales);
					} else {
						$('#results_product_simple').html('<p class="textcenter">No se encontraron productos.</p>');
						$('#pagination_product_simple').html('');
					}
				}
			});
		}

		// Manejar paginación del buscador
		$("body").on("click", "#pagination_product_simple li a", function(e) {
			e.preventDefault();
			let valorhref = $(this).attr("href");
			let valorBuscar = $("#txt_search_product_simple").val();
			buscarProductoSimpleAjuste(valorBuscar, valorhref);
		});

		function seleccionarProductoAjuste(id) {
			$.ajax({
				url: 'ajax.php?v=' + new Date().getTime(),
				type: 'POST',
				data: { action: 'infoProducto', producto: id },
				success: function(response) {
					if (response != 'error') {
						let info = JSON.parse(response);
						$('#producto_id_ajuste').val(info.codproducto);
						$('#info_producto_ajuste').html('<strong style="color: #3498db;">[' + info.codigo + ']</strong> ' + info.descripcion);
						$('#stock_actual_ajuste').html('<span style="font-weight: bold; font-size: 1.2em;">' + info.existencia + '</span>');
						
						// Limpiar buscador y resultados
						$('#txt_search_product_simple').val('');
						$('#results_product_simple').html('');
						$('#pagination_product_simple').html('');
						
						$('#cantidad_ajuste').focus();
					}
				}
			});
		}
		// --- Fin Funciones Buscador ---

		function cargarMotivosSelect() {
			$.ajax({
				url: 'ajax.php',
				type: 'POST',
				data: { action: 'getMotivosSelect' },
				success: function(response) {
					if (response != 'error') {
						var motivos = JSON.parse(response);
						var options = '<option value="">Seleccione Motivo</option>';
						motivos.forEach(function(m) {
							options += '<option value="' + m.id + '">' + m.descripcion + '</option>';
						});
						$('#motivo_ajuste').html(options);
					}
				}
			});
		}

		function procesarAjuste() {
			let datos = $('#form_ajuste').serialize();
			let action = 'procesarAjuste';
			
			// Preparar data para ajax_ajuste.php
			let id = $('#producto_id_ajuste').val();
			let cant = $('#cantidad_ajuste').val();
			let tipo = $('#tipo_ajuste').val();
			let motivo = $('#motivo_ajuste').val();
			let nota = $('#nota_ajuste').val();

			if(id == '' || cant == '' || motivo == ''){
				alert("Complete todos los campos obligatorios");
				return false;
			}

			$.ajax({
				url: 'ajax.php',
				type: 'POST',
				data: {
					action: action,
					producto_id: id,
					cantidad: cant,
					tipo: tipo,
					motivo_id: motivo,
					nota: nota
				},
				success: function(response) {
					let res = JSON.parse(response);
					if(res.cod == '00') {
						alert(res.msg);
						$('#form_ajuste')[0].reset();
						$('#info_producto_ajuste').html('-');
						$('#stock_actual_ajuste').html('-');
						listaHistorialAjustes('', 1);
					} else {
						alert(res.msg);
					}
				}
			});
		}

		function listaHistorialAjustes(busqueda, pagina) {
			$.ajax({
				url: 'ajax.php',
				type: 'POST',
				data: { action: 'listaHistorialAjustes', busqueda: busqueda, pagina: pagina },
				success: function(response) {
					if(response != 'error') {
						let info = JSON.parse(response);
						$('#historialAjustes').html(info.tabla);
						$('.paginador_ajuste').html(info.paginador);
					}
				}
			});
		}

		$('#busqueda_historial').keyup(function() {
			listaHistorialAjustes($(this).val(), 1);
		});

	</script>
</body>

</html>
