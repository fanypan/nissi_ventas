<?php 
 	session_start(); 

	header("Content-Type: application/vnd.ms-excel; charset=utf-8");
	header("Content-Disposition: attachment; filename= productos_sin_existencia.xls");
	header("Pragma: no-cache");
	header("Expires: 0");
 ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<style>
		table { border-collapse: collapse; width: 100%; }
		th { background-color: #c0392b; color: #ffffff; border: 1px solid #000000; padding: 10px; font-weight: bold; text-align: center; }
		td { border: 1px solid #000000; padding: 8px; }
		tr:nth-child(even) { background-color: #f2f2f2; }
	</style>
</head>
<body>
		<table style="border-collapse: collapse; width: 100%;">
				<!-- Título del Reporte -->
				<tr>
					<td colspan="6" style="background-color: #e74c3c; color: white; font-size: 20px; text-align: center; font-weight: bold; padding: 15px; border: 1px solid #000;">
						LISTA DE PRODUCTOS SIN EXISTENCIA (STOCK 0)
					</td>
				</tr>
				<!-- Cabeceras -->
				<tr style="background-color: #c0392b; color: white;">
					<th style="background-color: #c0392b; color: white; border: 1px solid #000; padding: 10px;">Código</th>
					<th style="background-color: #c0392b; color: white; border: 1px solid #000; padding: 10px;">Descripción</th>
					<th style="background-color: #c0392b; color: white; border: 1px solid #000; padding: 10px;">Existencia</th>
					<th style="background-color: #c0392b; color: white; border: 1px solid #000; padding: 10px;">Costo</th>
					<th style="background-color: #c0392b; color: white; border: 1px solid #000; padding: 10px;">Precio</th>
					<th style="background-color: #c0392b; color: white; border: 1px solid #000; padding: 10px;">Proveedor</th>
				</tr>
 <?php
 // ... (query code remains same, only loop content changes)
include "../conexion.php";

 $query = mysqli_query($conection,"SELECT p.codproducto, p.codigo, p.descripcion,p.costo, p.precio, p.existencia, pr.proveedor, p.status FROM producto p
						INNER JOIN proveedor pr
						ON p.proveedor = pr.codproveedor
						WHERE p.status = 1 AND p.existencia = 0 ORDER BY p.codproducto");
				
				$result = mysqli_num_rows($query);

				if ($result > 0) {
				  
				while ($data = mysqli_fetch_assoc($query)){
	
				?>
				<tr>
						<td style="border: 1px solid #000; padding: 5px;"><?php echo $data['codigo']; ?></td>
						<td style="border: 1px solid #000; padding: 5px;"><?php echo $data['descripcion'] ; ?></td>
						<td style="border: 1px solid #000; padding: 5px; text-align: center; background-color: #ffcccc; color: red; font-weight: bold;"><?php echo $data['existencia'] ; ?></td>
						<td style="border: 1px solid #000; padding: 5px;"><?php echo $data['costo']; ?></td>
						<td style="border: 1px solid #000; padding: 5px;"><?php echo $data['precio']; ?></td>
						<td style="border: 1px solid #000; padding: 5px;"><?php echo $data['proveedor'] ; ?></td>
				</tr>

				<?php 
				}
			} 
		?>
		</table>
</body>
</html>