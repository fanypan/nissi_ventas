<?php
session_start(); 

include "../conexion.php";

//$query = mysqli_query($conection,"SELECT * FROM caja WHERE usuario = $user AND status = 1");
		$query_caja = mysqli_query($conection,"SELECT * FROM caja WHERE status = 1");
		$result_caja = mysqli_num_rows($query_caja);
		if ($result_caja > 0) {
			 		$data_caja = mysqli_fetch_assoc($query_caja);
			 		$id_caja = $data_caja['id'];
			 		$query_dash = mysqli_query($conection,"CALL dataDashboard($id_caja);");
		$result_das = mysqli_num_rows($query_dash);
		if ($result_das > 0) {
			$data_dash = mysqli_fetch_assoc($query_dash);
			$inicio = $data_dash['inicios'];
			$ventas = $data_dash['ventas'];
			$abonos = $data_dash['abonos'];
			$creditos = $data_dash['credito'];
			$egreso = $data_dash['egreso'];
			$total = $inicio + $ventas + $abonos - $egreso;
			mysqli_close($conection);
		}
			 		}



?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"?>
	<title>Lista de egresos</title>
</head>
<body>
	<?php include "includes/header.php"?>
	<section id="container">
		<input type="hidden" name="total_caja" id="total_caja" value="<?= $total; ?>">
		<h1><i class="fa fa-file-alt fa-w-12"></i> Lista de egresos</h1>
		<a href="#" class="btn_new" id="nuevoEgreso"><i class="fas fa-plus"></i> Nuevo egreso</a>

		<form action="" method="" class="form_search" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; background: #eee; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <div style="flex: 1; min-width: 150px;">
                <label>Desde:</label>
                <input type="date" name="fecha_de" id="fecha_de" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" style="width: 100%;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label>Hasta:</label>
                <input type="date" name="fecha_a" id="fecha_a" value="<?php echo date('Y-m-d'); ?>" style="width: 100%;">
            </div>
            <div style="flex: 1; min-width: 200px;">
                <label>Buscar:</label>
                <input type="text" name="busquedaEgresos" id="busquedaEgresos" placeholder="Descripción..." style="width: 100%;">
            </div>
            <div style="display: flex; gap: 5px;">
                <button type="button" class="btn_view" onclick="listaGastosFiltrados()"><i class="fas fa-search"></i> Filtrar</button>
                <button type="button" class="btn_save" onclick="generarReporteEgreso()"><i class="fas fa-file-pdf"></i> PDF</button>
            </div>
		</form>
		<div style="width: 120px; margin-bottom: 5px">
						
						<p>
							<strong>Mostrar por : </strong>
							<select name="cantidad_mostrar_egresos" id="cantidad_mostrar_egresos">
								<option value="10">10</option>
								<option value="25">25</option>
								<option value="50">50</option>
								<option value="100">100</option>
							</select>
						</p>

					</div>
		<div class="containerTable" id="listaEgresos">
			<!--CONTENIDO AJAX-->
		</div>
		<div class="paginador" id="paginadoEgresos">
			<!--CONTENIDO AJAX-->
		</div>
	</section>


		<?php include "includes/footer.php"?>
	<script src="js/egresos_mejorado.js?v=1.1"></script>
</body>
</html>