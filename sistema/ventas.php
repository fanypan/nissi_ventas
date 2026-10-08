<?php
session_start(); 

include "../conexion.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"?>
	<title>Lista de ventas</title>
</head>
<body>
	<?php include "includes/header.php"?>
	<section id="container">

		<h1><i class="far fa-newspaper"></i> Lista de ventas</h1>
		<a href="nueva_venta.php" class="btn_new btnNewVenta"><i class="fas fa-plus"></i> Nueva venta</a>
		<form action="" method="post" class="form_search">
			<input type="text" name="busquedaVentas" id="busquedaVentas" placeholder="Buscar">
		</form>

		<div>
			<h5>Buscar por fecha</h5>
			<form action="" method="post" class="form_search_date" id="rango">
				<label>De:</label>
				<input type="date" name="fecha_de" id="fecha_de" required>
				<label>A</label>
				<input type="date" name="fecha_a" id="fecha_a" required>
				
				<label style="margin-left: 10px;">Producto:</label>
				<select name="filtro_producto" id="filtro_producto" style="width: 150px;">
					<option value="">Todos</option>
					<?php
						$query_p = mysqli_query($conection, "SELECT codproducto, descripcion FROM producto WHERE status = 1 ORDER BY descripcion ASC");
						while ($p = mysqli_fetch_assoc($query_p)) {
							echo '<option value="'.$p['codproducto'].'">'.$p['descripcion'].'</option>';
						}
					?>
				</select>

				<label style="margin-left: 10px;">Pago:</label>
				<select name="filtro_pago" id="filtro_pago" style="width: 120px;">
					<option value="">Todos</option>
					<option value="1">Efectivo</option>
					<option value="2">Transferencia</option>
					<option value="4">QR</option>
					<option value="5">Tarjeta</option>
					<option value="otros">Otros (Trans/QR/Tarj)</option>
				</select>

				<button type="submit" class="btn_view btn_rango_fecha" title="Filtrar Lista"><i class="fas fa-search"></i></button>
				<a href="#" class="btn_view" id="reporte_pdf" title="Descargar PDF" style="background-color: #e74c3c;"><i class="fas fa-file-pdf"></i> PDF</a>
				<a href="#" class="btn_view" id="descargar_excel_ventas" style="background-color: #2ecc7 green1; border-color: #2ecc71;" title="Exportar a Excel"><i class="fas fa-file-excel"></i> Excel</a>
				<a href="#" class="btn_new" id="devolucion"><i class="fas fa-undo-alt"></i> Devolución</a>
			</form>
			
		</div>
		<div style="width: 120px; margin-bottom: 5px">
						
						<p>
							<strong>Mostrar por : </strong>
							<select name="cantidad_mostrar_ventas" id="cantidad_mostrar_ventas">
								<option value="10">10</option>
								<option value="25">25</option>
								<option value="50">50</option>
								<option value="100">100</option>
							</select>
						</p>

					</div>
		<div class="containerTable" id="listaVentas">
			<!--CONTENIDO AJAX-->
		</div>
		<div class="paginador" id="paginadorVentas">
			<!--CONTENIDO AJAX-->
		</div>
	</section>

		<?php include "includes/footer.php"?>

</body>
</html>