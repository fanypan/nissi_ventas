<nav>
	<ul>
		<li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
		<?php
		if ($_SESSION['rol'] == 1) {

		?>
			<li class="principal">
				<a href="#"><i class="fas fa-user"></i> Usuarios</a>
				<ul>
					<li><a href="lista_usuarios.php"><i class="fas fa-users"></i> Lista de usuarios</a></li>
				</ul>
			</li>
		<?php  } ?>
		<li class="principal">
			<a href="#"><i class="fas fa-users"></i> Clientes</a>
			<ul>
				<li><a href="lista_cliente.php"><i class="fas fa-users"></i> Lista de clientes</a></li>
				<li><a href="cuentas_por_cobrar.php"><i class="far fa-money-bill-alt"></i> Cuentas por cobrar</a></li>
			</ul>
		</li>
		<?php
		if ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2) {

		?>
			<li class="principal">
				<a href="#"><i class="far fa-building"></i> Proveedores</a>
				<ul>
					<li><a href="lista_proveedor.php"><i class="far fa-building"></i> Lista de proveedores</a></li>
					<li><a href="cuentas_por_pagar.php"><i class="fas fa-dollar-sign"></i> Cuentas por pagar</a></li>
				</ul>
			</li>
		<?php  } ?>


		<li class="principal">
			<a href="#"><i class="fas fa-cubes"></i> Productos</a>
			<ul>
				<li><a href="lista_producto.php"><i class="fas fa-cubes"></i> Lista de productos</a></li>
				<li><a href="carga_masiva_productos.php"><i class="fas fa-list-alt"></i> Carga Masiva</a></li>
				<li><a href="editor_masivo_productos.php" style="color:#3498db;"><i class="fas fa-edit"></i> Editor Masivo</a></li>
				<li><a href="importar_fotos.php"><i class="fas fa-file-import"></i> Importar desde Fotos</a></li>
				<?php
				if ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2) {

				?>
					<li><a href="compras.php"><i class="far fa-file-alt fa-w-12"></i> Reporte de Compras</a></li>
					<li><a href="reportes_inventario.php"><i class="fas fa-boxes"></i> Reportes de Inventario</a></li>
					<li><a href="reabastecer_producto.php"><i class="fas fa-cart-plus"></i> Recarga de stock</a></li>
					<?php if ($_SESSION['rol'] == 1) { ?>
						<li><a href="ajuste_stock.php"><i class="fas fa-exchange-alt"></i> Ajuste de Stock</a></li>
					<?php } ?>

					<li><a href="productos_vencer.php"><i class="far fa-file-alt fa-w-12"></i> Productos próximos a vencer</a></li>
					<li><a href="lista_categorias.php"><i class="fas fa-th-list"></i> Categorías</a></li>
					<li><a href="lista_marcas.php"><i class="fas fa-tag"></i> Marcas</a></li>
					<li><a href="lista_tamanos.php"><i class="fas fa-ruler-combined"></i> Tamaños</a></li>
					<li><a href="duplicados_productos.php" style="color:#e74c3c;"><i class="fas fa-clone"></i> Duplicados</a></li>
				<?php  } ?>
			</ul>
		</li>


		<li class="principal">
			<a href="#"><i class="far fa-file-alt"></i> Ventas</a>
			<ul>
				<li><a href="ventas.php"><i class="fas fa-dollar-sign"></i> Reportes de Ventas</a></li>
				<li><a href="nueva_venta.php"><i class="fas fa-plus"></i> Nueva Venta</a></li>
			</ul>
		</li>


		<li class="principal">
			<a href="#"><i class="fas fa-cash-register"></i> Caja</a>
			<ul>
				<li><a href="lista_caja.php"><i class="far fa-money-bill-alt"></i> Apertura y Cierre</a></li>
				<li><a href="lista_egresos.php"><i class="fas fa-dollar-sign"></i> Egresos</a></li>
				<?php if ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2) { ?>
					<?php if ($_SESSION['rol'] == 1) { ?>
						<li><a href="arqueo_caja.php"><i class="fas fa-cash-register"></i> Arqueo de Caja</a></li>
						<li><a href="arqueo_reporte.php"><i class="fas fa-history"></i> Histórico de Arqueos</a></li>
						<li><a href="reporte_egresos.php"><i class="fas fa-file-invoice-dollar"></i> Reporte de Egresos</a></li>
						<li><a href="reporte_consolidado.php"><i class="fas fa-chart-line"></i> Reporte Consolidado</a></li>
					<?php  } ?>
				<?php  } ?>
			</ul>
		</li>

		<li class="principal">
			<a href="#"><i class="fas fa-plus"></i> Otros</a>
			<ul>
				<ul>
					<li><a href="configuracion.php"><i class="fas fa-file-alt"></i> Datos Empresa</a></li>
					<?php if ($_SESSION['rol'] == 1) { ?>
						<li><a href="configuracion_factura_electronica.php"><i class="fas fa-file-invoice"></i> Configuración factura electrónica</a></li>
						<li><a href="uso_insumos.php" style="background: #34495e; color: #fff;"><i class="fas fa-utensils"></i> Consumo de Insumos</a></li>
					<?php } ?>
				</ul>
			</ul>
		</li>


	</ul>
</nav>