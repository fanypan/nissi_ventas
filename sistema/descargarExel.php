<?php 
 	session_start(); 

	header("Content-Type: application/vnd.ms-excel; charset=utf-8");
	header("Content-Disposition: attachment; filename= lista_productos.xls");
	header("Pragma: no-cache");
	header("Expires: 0");
 ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<style>
		table { border-collapse: collapse; width: 100%; }
		th { background-color: #2e86c1; color: #ffffff; border: 1px solid #000000; padding: 10px; font-weight: bold; text-align: center; }
		td { border: 1px solid #000000; padding: 8px; }
		tr:nth-child(even) { background-color: #f2f2f2; }
	</style>
</head>
<body>
		<table style="border-collapse: collapse; width: 100%;">
				<!-- Título del Reporte -->
				<tr>
					<td colspan="9" style="background-color: #2e86c1; color: white; font-size: 20px; text-align: center; font-weight: bold; padding: 15px; border: 1px solid #000;">
						LISTADO GENERAL DE PRODUCTOS
					</td>
				</tr>
				<!-- Cabeceras -->
				<tr style="background-color: #2e86c1; color: white;">
					<th style="background-color: #2e86c1; color: white; border: 1px solid #000; padding: 10px;">Código</th>
					<th style="background-color: #2e86c1; color: white; border: 1px solid #000; padding: 10px;">Descripción</th>
					<th style="background-color: #2e86c1; color: white; border: 1px solid #000; padding: 10px;">Categoría</th>
					<th style="background-color: #2e86c1; color: white; border: 1px solid #000; padding: 10px;">Marca</th>
					<th style="background-color: #2e86c1; color: white; border: 1px solid #000; padding: 10px;">Tamaño</th>
					<th style="background-color: #2e86c1; color: white; border: 1px solid #000; padding: 10px;">Stock</th>
					<th style="background-color: #2e86c1; color: white; border: 1px solid #000; padding: 10px;">Costo</th>
					<th style="background-color: #2e86c1; color: white; border: 1px solid #000; padding: 10px;">Precio</th>
					<th style="background-color: #2e86c1; color: white; border: 1px solid #000; padding: 10px;">Proveedor</th>
				</tr>
 <?php

include "../conexion.php";

 $query = mysqli_query($conection,"SELECT p.codproducto, p.codigo, p.descripcion,p.costo, p.precio, p.existencia, pr.proveedor, p.status, c.nombre as categoria, m.nombre as marca, t.nombre as tamano FROM producto p
						INNER JOIN proveedor pr ON p.proveedor = pr.codproveedor
						LEFT JOIN categoria c ON p.categoria_id = c.idcategoria
						LEFT JOIN marca m ON p.marca_id = m.idmarca
						LEFT JOIN tamano t ON p.tamano_id = t.idtamano
						WHERE p.status = 1 ORDER BY p.codproducto");
				
				$result = mysqli_num_rows($query);

				if ($result > 0) {
				  
				while ($data = mysqli_fetch_assoc($query)){
					$categoria_txt = !empty($data['categoria']) ? $data['categoria'] : 'Sin asignar';
					$marca_txt = !empty($data['marca']) ? $data['marca'] : 'Sin asignar';
					$tamano_txt = !empty($data['tamano']) ? $data['tamano'] : 'Sin asignar';
				?>
				<tr>
						<td style="border: 1px solid #000; padding: 5px;"><?php echo $data['codigo']; ?></td>
						<td style="border: 1px solid #000; padding: 5px;"><?php echo $data['descripcion'] ; ?></td>
						<td style="border: 1px solid #000; padding: 5px;"><?php echo $categoria_txt ; ?></td>
						<td style="border: 1px solid #000; padding: 5px;"><?php echo $marca_txt ; ?></td>
						<td style="border: 1px solid #000; padding: 5px;"><?php echo $tamano_txt ; ?></td>
						<td style="border: 1px solid #000; padding: 5px; text-align: center;"><?php echo $data['existencia'] ; ?></td>
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