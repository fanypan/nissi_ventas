<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);




include "../conexion.php";
nissi_ensure_session();

//print_r($_POST);exit;

if (!empty($_POST)) {

	//$query = mysqli_query($conection,"SELECT * FROM caja WHERE usuario = $user AND status = 1");
	$query_caja = mysqli_query($conection, "SELECT * FROM caja WHERE status = 1");
	$result_caja = mysqli_num_rows($query_caja);
	$id_caja = 0; // Inicializar por defecto
	if ($result_caja > 0) {
		$data_caja = mysqli_fetch_assoc($query_caja);
		$id_caja = $data_caja['id'];
	}

	// ============================================
	// INCLUIR MÓDULO DE EGRESOS MEJORADO
	// ============================================
	include 'ajax_egresos.php';

	// ============================================
	// INCLUIR MÓDULO DE AJUSTE DE STOCK Y MOTIVOS
	// ============================================
	include 'ajax_ajuste.php';

	// ============================================
	// INCLUIR MÓDULO DE CATEGORIAS, MARCAS Y TAMAÑOS
	// ============================================
	include 'ajax_categorias_marcas_tamanos.php';


	//extraer datos del producto
	if ($_POST['action'] == 'infoProducto') {
		$producto_id = $_POST['producto'];

		$query_conf = mysqli_query($conection, "SELECT moneda FROM configuracion");
		$result_conf = mysqli_num_rows($query_conf);

		if ($result_conf > 0) {
			$info_conf = mysqli_fetch_assoc($query_conf);
			$moned = $info_conf['moneda'];
		}

		$query = mysqli_query($conection, "SELECT * FROM producto
												WHERE codigo LIKE '$producto_id' OR
													  codproducto LIKE '$producto_id'
													  AND status = 1 ");
		mysqli_close($conection);

		$result = mysqli_num_rows($query);
		if ($result > 0) {
			$data = mysqli_fetch_assoc($query);
			echo json_encode($data, JSON_UNESCAPED_UNICODE);
			exit;
		}
		echo 'error';
		exit;
	}

	//extraer datos del producto
	if ($_POST['action'] == 'infoProductoEnter') {
		$producto_id = $_POST['producto'];

		$query_conf = mysqli_query($conection, "SELECT moneda FROM configuracion");
		$result_conf = mysqli_num_rows($query_conf);

		if ($result_conf > 0) {
			$info_conf = mysqli_fetch_assoc($query_conf);
			$moned = $info_conf['moneda'];
		}

		$query = mysqli_query($conection, "SELECT * FROM producto
												WHERE codigo = '$producto_id'
													  AND status = 1 ");
		mysqli_close($conection);

		$result = mysqli_num_rows($query);
		if ($result > 0) {
			$data = mysqli_fetch_assoc($query);
			echo json_encode($data, JSON_UNESCAPED_UNICODE);
			exit;
		}
		echo 'error';
		exit;
	}
	//Agregar producto a entrada
	if ($_POST['action'] == 'addProduct') {
		if (!empty($_POST['cantidad']) || !empty($_POST['precio']) || !empty($_POST['producto_id'])) {
			$cantidad    = $_POST['cantidad'];
			$precio      = $_POST['precio'];
			$producto_id = $_POST['producto_id'];
			$usuario_id  = $_SESSION['idUser'];

			$query_insert = mysqli_query($conection, "INSERT INTO entradas(codproducto,
		 																	  cantidad,
		 																	  precio,
		 																	  usuario_id) 
		 														        VALUES($producto_id,
		 														               $cantidad,
		 														               $precio,
		 														               $usuario_id)");
			if ($query_insert) {
				//Ejecutar procedimiento almacenado
				$query_upd = mysqli_query($conection, "CALL actualizar_precio_producto($cantidad,$precio,$producto_id)");
				$result_pro = mysqli_num_rows($query_upd);
				if ($result_pro > 0) {
					$data = mysqli_fetch_assoc($query_upd);
					$data['producto_id'] = $producto_id;
					echo json_encode($data, JSON_UNESCAPED_UNICODE);
					exit;
				}
			} else {
				echo 'error';
			}
			mysqli_close($conection);
		} else {
			echo 'error';
		}
		exit;
	}

	//Eliminar producto
	if ($_POST['action'] == 'delProduct') {
		if (empty($_POST['producto_id']) || !is_numeric($_POST['producto_id'])) {
			echo 'error';
		} else {
			$idproducto = $_POST['producto_id'];

			//$query_delete = mysqli_query($conection,"DELETE FROM usuario WHERE idusuario = $idusuario ");
			$query_delete = mysqli_query($conection, "UPDATE producto SET status = 0 WHERE codproducto = $idproducto ");
			mysqli_close($conection);
			if ($query_delete) {
				echo 'ok';
			} else {
				echo 'error';
			}
		}
		echo 'error';
		exit;
	}
	//Buscar Cliente
	if ($_POST['action'] == 'searchCliente') {
		if (!empty($_POST['cliente'])) {
			$nombre = $_POST['cliente'];

			$query = mysqli_query($conection, "SELECT * FROM cliente WHERE nombre LIKE '$nombre' and status = 1");
			mysqli_close($conection);
			$result = mysqli_num_rows($query);

			$data = '';
			if ($result > 0) {
				$data = mysqli_fetch_assoc($query);
			} else {
				$data = 0;
			}
			echo json_encode($data, JSON_UNESCAPED_UNICODE);
		}
		exit;
	}

	//Registro Cliente - Ventas
	if ($_POST['action'] == 'addCliente') {
		$nit        = $_POST['nit_cliente'];
		$nombre     = $_POST['nom_cliente'];
		$telefono   = $_POST['tel_cliente'];
		$direccion  = $_POST['dir_cliente'];
		$usuario_id = $_SESSION['idUser'];

		$query = mysqli_query($conection, "SELECT * FROM cliente WHERE nit = '$nit' ");
		$result = mysqli_fetch_array($query);

		if ($result > 0) {
			$msg = 'El número de CI ya existe.';
		} else {
			$query_insert = mysqli_query($conection, "INSERT INTO cliente(nit,nombre,telefono,direccion,usuario_id)
																	VALUES('$nit','$nombre','$telefono','$direccion','$usuario_id')");
		}
		mysqli_close($conection);
		if ($query_insert) {
			$codCliente = mysqli_insert_id($conection);
			$msg = $codCliente;
		} else {
			$msg = 'error';
		}
		echo $msg;
		exit;
	}

	//Agregar producto al detalle temporal
	if ($_POST['action'] == 'addProductoDetalle') {
		//print_r($_POST);
		if (empty($_POST['producto']) || empty($_POST['cantidad'])) {
			echo 'error';
		} else {
			$precio 	 = $_POST['precio'];
			$codproducto = $_POST['producto'];
			$cantidad    = $_POST['cantidad'];
			$token       = md5($_SESSION['idUser']);

			$query_iva = mysqli_query($conection, "SELECT iva,moneda FROM configuracion");
			$result_iva = mysqli_num_rows($query_iva);

			$query_detalle_temp = mysqli_query($conection, "CALL add_detalle_temp($codproducto,$cantidad,'$token',$precio)");
			$result = mysqli_num_rows($query_detalle_temp);

			$detalleTabla = '';
			$sub_total    = 0;
			$iva          = 0;
			$total        = 0;
			$arrayData    = array();

			if ($result > 0) {
				if ($result_iva > 0) {
					$info_iva = mysqli_fetch_assoc($query_iva);
					$iva = $info_iva['iva'];
					$moned = $info_iva['moneda'];
				}

				while ($data = mysqli_fetch_assoc($query_detalle_temp)) {
					$precioTotal1 = formatCant($data['cantidad'] * $data['precio_venta']);
					$precioTotal  = round($data['cantidad'] * $data['precio_venta'], 2);
					$sub_total    = round($sub_total + $precioTotal, 2);
					$total        = round($total + $precioTotal, 2);
					$precio_venta = formatCant($data['precio_venta']);

					$detalleTabla .= '<tr>
						                <td style="display:none;">' . $data['codproducto'] . '</td>
						                <td class="">' . $data['cantidad'] . '</td>
						                <td colspan="3">' . $data['descripcion'] . '</td>
						                <td class="textright">' . $precio_venta . '</td>
						                <td class="textright">' . $precioTotal1 . '</td>
						                <td class="">
							                <a class="link_delete" href="#" onclick="event.preventDefault();
							                  del_product_detalle(' . $data['correlativo'] . ');"><i class="far fa-trash-alt"></i></a>
						                </td>
						             </tr>';
				}

				
				$descuento = !empty($_POST['descuento']) ? (float)$_POST['descuento'] : 0;
				$total_total = $sub_total;
				$monto_desc  = $descuento;
				$total_con_desc = formatCant($total_total - $monto_desc);

				$detalleTotales = '<tr>
						               <td colspan="5" class="textright">Sub Total ' . $moned . '</td>
						               <td class="textright">' . formatCant($total_total) . '</td>
						               <td></td>
					               </tr>
					               <tr>
						               <td colspan="5" class="textright">Descuento ' . $moned . '</td>
						               <td class="textright">' . formatCant($monto_desc) . '</td>
						               <td></td>
					               </tr>
					               <tr>
						               <td colspan="5" class="textright">TOTAL ' . $moned . '</td>
						               <td class="textright">' . $total_con_desc . '</td>
						               <td></td>
					               </tr>';

				$arrayData['detalle'] = $detalleTabla;
				$arrayData['totales'] = $detalleTotales;
				$arrayData['total_con_desc_raw'] = round($total_total - $monto_desc, 2);

				echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
			} else {
				echo 'error';
			}
			mysqli_close($conection);
		}
		exit;
	}

	//Extraer datos del detalle_temp
	if ($_POST['action'] == 'serchForDetalle') {
		//print_r($_POST);
		if (empty($_POST['user'])) {
			echo 'error';
		} else {

			$descuento = $_POST['descuento'];
			$token = md5($_SESSION['idUser']);
			$query = mysqli_query($conection, "SELECT tmp.correlativo,
														tmp.token_user,
														tmp.cantidad,
														tmp.precio_venta,
														p.codproducto,
														p.descripcion
													FROM detalle_temp tmp
													INNER JOIN producto p
													ON tmp.codproducto = p.codproducto
													WHERE token_user = '$token' ");
			$result = mysqli_num_rows($query);

			$query_iva = mysqli_query($conection, "SELECT iva, moneda FROM configuracion");
			$result_iva = mysqli_num_rows($query_iva);

			$detalleTabla = '';
			$sub_total    = 0;
			$iva          = 0;
			$total        = 0;
			$arrayData    = array();

			if ($result > 0) {
				if ($result_iva > 0) {
					$info_iva = mysqli_fetch_assoc($query_iva);
					$iva = $info_iva['iva'];
					$moned = $info_iva['moneda'];
				}

				while ($data = mysqli_fetch_assoc($query)) {
					$precioTotal1 = formatCant($data['cantidad'] * $data['precio_venta']);
					$precioTotal  = $data['cantidad'] * $data['precio_venta'];
					$sub_total    = $sub_total + $precioTotal;
					$total        = $total + $precioTotal;
					$precio_venta = formatCant($data['precio_venta']);

					$detalleTabla .= '<tr>
						                <td style="display:none;">' . $data['codproducto'] . '</td>
						                <td class="">' . $data['cantidad'] . '</td>
						                <td colspan="3">' . $data['descripcion'] . '</td>
						                <td class="textright">' . $precio_venta . '</td>
						                <td class="textright">' . $precioTotal1 . '</td>
						                <td class="">
							                <a class="link_delete" href="#" onclick="event.preventDefault();
							                  del_product_detalle(' . $data['correlativo'] . ');"><i class="far fa-trash-alt"></i></a>
						                </td>
						             </tr>';
				}

				$total_total = $sub_total;
				$monto_desc  = $descuento;
				$total_con_desc = formatCant($total_total - $monto_desc);

				$detalleTotales = '<tr>
						               <td colspan="5" class="textright">Sub Total ' . $moned . '</td>
						               <td class="textright">' . formatCant($total_total) . '</td>
						               <td></td>
					               </tr>
					               <tr>
						               <td colspan="5" class="textright">Descuento ' . $moned . '</td>
						               <td class="textright">' . number_format($monto_desc, 0, ',', '.') . '</td>
						               <td></td>
					               </tr>
					               <tr>
						               <td colspan="5" class="textright">TOTAL ' . $moned . '</td>
						               <td class="textright">' . $total_con_desc . '</td>
						               <td></td>
					               </tr>';

				$arrayData['detalle'] = $detalleTabla;
				$arrayData['totales'] = $detalleTotales;
				$arrayData['total_con_desc_raw'] = round($total_total - $monto_desc, 2);

				echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
			} else {
				echo 'error';
			}
			mysqli_close($conection);
		}
		exit;
	}

	if ($_POST['action'] == 'delProductoDetalle') {

		if (empty($_POST['id_detalle'])) {
			echo 'error';
		} else {
			$descuento = $_POST['descuento'];
			$id_detalle = $_POST['id_detalle'];
			$token      = md5($_SESSION['idUser']);

			$query_iva = mysqli_query($conection, "SELECT iva,moneda FROM configuracion");
			$result_iva = mysqli_num_rows($query_iva);

			$query_detalle_temp = mysqli_query($conection, "CALL del_detalle_temp($id_detalle,'$token')");
			$result = mysqli_num_rows($query_detalle_temp);

			$detalleTabla = '';
			$sub_total    = 0;
			$iva          = 0;
			$total        = 0;
			$arrayData    = array();

			if ($result > 0) {
				if ($result_iva > 0) {
					$info_iva = mysqli_fetch_assoc($query_iva);
					$iva = $info_iva['iva'];
					$moned = $info_iva['moneda'];
				}

				while ($data = mysqli_fetch_assoc($query_detalle_temp)) {
					$precioTotal1 = formatCant($data['cantidad'] * $data['precio_venta']);
					$precioTotal  = $data['cantidad'] * $data['precio_venta'];
					$sub_total    = $sub_total + $precioTotal;
					$total        = $total + $precioTotal;
					$precio_venta = formatCant($data['precio_venta']);

					$detalleTabla .= '<tr>
						                <td style="display:none;">' . $data['codproducto'] . '</td>
						                 <td class="">' . $data['cantidad'] . '</td>
						                <td colspan="3">' . $data['descripcion'] . '</td>
						                <td class="textright">' . $precio_venta . '</td>
						                <td class="textright">' . $precioTotal1 . '</td>
						                <td class="">
							                <a class="link_delete" href="#" onclick="event.preventDefault();
							                  del_product_detalle(' . $data['correlativo'] . ');"><i class="far fa-trash-alt"></i></a>
						                </td>
						             </tr>';
				}


				$total_total = $sub_total;
				$monto_desc  = $descuento;
				$total_con_desc = number_format($total_total - $monto_desc, 0, ',', '.');

				$detalleTotales = '<tr>
						               <td colspan="5" class="textright">Sub Total ' . $moned . '</td>
						               <td class="textright">' . number_format($total_total, 0, ',', '.') . '</td>
						               <td></td>
					               </tr>
					               <tr>
						               <td colspan="5" class="textright">Descuento ' . $moned . '</td>
						               <td class="textright">' . number_format($monto_desc, 0, ',', '.') . '</td>
						               <td></td>
					               </tr>
					               <tr>
						               <td colspan="5" class="textright">TOTAL ' . $moned . '</td>
						               <td class="textright">' . $total_con_desc . '</td>
						               <td></td>
					               </tr>';

				$arrayData['detalle'] = $detalleTabla;
				$arrayData['totales'] = $detalleTotales;
				$arrayData['total_con_desc_raw'] = round($total_total - $monto_desc, 2);

				echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
			} else {
				echo 'error';
			}
			mysqli_close($conection);
		}
		exit;
	}

	//Anular Venta
	if ($_POST['action'] == 'anularVenta') {

		$token     = md5($_SESSION['idUser']);

		$query_del = mysqli_query($conection, "CALL cancelar_venta('$token')");
		mysqli_close($conection);
		if ($query_del) {
			echo 'ok';
		} else {
			echo 'error';
		}
		exit;
	}

	//Anular compra
	if ($_POST['action'] == 'anularCompra') {

		$token     = md5($_SESSION['idUser']);

		$query_del = mysqli_query($conection, "CALL cancelar_compra('$token')");
		mysqli_close($conection);
		if ($query_del) {
			echo 'ok';
		} else {
			echo 'error';
		}
		exit;
	}

	//Procesar Venta
	if ($_POST['action'] == 'procesarVenta') {
		//print_r($_POST);exit;
		$codcliente = !empty($_POST['codcliente']) ? (int)$_POST['codcliente'] : 1;
		$tipo_pago  = !empty($_POST['tipoPago']) ? (int)$_POST['tipoPago'] : 1;
		$descuento  = !empty($_POST['descuento']) ? (float)$_POST['descuento'] : 0;
		$pago_con   = !empty($_POST['pago_con']) ? (float)$_POST['pago_con'] : 0;
		$vuelto     = !empty($_POST['vuelto']) ? (float)$_POST['vuelto'] : 0;
		$token      = md5($_SESSION['idUser']);
		$usuario    = $_SESSION['idUser'];

		$query = mysqli_query($conection, "SELECT * FROM detalle_temp WHERE token_user = '$token'");
		$result = mysqli_num_rows($query);

		if ($result > 0) {
			$query_procesar = mysqli_query($conection, "CALL procesar_venta('$usuario',$codcliente,'$token',$tipo_pago,$id_caja,$descuento,$pago_con,$vuelto)");
			$result_detalle = mysqli_num_rows($query_procesar);

			if ($result_detalle > 0) {
				$data = mysqli_fetch_assoc($query_procesar);
				echo json_encode($data, JSON_UNESCAPED_UNICODE);
			} else {
				echo 'error';
			}
		} else {
			echo 'error';
		}
		mysqli_close($conection);
		exit;
	}

	// Guardar datos de Transferencia
	if ($_POST['action'] == 'guardarTransferencia') {
		// JS envía: numero_referencia, fecha_transferencia, monto, noventa
		if (empty($_POST['noventa'])) {
			echo json_encode(['cod' => '01', 'msg' => 'Faltan datos']);
			exit;
		}

		$noventa = $_POST['noventa'];
		// Default banco = 1 si no se envía (El JS actual no tiene selector de banco)
		$banco_id = !empty($_POST['banco']) ? $_POST['banco'] : 1; 
		$referencia = $_POST['numero_referencia'];
		$fecha_transferencia = !empty($_POST['fecha_transferencia']) ? $_POST['fecha_transferencia'] : date('Y-m-d');
		$monto = !empty($_POST['monto']) ? $_POST['monto'] : 0;
		$observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : '';

		$query_insert = mysqli_query($conection, "INSERT INTO transferencias(noventa, banco_id, numero_referencia, fecha_transferencia, monto, observaciones) 
												  VALUES($noventa, $banco_id, '$referencia', '$fecha_transferencia', $monto, '$observaciones')");

		if ($query_insert) {
			echo json_encode(['cod' => '00', 'msg' => 'Guardado correctamente']);
		} else {
			echo json_encode(['cod' => '02', 'msg' => 'Error al guardar']);
		}
		mysqli_close($conection);
		exit;
	}

	// Guardar datos de QR
	if ($_POST['action'] == 'guardarPagoQR') {
		// JS envía: numero_referencia, fecha_pago, monto
		if (empty($_POST['noventa'])) {
			echo json_encode(['cod' => '01', 'msg' => 'Faltan datos']);
			exit;
		}

		$noventa = $_POST['noventa'];
		$tipo_qr = !empty($_POST['tipo_qr']) ? $_POST['tipo_qr'] : 'General';
		$referencia = $_POST['numero_referencia'];
		$celular = isset($_POST['celular']) ? $_POST['celular'] : '';
		$fecha_pago = !empty($_POST['fecha_pago']) ? $_POST['fecha_pago'] : date('Y-m-d');
		$monto = !empty($_POST['monto']) ? $_POST['monto'] : 0;
		$observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : '';

		$query_insert = mysqli_query($conection, "INSERT INTO pagos_qr(noventa, tipo_qr, numero_referencia, numero_celular, fecha_pago, monto, observaciones) 
												  VALUES($noventa, '$tipo_qr', '$referencia', '$celular', '$fecha_pago', $monto, '$observaciones')");

		if ($query_insert) {
			echo json_encode(['cod' => '00', 'msg' => 'Guardado correctamente']);
		} else {
			echo json_encode(['cod' => '02', 'msg' => 'Error al guardar']);
		}
		mysqli_close($conection);
		exit;
	}

	// Guardar datos de Tarjeta
	if ($_POST['action'] == 'guardarPagoTarjeta') {
		// JS envía: numero_referencia, fecha_pago, monto
		if (empty($_POST['noventa'])) {
			echo json_encode(['cod' => '01', 'msg' => 'Faltan datos']);
			exit;
		}

		$noventa = $_POST['noventa'];
		$tipo_tarjeta = !empty($_POST['tipo_tarjeta']) ? $_POST['tipo_tarjeta'] : 'General';
		$banco = !empty($_POST['banco']) ? $_POST['banco'] : 'General';
		$ultimos_digitos = isset($_POST['digitos']) ? $_POST['digitos'] : '0000';
		$autorizacion = isset($_POST['autorizacion']) ? $_POST['autorizacion'] : '';
		$lote = isset($_POST['lote']) ? $_POST['lote'] : '';
		$fecha_pago = !empty($_POST['fecha_pago']) ? $_POST['fecha_pago'] : date('Y-m-d');
		$monto = !empty($_POST['monto']) ? $_POST['monto'] : 0;
		$observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : '';

		$query_insert = mysqli_query($conection, "INSERT INTO pagos_tarjeta(noventa, tipo_tarjeta, banco, ultimos_digitos, numero_autorizacion, numero_lote, fecha_pago, monto, observaciones) 
												  VALUES($noventa, '$tipo_tarjeta', '$banco', '$ultimos_digitos', '$autorizacion', '$lote', '$fecha_pago', $monto, '$observaciones')");

		if ($query_insert) {
			echo json_encode(['cod' => '00', 'msg' => 'Guardado correctamente']);
		} else {
			echo json_encode(['cod' => '02', 'msg' => 'Error al guardar']);
		}
		mysqli_close($conection);
		exit;
	}

	// Registro de Cliente desde Modal
	if ($_POST['action'] == 'registroClienteModal') {
		if (empty($_POST['nit']) || empty($_POST['nombre'])) {
			echo json_encode(['codcliente' => 0, 'msg' => 'Faltan datos obligatorios']);
			exit;
		}

		$nit       = $_POST['nit'];
		$nombre    = $_POST['nombre'];
		$telefono  = $_POST['telefono'];
		$direccion = $_POST['direccion'];
		$usuario_id = $_SESSION['idUser'];

		$query_existe = mysqli_query($conection, "SELECT * FROM cliente WHERE nit = '$nit' AND status = 1");
		$result_existe = mysqli_num_rows($query_existe);

		if ($result_existe > 0) {
			echo json_encode(['codcliente' => 0, 'msg' => 'El cliente ya existe con ese RUC/Cédula']);
		} else {
			$query_insert = mysqli_query($conection, "INSERT INTO cliente(nit, nombre, telefono, direccion, usuario_id) 
													VALUES('$nit', '$nombre', '$telefono', '$direccion', $usuario_id)");
			if ($query_insert) {
				$id_nuevo = mysqli_insert_id($conection);
				echo json_encode([
					'codcliente' => $id_nuevo,
					'nit'        => $nit,
					'nombre'     => $nombre,
					'telefono'   => $telefono,
					'direccion'  => $direccion
				], JSON_UNESCAPED_UNICODE);
			} else {
				echo json_encode(['codcliente' => 0, 'msg' => 'Error al guardar el cliente']);
			}
		}
		mysqli_close($conection);
		exit;
	}

	// Registro de Proveedor desde Modal
	if ($_POST['action'] == 'registroProveedorModal') {
		if (empty($_POST['proveedor']) || empty($_POST['contacto'])) {
			echo json_encode(['codproveedor' => 0, 'msg' => 'Faltan datos obligatorios']);
			exit;
		}

		$proveedor = $_POST['proveedor'];
		$contacto  = $_POST['contacto'];
		$telefono  = $_POST['telefono'];
		$direccion = $_POST['direccion'];
		$usuario_id = $_SESSION['idUser'];

		$query_existe = mysqli_query($conection, "SELECT * FROM proveedor WHERE proveedor = '$proveedor' AND status = 1");
		$result_existe = mysqli_num_rows($query_existe);

		if ($result_existe > 0) {
			echo json_encode(['codproveedor' => 0, 'msg' => 'Ya existe un proveedor con ese nombre']);
		} else {
			$query_insert = mysqli_query($conection, "INSERT INTO proveedor(proveedor, contacto, telefono, direccion, usuario_id) 
													VALUES('$proveedor', '$contacto', '$telefono', '$direccion', $usuario_id)");
			if ($query_insert) {
				$id_nuevo = mysqli_insert_id($conection);
				echo json_encode([
					'codproveedor' => $id_nuevo,
					'proveedor'    => $proveedor,
					'telefono'     => $telefono,
					'direccion'    => $direccion
				], JSON_UNESCAPED_UNICODE);
			} else {
				echo json_encode(['codproveedor' => 0, 'msg' => 'Error al guardar el proveedor']);
			}
		}
		mysqli_close($conection);
		exit;
	}

	// Info Factura
	if ($_POST['action'] == 'infoFactura') {
		if (!empty($_POST['nofactura'])) {

			$noventa = $_POST['nofactura'];

			$query = mysqli_query($conection, "SELECT * FROM venta WHERE noventa = '$noventa' AND status != 2");
			mysqli_close($conection);

			$result = mysqli_num_rows($query);
			if ($result > 0) {

				$data = mysqli_fetch_assoc($query);
				echo json_encode($data, JSON_UNESCAPED_UNICODE);
				exit;
			}
		}
		echo "error";
		exit;
	}

	// Info Factura
	if ($_POST['action'] == 'infoCompra') {
		if (!empty($_POST['nofactura'])) {

			$noventa = $_POST['nofactura'];

			$query = mysqli_query($conection, "SELECT * FROM compras WHERE nocompra = '$noventa' AND status != 2");
			mysqli_close($conection);

			$result = mysqli_num_rows($query);
			if ($result > 0) {

				$data = mysqli_fetch_assoc($query);
				echo json_encode($data, JSON_UNESCAPED_UNICODE);
				exit;
			}
		}
		echo "error";
		exit;
	}

	//Anular Factuea
	if ($_POST['action'] == 'anularFactura') {

		if (!empty($_POST['noFactura'])) {
			$noFactura = $_POST['noFactura'];

			$query_anular = mysqli_query($conection, "CALL anular_venta($noFactura)");
			mysqli_close($conection);
			$result = mysqli_num_rows($query_anular);
			if ($result > 0) {
				$data = mysqli_fetch_assoc($query_anular);
				echo json_encode($data, JSON_UNESCAPED_UNICODE);
				exit;
			}
		}
		echo "error";
		exit;
	}

	//Anular Compra
	if ($_POST['action'] == 'anularFactCompra') {

		if (!empty($_POST['noFactura'])) {
			$noFactura = $_POST['noFactura'];

			$query_anular = mysqli_query($conection, "CALL anular_compra($noFactura)");
			mysqli_close($conection);
			$result = mysqli_num_rows($query_anular);
			if ($result > 0) {
				$data = mysqli_fetch_assoc($query_anular);
				echo json_encode($data, JSON_UNESCAPED_UNICODE);
				exit;
			}
		}
		echo "error";
		exit;
	}

	//Cambiar contraseña
	if ($_POST['action'] == 'changePassword') {


		if (!empty($_POST['passActual']) && !empty($_POST['passNuevo'])) {
			$password = md5($_POST['passActual']);
			$newPass = md5($_POST['passNuevo']);
			$idUser = $_SESSION['idUser'];

			$code = '';
			$msg = '';
			$arrData = array();

			$query_user = mysqli_query($conection, "SELECT * FROM usuario
	   															WHERE clave = '$password' AND idusuario = $idUser ");
			$result = mysqli_num_rows($query_user);
			if ($result > 0) {
				$query_update = mysqli_query($conection, "UPDATE usuario SET clave = '$newPass' WHERE idusuario = $idUser");
				mysqli_close($conection);

				if ($query_update) {
					$code = '00';
					$msg = "Su contraseña se ha actualizado con éxito.";
				} else {
					$code = '2';
					$msg = "No es posible cambiar su contraseña.";
				}
			} else {
				$code = '1';
				$msg = "La contraseña actual es incorrecta.";
			}
			$arrData = array('cod' => $code, 'msg' => $msg);
			echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		} else {
			echo "error";
		}

		exit;
	}

	//Actualizar datos empresa
	if ($_POST['action'] == 'updateDataEmpresa') {
		if (empty($_POST['txtNit']) || empty($_POST['txtNombre']) || empty($_POST['txtRSocial']) || empty($_POST['txtTelEmpresa']) || empty($_POST['txtEmailEmpresa']) || empty($_POST['txtDirEmpresa'])) {
			$code = '1';
			$msg = "Todos los campos son abligatorios.";
		} else {
			$intNit 	= $_POST['txtNit'];
			$strNombre 	= $_POST['txtNombre'];
			$strRSocial	= $_POST['txtRSocial'];
			$intTel 	= intval($_POST['txtTelEmpresa']);
			$strEmail 	= $_POST['txtEmailEmpresa'];
			$strDir	 	= $_POST['txtDirEmpresa'];
			$strMoneda 	= $_POST['txtMoneda'];
			//$strIva 	= $_POST['txtIva'];

			$foto = $_FILES['foto'];
			$nombre_foto = $foto['name'];
			$type 		 = $foto['type'];
			$url_temp    = $foto['tmp_name'];

			if ($nombre_foto != '') {
				$destino = 'factura/img/';
				$img_nombre = 'logo_' . md5(date('d-m-Y H:m:s'));
				$imgProducto = $img_nombre . '.jpg';
				$src = $destino . $imgProducto;

				$queryUpd = mysqli_query($conection, " UPDATE configuracion SET nit = '$intNit',
	   		     																   nombre = '$strNombre',
	   		     																   razon_social = '$strRSocial',
	   		     																   telefono = $intTel,
	   		     																   email = '$strEmail',
	   		     																   direccion = '$strDir',
	   		     																   moneda = '$strMoneda',
	   		     																   foto = '$imgProducto'
	   		     																   WHERE id = 1");
			} else {

				$queryUpd = mysqli_query($conection, " UPDATE configuracion SET nit = '$intNit',
	   		     																   nombre = '$strNombre',
	   		     																   razon_social = '$strRSocial',
	   		     																   telefono = $intTel,
	   		     																   email = '$strEmail',
	   		     																   direccion = '$strDir',
	   		     																   moneda = '$strMoneda'
	   		     																   WHERE id = 1");
			}
			mysqli_close($conection);

			if ($queryUpd) {
				if ($nombre_foto != '') {
					move_uploaded_file($url_temp, $src);
				}
				$code = '00';
				$msg = "Datos actualizados correctamente";
			} else {
				$code = '2';
				$msg = "Error al actualizar los datos";
			}
		}

		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//buscar cliente desde ventas
	if ($_POST['action'] == 'busquedaCliente') {



		$query = mysqli_query($conection, "SELECT nit,nombre,telefono,direccion FROM cliente WHERE status = 1");

		if (isset($_POST['consulta'])) {
			$q = mysqli_real_escape_string($conection, $_POST['consulta']);
			$query = mysqli_query($conection, "SELECT nit,nombre,telefono,direccion FROM cliente WHERE nit LIKE '%" . $q . "%' OR nombre LIKE '%" . $q . "%' AND status = 1");
		}
		$result = mysqli_num_rows($query);

		$detalleTabla = '';
		$arrayData    = array();

		if ($result > 0) {

			while ($data = mysqli_fetch_assoc($query)) {

				$detalleTabla .= '<tr>
						                <td>' . $data['nit'] . '</td>
						                <td colspan="">' . $data['nombre'] . '</td>
						                <td class="">' . $data['telefono'] . '</td>
						                <td class="">' . $data['direccion'] . '</td>
						                <td class="textcenter">
							                <a class="link_edit" href="#" onclick="event.preventDefault();
							                  del_product_detalle(' . $data['nit'] . ');"><i class="fas fa-plus"></i></a>
						                </td>
						             </tr>';
			}

			$detalleTabla .= '</table>';

			//$arrayData['detalle'] = $detalleTabla;

			//echo json_encode($arrayData,JSON_UNESCAPED_UNICODE);	               
		} else {
			$detalleTabla = "No se encontraron considencias.";
			//echo 'error';
		}
		echo $detalleTabla;
		mysqli_close($conection);

		exit;
	}

	//Agregar producto al detalle temporal

	if ($_POST['action'] == 'addProductoDetalle2') {
		//print_r($_POST);
		if (empty($_POST['txt_cod_producto_venta']) || empty($_POST['txt_cant_producto_venta']) || $_POST['txt_cant_producto_venta'] <= 0) {
			echo 'error';
		} else {


			if ($_POST['descuento'] == '') {
				$descuento = 0;
			} else {
				$descuento = $_POST['descuento'];
			}
			//$precio = $_POST['txt_precio_venta'];
			$codproducto = $_POST['txt_cod_producto_venta'];
			$cantidad    = $_POST['txt_cant_producto_venta'];
			$token       = md5($_SESSION['idUser']);

			$query_iva = mysqli_query($conection, "SELECT iva,moneda FROM configuracion");
			$result_iva = mysqli_num_rows($query_iva);

			$query_detalle_temp = mysqli_query($conection, "CALL add_detalle_temp($codproducto,$cantidad,'$token')");
			$result = mysqli_num_rows($query_detalle_temp);

			$detalleTabla = '';
			$sub_total    = 0;
			$iva          = 0;
			$total        = 0;
			$arrayData    = array();

			if ($result > 0) {
				if ($result_iva > 0) {
					$info_iva = mysqli_fetch_assoc($query_iva);
					$iva = $info_iva['iva'];
					$moned = $info_iva['moneda'];
				}

				while ($data = mysqli_fetch_assoc($query_detalle_temp)) {
					$precioTotal1 = number_format($data['cantidad'] * $data['precio_venta'], 2);
					$precioTotal  = $data['cantidad'] * $data['precio_venta'];
					$sub_total    = $sub_total + $precioTotal;
					$total        = $total + $precioTotal;
					$precio_venta = number_format($data['precio_venta'], 2);

					$detalleTabla .= '<tr>
						                <td style="display:none;">' . $data['codproducto'] . '</td>
						                 <td class="">' . $data['cantidad'] . '</td>
						                <td colspan="3">' . $data['descripcion'] . '</td>
						                <td class="textright">' . $precio_venta . '</td>
						                <td class="textright">' . $precioTotal1 . '</td>
						                <td class="">
							                <a class="link_delete" href="#" onclick="event.preventDefault();
							                  del_product_detalle(' . $data['correlativo'] . ');"><i class="far fa-trash-alt"></i></a>
						                </td>
						             </tr>';
				}

				
				$descuento = !empty($_POST['descuento']) ? (float)$_POST['descuento'] : 0;
				$total_total = $sub_total;
				$monto_desc  = round(($total_total * $descuento) / 100, 2);
				$total_con_desc = formatCant($total_total - $monto_desc);

				$detalleTotales = '<tr>
						               <td colspan="5" class="textright">Sub Total ' . $moned . '</td>
						               <td class="textright">' . formatCant($total_total) . '</td>
						               <td></td>
					               </tr>
					               <tr>
						               <td colspan="5" class="textright">Descuento (' . $descuento . '%) ' . $moned . '</td>
						               <td class="textright">' . formatCant($monto_desc) . '</td>
						               <td></td>
					               </tr>
					               <tr>
						               <td colspan="5" class="textright">TOTAL ' . $moned . '</td>
						               <td class="textright">' . $total_con_desc . '</td>
						               <td></td>
					               </tr>';

				$arrayData['detalle'] = $detalleTabla;
				$arrayData['totales'] = $detalleTotales;
				$arrayData['total_con_desc_raw'] = round($total_total - $monto_desc, 2);

				echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
			} else {
				echo 'error';
			}
			mysqli_close($conection);
		}
		exit;
	}

	// Info Cliente
	if ($_POST['action'] == 'editarCliente') {
		//print_r($_POST);
		if (!empty($_POST['cliente'])) {

			$cliente = $_POST['cliente'];

			$query = mysqli_query($conection, "SELECT * FROM cliente WHERE idcliente = '$cliente' AND status = 1");
			mysqli_close($conection);

			$result = mysqli_num_rows($query);
			if ($result > 0) {

				$data = mysqli_fetch_assoc($query);
				echo json_encode($data, JSON_UNESCAPED_UNICODE);
				exit;
			}
		}
		echo "error";
		exit;
	}

	//Actualizar datos del cliente
	if ($_POST['action'] == 'actualizarCliente') {

		if (empty($_POST['idCliente']) || empty($_POST['nitCliente']) || empty($_POST['nombreCliente']) || empty($_POST['telefonoCliente']) || empty($_POST['direccionCliente'])) {
			$code = '1';
			$msg = "Todos los campos son abligatorios.";
		} else {
			$idCliente  = $_POST['idCliente'];
			$intNit 	= $_POST['nitCliente'];
			$strNombre 	= $_POST['nombreCliente'];
			$intTel 	= $_POST['telefonoCliente'];
			$strDir 	= $_POST['direccionCliente'];

			$query = mysqli_query($conection, "SELECT * FROM cliente WHERE (nit = '$intNit' AND idcliente != $idCliente) ");
			$result = mysqli_fetch_array($query);
		}
		if ($result > 0) {
			$code = '2';
			$msg = 'El número de CI ya existe.';
		} else {

			$queryUpd = mysqli_query($conection, " UPDATE cliente SET  nit = '$intNit',
	   		     														nombre = '$strNombre',
	   		     														telefono = $intTel,
	   		     														direccion = '$strDir'
	   		     														WHERE idcliente = $idCliente");
			mysqli_close($conection);

			if ($queryUpd) {
				$code = '00';
				$msg = "Datos actualizados correctamente";
			} else {
				$code = '2';
				$msg = "Error al actualizar los datos";
			}
		}

		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Eliminar Cliete
	if ($_POST['action'] == 'eliminarCliente') {
		if (empty($_POST['cliente_id']) || !is_numeric($_POST['cliente_id'])) {
			echo 'error';
		} else {
			$idCliente = $_POST['cliente_id'];

			//$query_delete = mysqli_query($conection,"DELETE FROM cliente WHERE idcliente = $idCliente ");
			$query_delete = mysqli_query($conection, "UPDATE cliente SET status = 0 WHERE idcliente = $idCliente ");
			mysqli_close($conection);
			if ($query_delete) {
				echo 'ok';
			} else {
				echo 'error';
			}
		}
		echo 'error';
		exit;
	}

	//Nuevo Cliete
	if ($_POST['action'] == 'nuevoCliente') {
		if (empty($_POST['nitCliente']) || empty($_POST['nombreCliente']) || empty($_POST['telefonoCliente']) || empty($_POST['direccionCliente'])) {
			$code = '1';
			$msg = "Todos los campos son abligatorios.";
		} else {
			$nit 		= $_POST['nitCliente'];
			$nombre 	= $_POST['nombreCliente'];
			$telefono 	= $_POST['telefonoCliente'];
			$direccion 	= $_POST['direccionCliente'];
			$user 		= $_SESSION['idUser'];
			$result 	= 0;

			$query = mysqli_query($conection, "SELECT * FROM cliente WHERE nit = '$nit' ");
			$result = mysqli_fetch_array($query);
		}
		if ($result > 0) {
			$code = '2';
			$msg = 'El número de CI ya existe.';
		} else {

			$query_insert = mysqli_query($conection, "INSERT INTO cliente(nit,nombre,telefono,direccion,usuario_id)
					                                           VALUES('$nit','$nombre',$telefono,'$direccion',$user)");

			mysqli_close($conection);
			if ($query_insert) {
				$code = '00';
				$msg = "Cliente registrado correctamente";
			} else {
				$code = '2';
				$msg = "Error al registar el cliente";
			}
		}
		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Info Usuarios
	if ($_POST['action'] == 'editarUsuario') {
		//print_r($_POST);
		if (!empty($_POST['usuario'])) {

			$id = $_POST['usuario'];

			$query = mysqli_query($conection, "SELECT u.idusuario, u.nombre, u.correo, u.usuario, r.idrol, r.rol FROM usuario u INNER JOIN rol r ON u.rol = r.idrol WHERE u.idusuario = $id AND u.status = 1");

			$result = mysqli_num_rows($query);
			if ($result > 0) {
				$dataUsuario = mysqli_fetch_assoc($query);
			}
			$query_rol = mysqli_query($conection, "SELECT * FROM rol");
			$result_rol = mysqli_num_rows($query_rol);
			mysqli_close($conection);
			$rol = '';
			while ($data = mysqli_fetch_array($query_rol)) {
				$rol .= '<option value="' . $data['idrol'] . '">' . $data['rol'] . '</option>';
			}
			$arrData = array('usuario' => $dataUsuario, 'rol' => $rol);
			echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
			//echo json_encode($data,JSON_UNESCAPED_UNICODE);
			exit;
		}
		echo "error";
		exit;
	}

	//Actualizar datos del Usuario
	if ($_POST['action'] == 'actualizarUsuario') {

		if (empty($_POST['idUsuario']) || empty($_POST['nombreUsuario']) || empty($_POST['correoUsuario']) || empty($_POST['usuario']) || empty($_POST['rolUsuario'])) {
			$code 	= '1';
			$msg 	= "Todos los campos son abligatorios.";
		} else {
			$id     	= $_POST['idUsuario'];
			$nombre 	= $_POST['nombreUsuario'];
			$usuario 	= $_POST['usuario'];
			$email 		= $_POST['correoUsuario'];
			$clave 		= md5($_POST['claveUsuario']);
			$rol 		= $_POST['rolUsuario'];

			$query = mysqli_query($conection, "SELECT * FROM usuario 
	   		     										WHERE (usuario = '$usuario' AND idusuario != $id) OR
	   		     										      (correo = '$email' AND idusuario != $id)");
			$result = mysqli_fetch_array($query);
		}

		if ($result > 0) {
			$code = '2';
			$msg = "El usuario o correo ya existe.";
		} else {

			$queryUpd = mysqli_query($conection, " UPDATE usuario SET nombre  = '$nombre',
	   		     															 correo  = '$email',
	   		     															 usuario = '$usuario',
	   		     															 clave 	 = '$clave',
	   		     															 rol     = '$rol'
	   		     															 WHERE idusuario = $id");
			mysqli_close($conection);

			if ($queryUpd) {
				$code = '00';
				$msg = "Datos actualizados correctamente";
			} else {
				$code = '2';
				$msg = "Error al actualizar los datos";
			}
		}

		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Eliminar Usuario
	if ($_POST['action'] == 'eliminarUsuario') {
		if (empty($_POST['usuario_id']) || !is_numeric($_POST['usuario_id'])) {
			echo 'error';
		} else {
			$idUsuario = $_POST['usuario_id'];

			//$query_delete = mysqli_query($conection,"DELETE FROM usuario WHERE idusuario = $idUsuario ");
			$query_delete = mysqli_query($conection, "UPDATE usuario SET status = 0 WHERE idusuario = $idUsuario ");
			mysqli_close($conection);
			if ($query_delete) {
				echo 'ok';
			} else {
				echo 'error';
			}
		}
		echo 'error';
		exit;
	}

	//Nuevo Usuario
	if ($_POST['action'] == 'nuevoUsuario') {
		if (empty($_POST['nombreUsuario']) || empty($_POST['correoUsuario']) || empty($_POST['usuario']) || empty($_POST['claveUsuario']) || empty($_POST['rolUsuario'])) {
			$code = '1';
			$msg = "Todos los campos son abligatorios.";
		} else {
			$nombre 	= $_POST['nombreUsuario'];
			$email 		= $_POST['correoUsuario'];
			$user 		= $_POST['usuario'];
			$clave 	= md5($_POST['claveUsuario']);
			$rol 	= $_POST['rolUsuario'];
			$result 	= 0;

			$query = mysqli_query($conection, "SELECT * FROM usuario WHERE usuario = '$user' OR correo = '$email' ");
			$result = mysqli_fetch_array($query);
		}

		if ($result > 0) {
			$code = '2';
			$msg = "El usuario o correo ya existe.";
		} else {
			$query_insert = mysqli_query($conection, "INSERT INTO usuario(nombre,correo,usuario,clave,rol)
					                                                  VALUES('$nombre','$email','$user','$clave','$rol')");

			mysqli_close($conection);
			if ($query_insert) {
				$code = '00';
				$msg = "Usuario registrado correctamente";
			} else {
				$code = '2';
				$msg = "Error al registar el usuario";
			}
		}
		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Info proveedor
	if ($_POST['action'] == 'editarProveedor') {
		//print_r($_POST);
		if (!empty($_POST['proveedor'])) {

			$id = $_POST['proveedor'];

			$query = mysqli_query($conection, "SELECT * FROM proveedor WHERE codproveedor = '$id' AND status = 1");
			mysqli_close($conection);

			$result = mysqli_num_rows($query);
			if ($result > 0) {

				$data = mysqli_fetch_assoc($query);
				echo json_encode($data, JSON_UNESCAPED_UNICODE);
				exit;
			}
		}
		echo "error";
		exit;
	}

	//Actualizar datos del proveedor
	if ($_POST['action'] == 'actualizarProveedor') {

		if (empty($_POST['idProveedor']) || empty($_POST['nombreProveedor']) || empty($_POST['nombreContacto']) || empty($_POST['telefonoProveedor']) || empty($_POST['direccionProveedor'])) {
			$code = '1';
			$msg = "Todos los campos son abligatorios.";
		} else {
			$id     	= $_POST['idProveedor'];
			$proveedor  = $_POST['nombreProveedor'];
			$contacto 	= $_POST['nombreContacto'];
			$telefono 	= $_POST['telefonoProveedor'];
			$direccion 	= $_POST['direccionProveedor'];

			$query = mysqli_query($conection, "SELECT * FROM proveedor WHERE (proveedor = '$proveedor' AND codproveedor != $id)");
			$result = mysqli_fetch_array($query);
		}

		if ($result > 0) {
			$code = '2';
			$msg = "El proveedor ya existe";
		} else {

			$queryUpd = mysqli_query($conection, " UPDATE proveedor SET proveedor = '$proveedor',
	   		     																contacto = '$contacto',
	   		     																telefono = $telefono,
	   		     																direccion = '$direccion'
	   		     																WHERE codproveedor = $id");
			mysqli_close($conection);

			if ($queryUpd) {
				$code = '00';
				$msg = "Datos actualizados correctamente";
			} else {
				$code = '2';
				$msg = "Error al actualizar los datos";
			}
		}

		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Eliminar proveedor
	if ($_POST['action'] == 'eliminarProveedor') {
		if (empty($_POST['proveedor_id']) || !is_numeric($_POST['proveedor_id'])) {
			echo 'error';
		} else {
			$id = $_POST['proveedor_id'];

			//$query_delete = mysqli_query($conection,"DELETE FROM proveedor WHERE codproveedor = $id ");
			$query_delete = mysqli_query($conection, "UPDATE proveedor SET status = 0 WHERE codproveedor = $id ");
			mysqli_close($conection);
			if ($query_delete) {
				echo 'ok';
			} else {
				echo 'error';
			}
		}
		echo 'error';
		exit;
	}

	//Nuevo proveedor
	if ($_POST['action'] == 'nuevoProveedor') {
		if (empty($_POST['nombreProveedor']) || empty($_POST['nombreContacto']) || empty($_POST['telefonoProveedor']) || empty($_POST['direccionProveedor'])) {
			$code = '1';
			$msg = "Todos los campos son abligatorios.";
		} else {
			$proveedor 	= $_POST['nombreProveedor'];
			$contacto 		= $_POST['nombreContacto'];
			$telefono 		= $_POST['telefonoProveedor'];
			$direccion 		= $_POST['direccionProveedor'];
			$result 	= 0;

			$query = mysqli_query($conection, "SELECT * FROM proveedor WHERE proveedor = '$proveedor'");
			$result = mysqli_fetch_array($query);
		}

		if ($result > 0) {
			$code = '2';
			$msg = "El proveedor ya existe";
		} else {
			$query_insert = mysqli_query($conection, "INSERT INTO proveedor(proveedor,contacto,telefono,direccion)
					                                                  VALUES('$proveedor','$contacto','$telefono','$direccion')");

			mysqli_close($conection);
			if ($query_insert) {
				$code = '00';
				$msg = "Proveedor registrado correctamente";
			} else {
				$code = '2';
				$msg = "Error al registar el proveedor";
			}
		}
		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Info producto
	if ($_POST['action'] == 'editarProducto') {
		//print_r($_POST);
		if (!empty($_POST['producto'])) {

			$id = $_POST['producto'];

			$query = mysqli_query($conection, "SELECT p.codproducto,p.codigo,p.descripcion,p.costo,p.precio,p.foto,pr.codproveedor,pr.proveedor,p.categoria_id,p.marca_id,p.tamano_id 
														FROM producto p
														INNER JOIN proveedor pr 
														ON p.proveedor = pr.codproveedor
														WHERE p.codproducto = $id AND p.status != 10");

			$result = mysqli_num_rows($query);
			if ($result > 0) {
				$data = mysqli_fetch_assoc($query);
			}
			$query_prov = mysqli_query($conection, "SELECT * FROM proveedor");
			$proveedor = '';
			while ($data_prov = mysqli_fetch_array($query_prov)) {
				$proveedor .= '<option value="' . $data_prov['codproveedor'] . '">' . $data_prov['proveedor'] . '</option>';
			}

			// Categorias
			$query_cat = mysqli_query($conection, "SELECT * FROM categoria WHERE status = 1 ORDER BY nombre ASC");
			$categoria = '';
			while ($data_cat = mysqli_fetch_array($query_cat)) {
				$categoria .= '<option value="' . $data_cat['idcategoria'] . '">' . $data_cat['nombre'] . '</option>';
			}

			// Marcas
			$categoria_id = isset($data['categoria_id']) ? (int)$data['categoria_id'] : 0;
			$query_marca = mysqli_query($conection, "SELECT m.idmarca, m.nombre FROM marca m
													 INNER JOIN categoria_marca cm ON m.idmarca = cm.marca_id
													 WHERE cm.categoria_id = $categoria_id AND m.status = 1 ORDER BY m.nombre ASC");
			$marca = '';
			while ($data_marca = mysqli_fetch_array($query_marca)) {
				$marca .= '<option value="' . $data_marca['idmarca'] . '">' . $data_marca['nombre'] . '</option>';
			}

			// Tamaños
			$marca_id = isset($data['marca_id']) ? (int)$data['marca_id'] : 0;
			$query_tam = mysqli_query($conection, "SELECT t.idtamano, t.nombre FROM tamano t
												   INNER JOIN marca_tamano mt ON t.idtamano = mt.tamano_id
												   WHERE mt.marca_id = $marca_id AND t.status = 1 ORDER BY t.nombre ASC");
			$tamano = '';
			while ($data_tam = mysqli_fetch_array($query_tam)) {
				$tamano .= '<option value="' . $data_tam['idtamano'] . '">' . $data_tam['nombre'] . '</option>';
			}

			mysqli_close($conection);

			$arrData = array(
				'proveedor' => $proveedor, 
				'categoria' => $categoria,
				'marca' => $marca,
				'tamano' => $tamano,
				'producto' => $data
			);
			echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
			exit;
		}
		echo "error";
		exit;
	}

	//Actualizar datos del producto
	if ($_POST['action'] == 'actualizarProducto') {
		//print_r($_POST);
		if (empty($_POST['idProducto']) || empty($_POST['nombreProveedorProd']) || empty($_POST['codigoProducto']) || empty($_POST['nombreProducto']) || empty($_POST['prcioProducto'])) {
			$code = '1';
			$msg = "Todos los campos son abligatorios.";
		} else {
			$id     	= $_POST['idProducto'];
			$proveedor  = $_POST['nombreProveedorProd'];
			$codigo     = $_POST['codigoProducto'];
			$producto 	= $_POST['nombreProducto'];
			$costo 		= $_POST['costoProducto'];
			$precio 	= $_POST['prcioProducto'];
			$categoria_id = !empty($_POST['categoriaProducto']) ? (int)$_POST['categoriaProducto'] : "NULL";
			$marca_id     = !empty($_POST['marcaProducto']) ? (int)$_POST['marcaProducto'] : "NULL";
			$tamano_id    = !empty($_POST['tamanoProducto']) ? (int)$_POST['tamanoProducto'] : "NULL";

			$foto = $_FILES['foto'];
			$nombre_foto = $foto['name'];
			$type 		 = $foto['type'];
			$url_temp    = $foto['tmp_name'];

			$imgProducto = 'img_producto.png';

			if ($nombre_foto != '') {
				$destino = 'img/uploads/';
				$img_nombre = 'img_' . md5(date('d-m-Y H:m:s'));
				$imgProducto = $img_nombre . '.jpg';
				$src = $destino . $imgProducto;

				$query_update = mysqli_query($conection, " UPDATE producto 
															SET codigo 		= '$codigo',
	   		     												descripcion = '$producto',
	   		     												proveedor 	= $proveedor,
	   		     												costo 		= $costo,
	   		     												precio 		= $precio,
	   		     												foto 		= '$imgProducto',
	   		     												categoria_id = $categoria_id,
	   		     												marca_id 	= $marca_id,
	   		     												tamano_id 	= $tamano_id
	   		     											WHERE codproducto = $id");
			} else {
				$query_update = mysqli_query($conection, " UPDATE producto 
															SET codigo 		= '$codigo',
	   		     												descripcion = '$producto',
	   		     												proveedor 	= $proveedor,
	   		     												costo 		= $costo,
	   		     												precio 		= $precio,
	   		     												categoria_id = $categoria_id,
	   		     												marca_id 	= $marca_id,
	   		     												tamano_id 	= $tamano_id
	   		     												WHERE codproducto = $id");
			}
			mysqli_close($conection);

			if ($query_update) {
				if ($nombre_foto != '') {
					move_uploaded_file($url_temp, $src);
				}
				$code = '00';
				$msg = "Datos actualizados correctamente";
			} else {
				$code = '2';
				$msg = "Error al actualizar los datos";
			}
		}

		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Eliminar producto
	if ($_POST['action'] == 'eliminarProducto') {
		if (empty($_POST['producto_id2']) || !is_numeric($_POST['producto_id2'])) {
			echo 'error';
		} else {
			$id = $_POST['producto_id2'];

			//$query_delete = mysqli_query($conection,"DELETE FROM producto WHERE codproducto = $id ");
			$query_delete = mysqli_query($conection, "UPDATE producto SET status = 0 WHERE codproducto = $id ");
			mysqli_close($conection);
			if ($query_delete) {
				echo 'ok';
			} else {
				echo 'error';
			}
		}
		echo 'error';
		exit;
	}

	//Nuevo producto
	if ($_POST['action'] == 'nuevoProducto') {
		//print_r($_POST);
		if (empty($_POST['nombreProv']) || empty($_POST['nombreProd']) || empty($_POST['precioProd']) || $_POST['precioProd'] <= 0 || $_POST['costoProd'] < 0) {
			$code = '1';
			$msg = "Todos los campos son abligatorios y los valores deben ser válidos.";
		} else {
			$proveedor   = $_POST['nombreProv'];
			$codigo 	 = $_POST['codigoProd'];
			$producto 	 = $_POST['nombreProd'];
			$costo  	 = $_POST['costoProd'];
			$precio  	 = $_POST['precioProd'];
			$existencia  = (!empty($_POST['existenciaProd']) || (isset($_POST['existenciaProd']) && $_POST['existenciaProd'] === '0')) ? (int)$_POST['existenciaProd'] : 0;
			if ($existencia < 0) { $existencia = 0; }
			$usuario_id  = $_SESSION['idUser'];
			$categoria_id = !empty($_POST['categoriaProd']) ? (int)$_POST['categoriaProd'] : "NULL";
			$marca_id     = !empty($_POST['marcaProd']) ? (int)$_POST['marcaProd'] : "NULL";
			$tamano_id    = !empty($_POST['tamanoProd']) ? (int)$_POST['tamanoProd'] : "NULL";

			$foto = $_FILES['fotoProd'];
			$nombre_foto = $foto['name'];
			$type 		 = $foto['type'];
			$url_temp    = $foto['tmp_name'];

			$imgProducto = 'img_producto.png';

			if ($nombre_foto != '') {
				$destino = 'img/uploads/';
				$img_nombre = 'img_' . md5(date('d-m-Y H:m:s'));
				$imgProducto = $img_nombre . '.jpg';
				$src = $destino . $imgProducto;
			}

				$codigo_int_raw = !empty($_POST['codigoInternoProd']) ? mysqli_real_escape_string($conection, $_POST['codigoInternoProd']) : '';

				if ($codigo_int_raw !== '') {
					$query = mysqli_query($conection, "SELECT * FROM producto WHERE codigo_interno = '$codigo_int_raw'");
					$duplicateMsg = 'El código interno ya existe';
				} else {
					$query = mysqli_query($conection, "SELECT * FROM producto WHERE codigo = '$codigo'");
					$duplicateMsg = 'El código del producto ya existe';
				}
				$result = mysqli_num_rows($query);
			}
			if ($result > 0) {
				$code = '2';
				$msg = $duplicateMsg;
				if ($nombre_foto != '') {
					move_uploaded_file($url_temp, $src);
				}
				$code = '00';
				$msg = "Producto registrado correctamente";
			} else {
				$code = '2';
				$msg = "Error al registar el producto";
			}
		}
		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Seleccionar proveeedor para registrar producto
	if ($_POST['action'] == 'selecionarProveedor') {
		$query = mysqli_query($conection, "SELECT * FROM proveedor");
		$proveedor = '';
		while ($data = mysqli_fetch_array($query)) {
			$proveedor .= '<option value="' . $data['codproveedor'] . '">' . $data['proveedor'] . '</option>';
		}

		// Categorias
		$query_cat = mysqli_query($conection, "SELECT * FROM categoria WHERE status = 1 ORDER BY nombre ASC");
		$categoria = '<option value="">Seleccionar Categoría</option>';
		while ($data_cat = mysqli_fetch_array($query_cat)) {
			$categoria .= '<option value="' . $data_cat['idcategoria'] . '">' . $data_cat['nombre'] . '</option>';
		}

		// Marcas (inicialmente vacías)
		$marca = '<option value="">Seleccionar Marca</option>';

		// Tamaños (inicialmente vacíos)
		$tamano = '<option value="">Seleccionar Tamaño</option>';

		mysqli_close($conection);

		$arrData = array(
			'proveedor' => $proveedor,
			'categoria' => $categoria,
			'marca' => $marca,
			'tamano' => $tamano
		);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Seleccionar rol para registrar usuario
	if ($_POST['action'] == 'selecionarRol') {
		$query = mysqli_query($conection, "SELECT * FROM rol");
		$result = mysqli_num_rows($query);
		$rol = '';
		while ($data = mysqli_fetch_array($query)) {
			$rol .= '<option value="' . $data['idrol'] . '">' . $data['rol'] . '</option>';
		}
		$arrData = array('rol' => $rol);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Activar producto
	if ($_POST['action'] == 'activarProducto') {
		if (empty($_POST['producto_id_2']) || !is_numeric($_POST['producto_id_2'])) {
			echo 'error';
		} else {
			$id = $_POST['producto_id_2'];

			//$query_delete = mysqli_query($conection,"DELETE FROM producto WHERE codproducto = $id ");
			$query_delete = mysqli_query($conection, "UPDATE producto SET status = 1 WHERE codproducto = $id ");
			mysqli_close($conection);
			if ($query_delete) {
				echo 'ok';
			} else {
				echo 'error';
			}
		}
		echo 'error';
		exit;
	}

	//Nueva factura a cobrar
	if ($_POST['action'] == 'add_factura_cliente') {
		if (empty($_POST['id_client_fact']) || empty($_POST['nuevaFactura']) || $_POST['nuevaFactura'] <= 0) {
			$code = '1';
			$msg = "Todos los campos son abligatorios y el monto debe ser mayor a cero.";
		} else {
			$id_cliente 	= $_POST['id_client_fact'];
			$user 			= $_SESSION['idUser'];
			$cantidad 		= $_POST['nuevaFactura'];
			$status 		= 3;
			$result 		= 0;

			$query_insert = mysqli_query($conection, "INSERT INTO venta(usuario,codcliente,status,totalventa)
					                                                  VALUES($user,$id_cliente,$status,$cantidad)");

			mysqli_close($conection);
			if ($query_insert) {
				$code = '00';
				$msg = "Factura agregada correctamente.";
			} else {
				$code = '2';
				$msg = "Error al agregar la factura.";
			}
		}
		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Nueva abono

	if ($_POST['action'] == 'add_abono_cliente') {
		//print_r($_POST);
		if (empty($_POST['id_client_abono']) || empty($_POST['nuevoAbono']) || $_POST['nuevoAbono'] <= 0) {
			$code = '1';
			$msg = "Todos los campos son abligatorios y el abono debe ser mayor a cero.";
		} else {
			$saldo_ant 		= $_POST['id_client_saldo'];
			$id_cliente 	= $_POST['id_client_abono'];
			$user 			= $_SESSION['idUser'];
			$cantidad 		= $_POST['nuevoAbono'];
			$status 		= 4;
			$result 		= 0;
			$saldo_act 		= $saldo_ant - $cantidad;

			$query_up = mysqli_query($conection, "INSERT INTO venta(usuario,codcliente,status,abono,caja)
																VALUES($user,$id_cliente,$status,$cantidad,$id_caja)");
			$no_venta = mysqli_insert_id($conection);

			$query_detalle = mysqli_query($conection, "INSERT INTO detalle_recibo(noventa,saldo_anterior,cantidad,saldo_actual,usuario,caja)
					                                            VALUES($no_venta,$saldo_ant,$cantidad,$saldo_act,$user,$id_caja)");
			//$no_recibo = mysqli_insert_id($conection);	

			mysqli_close($conection);
			if ($query_detalle) {
				$code = '00';
				$msg = $no_venta;
			} else {
				$code = '2';
				$msg = "Error al agregar el pago.";
			}
		}
		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	// Info Cuenta a cobrar
	if ($_POST['action'] == 'info_cuenta_cobrar') {
		//print_r($_POST);
		if (!empty($_POST['cliente']) || !empty($_POST['nofactura'])) {

			$cliente = $_POST['cliente'];
			$no_venta = $_POST['nofactura'];

			$query = mysqli_query($conection, "SELECT f.noventa,f.fecha,SUM(f.totalventa) as totalventa,f.codcliente,f.status,SUM(f.abono) as abono,
															u.nombre as vendedor,
															cl.nombre as cliente
														FROM venta f
														INNER JOIN usuario u 
														ON f.usuario = u.idusuario
														INNER JOIN cliente cl 
														ON f.codcliente = cl.idcliente
														WHERE f.codcliente = $cliente
														AND (f.status = 3 || f.status = 4)");
			mysqli_close($conection);

			$result = mysqli_num_rows($query);
			if ($result > 0) {

				$data = mysqli_fetch_assoc($query);
				echo json_encode($data, JSON_UNESCAPED_UNICODE);
				exit;
			}
		}
		echo "error";
		exit;
	}

	// Agregar New factura
	if ($_POST['action'] == 'add_new_fact') {
		//print_r($_POST);
		if (empty($_POST['new_cliente_fact']) || empty($_POST['cantidad_fact']) || empty($_POST['new_num_fact']) || $_POST['cantidad_fact'] <= 0) {
			$code = '1';
			$msg = "Todos los campos son abligatorios y el monto debe ser mayor a cero.";
		} else {
			$cliente 		= $_POST['new_cliente_fact'];
			$user 			= $_SESSION['idUser'];
			$cantidad 		= $_POST['cantidad_fact'];
			$noDoc 			= $_POST['new_num_fact'];
			$status 		= 3;
			$result 		= 0;

			$query_cliente = mysqli_query($conection, "SELECT * FROM cliente WHERE nombre = '$cliente'");
			$result = mysqli_num_rows($query_cliente);
			if ($result > 0) {
				$data_cliente = mysqli_fetch_assoc($query_cliente);
			}
			$id_cliente = $data_cliente['idcliente'];

			$query_insert = mysqli_query($conection, "INSERT INTO venta(usuario,codcliente,status,nodocumento,totalventa)
					                                                  VALUES($user,$id_cliente,$status,$noDoc,$cantidad)");
			$no_venta = mysqli_insert_id($conection);
			$producto = 39;
			$cant_detalle = 1;

			$query_detalle = mysqli_query($conection, "INSERT INTO detalleventa(noventa,codproducto,cantidad,precio_venta)
					                                                  VALUES($no_venta,$producto,$cant_detalle,$cantidad)");
			//print_r($no_venta);

			mysqli_close($conection);
			if ($query_detalle) {
				$code = '00';
				$msg = "Factura agregada correctamente.";
			} else {
				$code = '2';
				$msg = "Error al agregar la factura.";
			}
		}
		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	// Agregar New factura proveedor
	if ($_POST['action'] == 'add_proveedor_fact') {
		//print_r($_POST);
		if (empty($_POST['new_proveedor_fact']) || empty($_POST['new_prov_fact']) || empty($_POST['cantidad_fact_prov']) || $_POST['cantidad_fact_prov'] <= 0) {
			$code = '1';
			$msg = "Todos los campos son abligatorios y el monto debe ser mayor a cero.";
		} else {
			$proveedor 	= $_POST['new_proveedor_fact'];
			$user 			= $_SESSION['idUser'];
			$cantidad 		= $_POST['cantidad_fact_prov'];
			$noDoc 			= $_POST['new_prov_fact'];
			$estado 		= 1;
			$result 		= 0;

			$query_cliente = mysqli_query($conection, "SELECT * FROM proveedor WHERE proveedor = '$proveedor'");
			$result = mysqli_num_rows($query_cliente);
			if ($result > 0) {
				$data_cliente = mysqli_fetch_assoc($query_cliente);
			}
			$id_prov = $data_cliente['codproveedor'];

			$query_insert = mysqli_query($conection, "INSERT INTO 
											compras(usuario,codproveedor,nodocumento,totalcompra,caja)
					                                   VALUES($user,$id_prov,$noDoc,$cantidad,$id_caja)");
			$no_compra = mysqli_insert_id($conection);
			$producto = 39;
			$cant_detalle = 1;

			$query_detalle = mysqli_query($conection, "INSERT INTO entradas(nocompra,codproducto,cantidad,precio,usuario_id)
					                VALUES($no_compra,$producto,$cant_detalle,$cantidad,$user)");
			//print_r($no_venta);

			mysqli_close($conection);
			if ($query_detalle) {
				$code = '00';
				$msg = "Factura agregada correctamente.";
			} else {
				$code = '2';
				$msg = "Error al agregar la factura.";
			}
		}
		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	// Info Cuenta a pagar
	if ($_POST['action'] == 'info_cuenta_pagar') {
		//print_r($_POST);
		if (!empty($_POST['proveedor']) || !empty($_POST['nofactura'])) {

			$proveedor = $_POST['proveedor'];
			$no_compra = $_POST['nofactura'];

			$query = mysqli_query($conection, "SELECT f.nocompra,f.fecha,SUM(f.totalcompra) as totalcompra,f.codproveedor,f.status,SUM(f.abono) as abono,
															u.nombre as vendedor,
															cl.proveedor as proveedor
														FROM compras f
														INNER JOIN usuario u 
														ON f.usuario = u.idusuario
														INNER JOIN proveedor cl 
														ON f.codproveedor = cl.codproveedor
														 WHERE f.codproveedor = $proveedor
														 AND f.status = 3");
			mysqli_close($conection);

			$result = mysqli_num_rows($query);
			if ($result > 0) {

				$data = mysqli_fetch_assoc($query);
				echo json_encode($data, JSON_UNESCAPED_UNICODE);
				exit;
			}
		}
		echo "error";
		exit;
	}

	//Nueva abono a proveedor	
	if ($_POST['action'] == 'add_abono_proveedor') {
		//print_r($_POST);
		if (empty($_POST['id_prov_abono']) || empty($_POST['nuevoAbono_prov']) || $_POST['nuevoAbono_prov'] <= 0) {
			$code = '1';
			$msg = "Todos los campos son abligatorios y el abono debe ser mayor a cero.";
		} else {
			$saldo_ant 		= $_POST['saldo_anterior'];
			$id_proveedor 	= $_POST['id_prov_abono'];
			$user 			= $_SESSION['idUser'];
			$cantidad 		= $_POST['nuevoAbono_prov'];
			$status 		= 3;
			$result 		= 0;
			$saldo_act 		= $saldo_ant - $cantidad;

			$query_up = mysqli_query($conection, "INSERT INTO compras(																	usuario,caja,codproveedor,status,abono)
													VALUES($user,$id_caja,$id_proveedor,$status,$cantidad)");
			$no_venta = mysqli_insert_id($conection);

			$query_detalle = mysqli_query($conection, "INSERT INTO detalle_recibo_compra(									nocompra,saldo_anterior,cantidad,saldo_actual,usuario,caja)
					                         VALUES($no_venta,$saldo_ant,$cantidad,$saldo_act,$user,$id_caja)");
			mysqli_close($conection);
			if ($query_detalle) {
				$code = '00';
				$msg = "Pago agregado correctamente.";
			} else {
				$code = '2';
				$msg = "Error al agregar el pago.";
			}
		}
		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Buscar Proveedor
	if ($_POST['action'] == 'searchProveedor') {
		if (!empty($_POST['proveedor'])) {
			$proveedor = $_POST['proveedor'];

			$query = mysqli_query($conection, "SELECT * FROM proveedor WHERE proveedor LIKE '$proveedor' OR codproveedor LIKE '$proveedor' and status = 1");
			mysqli_close($conection);
			$result = mysqli_num_rows($query);

			$data = '';
			if ($result > 0) {
				$data = mysqli_fetch_assoc($query);
			} else {
				$data = 0;
			}
			echo json_encode($data, JSON_UNESCAPED_UNICODE);
		}
		exit;
	}

	//Registro Proveedor - compra
	if ($_POST['action'] == 'addProveedor') {
		$nombre     = $_POST['nom_proveedor'];
		$contacto   = $_POST['con_proveedor'];
		$telefono   = $_POST['tel_proveedor'];
		$direccion  = $_POST['dir_proveedor'];
		$usuario_id = $_SESSION['idUser'];

		$query = mysqli_query($conection, "SELECT * FROM proveedor WHERE proveedor = '$nombre' ");
		$result = mysqli_fetch_array($query);

		if ($result > 0) {
			$msg = 'El proveedor ya existe.';
		} else {
			$query_insert = mysqli_query($conection, "INSERT INTO proveedor(proveedor,contacto,telefono,direccion,usuario_id)
																	VALUES('$nombre','$contacto','$telefono','$direccion','$usuario_id')");
		}
		mysqli_close($conection);
		if ($query_insert) {
			$codCliente = mysqli_insert_id($conection);
			$msg = $codCliente;
		} else {
			$msg = 'error';
		}
		echo $msg;
		exit;
	}

	//Procesar Compra
	//Procesar Compra
//Procesar Compra
if ($_POST['action'] == 'procesarCompra') {
    error_log("=== PROCESANDO COMPRA ===");
    
    if (empty($_POST['codproveedor'])) {
        $codproveedor = 1;
    } else {
        $codproveedor = $_POST['codproveedor'];
    }

    $tipo_pago = $_POST['tipoPago'];
    $token   = md5($_SESSION['idUser']);
    $usuario = $_SESSION['idUser'];

    // ✅ OBTENER CAJA ABIERTA CORRECTAMENTE
    $query_caja = mysqli_query($conection, "SELECT id FROM caja WHERE status = 1 LIMIT 1");
    
    if (!$query_caja || mysqli_num_rows($query_caja) == 0) {
        error_log("ERROR: No hay caja abierta");
        echo json_encode(['error' => 'No hay una caja abierta. Por favor abre una caja primero.']);
        mysqli_close($conection);
        exit;
    }
    
    $data_caja = mysqli_fetch_assoc($query_caja);
    $id_caja = $data_caja['id'];
    
    error_log("ID Caja: $id_caja");
    error_log("Proveedor: $codproveedor");
    error_log("Usuario: $usuario");

    // VERIFICA QUE HAY PRODUCTOS
    $query = mysqli_query($conection, "SELECT * FROM detalle_temp_compra WHERE token_user = '$token'");
    $result = mysqli_num_rows($query);
    
    error_log("Productos en temp: $result");
    
    if ($result > 0) {
        // LLAMA AL PROCEDIMIENTO CON TODOS LOS PARÁMETROS
        $query_procesar = mysqli_query($conection, "CALL procesar_compra($usuario, $codproveedor, '$token', $tipo_pago, $id_caja)");
        
        // VERIFICA SI HAY ERROR
        if (!$query_procesar) {
            $error = mysqli_error($conection);
            error_log("Error MySQL: " . $error);
            echo json_encode(['error' => 'Error de base de datos: ' . $error]);
            mysqli_close($conection);
            exit;
        }
        
        $result_detalle = mysqli_num_rows($query_procesar);

        if ($result_detalle > 0) {
            $data = mysqli_fetch_assoc($query_procesar);
            error_log("Compra procesada exitosamente: " . print_r($data, true));
            
            // ✅ ASEGURA QUE RETORNE EL FORMATO CORRECTO
            echo json_encode([
                'nocompra' => $data['nocompra'],
                'codproveedor' => $codproveedor
            ], JSON_UNESCAPED_UNICODE);
        } else {
            error_log("No se retornó número de compra");
            echo json_encode(['error' => 'No se generó número de compra']);
        }
    } else {
        error_log("No hay productos en el carrito");
        echo json_encode(['error' => 'No hay productos en el carrito']);
    }
    mysqli_close($conection);
    exit;
}
	//Extraer datos del detalle_temp_compra
	if ($_POST['action'] == 'serchForDetalleCompra') {
		if (empty($_POST['user'])) {
			echo 'error';
		} else {

			$token = md5($_SESSION['idUser']);

			$query = mysqli_query($conection, "SELECT tmp.correlativo,
														tmp.token_user,
														tmp.cantidad,
														tmp.precio_venta,
														p.codproducto,
														p.descripcion
													FROM detalle_temp_compra tmp
													INNER JOIN producto p
													ON tmp.codproducto = p.codproducto
													WHERE token_user = '$token' ");
			$result = mysqli_num_rows($query);

			$query_iva = mysqli_query($conection, "SELECT iva, moneda FROM configuracion");
			$result_iva = mysqli_num_rows($query_iva);

			$detalleTabla = '';
			$sub_total    = 0;
			$iva          = 0;
			$total        = 0;
			$arrayData    = array();

			if ($result > 0) {
				if ($result_iva > 0) {
					$info_iva = mysqli_fetch_assoc($query_iva);
					$iva = $info_iva['iva'];
					$moned = $info_iva['moneda'];
				}

				while ($data = mysqli_fetch_assoc($query)) {
					$precioTotal1 = formatCant($data['cantidad'] * $data['precio_venta']);
					$precioTotal  = round($data['cantidad'] * $data['precio_venta'], 2);
					$sub_total    = round($sub_total + $precioTotal, 2);
					$total        = round($total + $precioTotal, 2);
					$precio_venta = formatCant($data['precio_venta']);

					$detalleTabla .= '<tr>
						                <td style="display:none;">' . $data['codproducto'] . '</td>
						                <td class="">' . $data['cantidad'] . '</td>
						                <td colspan="3">' . $data['descripcion'] . '</td>
						                <td class="textright">' . $precio_venta . '</td>
						                <td class="textright">' . $precioTotal1 . '</td>
						                <td class="">
							                <a class="link_delete" href="#" onclick="event.preventDefault();
							                  del_product_detalle_compra(' . $data['correlativo'] . ');"><i class="far fa-trash-alt"></i></a>
						                </td>
						             </tr>';
				}

				
				$descuento = !empty($_POST['descuento']) ? (float)$_POST['descuento'] : 0;
				$total_total = $sub_total;
				$monto_desc  = round(($total_total * $descuento) / 100, 2);
				$total_con_desc = formatCant($total_total - $monto_desc);

				$detalleTotales = '<tr>
						               <td colspan="5" class="textright">Sub Total ' . $moned . '</td>
						               <td class="textright">' . formatCant($total_total) . '</td>
						               <td></td>
					               </tr>
					               <tr>
						               <td colspan="5" class="textright">Descuento (' . $descuento . '%) ' . $moned . '</td>
						               <td class="textright">' . formatCant($monto_desc) . '</td>
						               <td></td>
					               </tr>
					               <tr>
						               <td colspan="5" class="textright">TOTAL ' . $moned . '</td>
						               <td class="textright">' . $total_con_desc . '</td>
						               <td></td>
					               </tr>';

				$arrayData['detalle'] = $detalleTabla;
				$arrayData['totales'] = $detalleTotales;
				$arrayData['total_con_desc_raw'] = round($total_total - $monto_desc, 2);

				echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
			} else {
				echo 'error';
			}
			mysqli_close($conection);
		}
		exit;
	}

	//Agregar producto al detalle temporal compras
	if ($_POST['action'] == 'addProductoDetalleCompra') {
        // echo "DEBUG: Entered addProductoDetalleCompra handler\n";
		if ((empty($_POST['producto']) && empty($_POST['txt_cod_producto_compra'])) || (empty($_POST['cantidad']) && empty($_POST['txt_cant_producto_compra']))) {
			echo 'error';
		} else {
			// Soportar ambos nombres de campos (formularios antiguos y nuevos)
			$codproducto = !empty($_POST['txt_cod_producto_compra']) ? (int)$_POST['txt_cod_producto_compra'] : (int)$_POST['producto'];
			$cantidad    = !empty($_POST['txt_cant_producto_compra']) ? (int)$_POST['txt_cant_producto_compra'] : (int)$_POST['cantidad'];
			$costo       = !empty($_POST['txt_precio_compra']) ? (float)$_POST['txt_precio_compra'] : (!empty($_POST['precio']) ? (float)$_POST['precio'] : 0);
			$fecha_venc  = !empty($_POST['txt_fecha_vencimiento']) ? "'" . mysqli_real_escape_string($conection, $_POST['txt_fecha_vencimiento']) . "'" : "NULL";
			$token       = md5($_SESSION['idUser']);

			$query_iva = mysqli_query($conection, "SELECT iva,moneda FROM configuracion");
			$result_iva = mysqli_num_rows($query_iva);

			// Usar INSERT directo para mayor flexibilidad con campos nuevos (vencimiento)
			$query_insert = mysqli_query($conection, "INSERT INTO detalle_temp_compra (token_user, codproducto, cantidad, precio_venta, fecha_vencimiento) 
													  VALUES ('$token', $codproducto, $cantidad, $costo, $fecha_venc)");

			$detalleTabla = '';
			$sub_total    = 0;
			$iva          = 0;
			$total        = 0;
			$arrayData    = array();

			if ($query_insert) {
				if ($result_iva > 0) {
					$info_iva = mysqli_fetch_assoc($query_iva);
					$iva = $info_iva['iva'];
					$moned = $info_iva['moneda'];
				}

				$query_detalle = mysqli_query($conection, "SELECT tmp.correlativo,
																tmp.token_user,
																tmp.cantidad,
																tmp.precio_venta,
																tmp.fecha_vencimiento,
																p.codproducto,
																p.descripcion
															FROM detalle_temp_compra tmp
															INNER JOIN producto p
															ON tmp.codproducto = p.codproducto
															WHERE token_user = '$token' ");

				$result = mysqli_num_rows($query_detalle);

				if ($result > 0) {
					while ($data = mysqli_fetch_assoc($query_detalle)) {
						$precioTotal1 = formatCant($data['cantidad'] * $data['precio_venta']);
						$precioTotal  = round($data['cantidad'] * $data['precio_venta'], 2);
						$sub_total    = round($sub_total + $precioTotal, 2);
						$total        = round($total + $precioTotal, 2);
						$precio_venta = formatCant($data['precio_venta']);

						// Formatear fecha de vencimiento para la tabla
						$vencimiento_html = '';
						if (!empty($data['fecha_vencimiento']) && $data['fecha_vencimiento'] != '0000-00-00') {
							$fecha_formateada = date('d/m/Y', strtotime($data['fecha_vencimiento']));
							$vencimiento_html = '<br><small style="color: #d32f2f;">Vence: ' . $fecha_formateada . '</small>';
						}

						$detalleTabla .= '<tr>
											<td style="display:none;">' . $data['codproducto'] . '</td>
											 <td class="">' . $data['cantidad'] . '</td>
											<td colspan="3">' . $data['descripcion'] . $vencimiento_html . '</td>
											<td class="textright">' . $precio_venta . '</td>
											<td class="textright">' . $precioTotal1 . '</td>
											<td class="">
												<a class="link_delete" href="#" onclick="event.preventDefault();
												  del_product_detalle_compra(' . $data['correlativo'] . ');"><i class="far fa-trash-alt"></i></a>
											</td>
										 </tr>';
					}

					$descuento = !empty($_POST['descuento']) ? (float)$_POST['descuento'] : 0;
					$total_total = $sub_total;
					$monto_desc  = round(($total_total * $descuento) / 100, 2);
					$total_con_desc = formatCant($total_total - $monto_desc);

					$detalleTotales = '<tr>
										   <td colspan="5" class="textright">Sub Total ' . $moned . '</td>
										   <td class="textright">' . formatCant($total_total) . '</td>
										   <td></td>
									   </tr>
									   <tr>
										   <td colspan="5" class="textright">Descuento (' . $descuento . '%) ' . $moned . '</td>
										   <td class="textright">' . formatCant($monto_desc) . '</td>
										   <td></td>
									   </tr>
									   <tr>
										   <td colspan="5" class="textright">TOTAL ' . $moned . '</td>
										   <td class="textright">' . $total_con_desc . '</td>
										   <td></td>
									   </tr>';

					$arrayData['detalle'] = $detalleTabla;
					$arrayData['totales'] = $detalleTotales;
					$arrayData['total_con_desc_raw'] = round($total_total - $monto_desc, 2);

					echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
				} else {
					echo 'error';
				}
			} else {
				echo 'error';
			}
			mysqli_close($conection);
		}
		exit;
	}

	//Devolucion
	if ($_POST['action'] == 'devolucion') {
		//print_r($_POST);
		if (empty($_POST['noVenta_dev']) || empty($_POST['codProducto_dev']) || empty($_POST['cantProducto_dev']) || $_POST['cantProducto_dev'] <= 0) {
			$code = '1';
			$msg = "Todos los campos son abligatorios y la cantidad debe ser mayor a cero.";
		} else {
			$venta 		= $_POST['noVenta_dev'];
			$codigo 	= $_POST['codProducto_dev'];
			$cantidad 	= $_POST['cantProducto_dev'];
			$user 		= $_SESSION['idUser'];
			$result 	= 0;

			$query_producto = mysqli_query($conection, "SELECT codproducto,existencia FROM producto WHERE codigo = '$codigo' AND status = 1");
			$result_producto = mysqli_num_rows($query_producto);
		}

		if ($result_producto > 0) {
			$data_producto = mysqli_fetch_assoc($query_producto);
			$codproducto = $data_producto['codproducto'];
			$existencia = $data_producto['existencia'];
		}
		$query_det_venta = mysqli_query($conection, "SELECT * FROM detalleventa WHERE noventa = $venta AND codproducto = $codproducto ");
		$result_det_venta = mysqli_num_rows($query_det_venta);
		if ($result_det_venta > 0) {
			$info_det_venta = mysqli_fetch_assoc($query_det_venta);
		}

		if ($cantidad > $info_det_venta['cantidad']) {
			$code = '2';
			$msg = 'No se puede aplicar esta devolucion.';
		} else {
			$cant_vendido = $info_det_venta['cantidad'];
			$precio_vendido = $info_det_venta['precio_venta'];
			$totalventa_detalle = $cantidad * $precio_vendido;
			$nueva_cantidad_det = $cant_vendido - $cantidad;

			$query_venta = mysqli_query($conection, "SELECT * FROM venta WHERE noventa = $venta");
			$result_venta = mysqli_num_rows($query_venta);
			if ($result_venta > 0) {
				$info_venta = mysqli_fetch_assoc($query_venta);
				$totalventa = $info_venta['totalventa'];
			}
			$nuevo_total = $totalventa - $totalventa_detalle;
			$nueva_cantidad = $existencia + $cantidad;

			$query_upd_venta = mysqli_query($conection, "UPDATE venta SET totalventa = $nuevo_total WHERE noventa = $venta");

			$query_upd_producto = mysqli_query($conection, "UPDATE producto SET existencia = $nueva_cantidad WHERE codproducto = $codproducto");

			if ($cantidad == $info_det_venta['cantidad']) {
				$query_upd_det_venta = mysqli_query($conection, "DELETE FROM detalleventa WHERE noventa = $venta AND codproducto = $codproducto");
			} else {
				$query_upd_det_venta = mysqli_query($conection, "UPDATE detalleventa SET cantidad = $nueva_cantidad_det WHERE noventa = $venta AND codproducto = $codproducto");
			}


			mysqli_close($conection);
			if ($query_upd_det_venta) {
				$code = '00';
				$msg = "Devolución realizada con exito.";
			} else {
				$code = '2';
				$msg = "Error al registar la devolución.";
			}
		}
		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Anular Venta
	if ($_POST['action'] == 'anularRecibo') {
		print_r($_POST);
		$noventa = $_POST['noventa'];
		$query_del = mysqli_query($conection, "DELETE FROM venta WHERE noventa = $noventa");
		mysqli_close($conection);
		if ($query_del) {
			echo 'ok';
		} else {
			echo 'error';
		}
		exit;
	}

	//Nuevo egreso
	if ($_POST['action'] == 'nuevoEgreso') {
		//print_r($_POST);
		if (empty($_POST['descEgreso']) || empty($_POST['cantEgreso']) || $_POST['cantEgreso'] <= 0) {
			$code = '1';
			$msg = "Todos los campos son abligatorios y la cantidad debe ser mayor a cero.";
		} else {
			$descripcion = $_POST['descEgreso'];
			$cantidad 	= $_POST['cantEgreso'];
			$user 		= $_SESSION['idUser'];
			$result 	= 0;

			$query_insert = mysqli_query($conection, "INSERT INTO egresos(descripcion,cantidad,usuario,caja)
					                                      			VALUES('$descripcion',$cantidad,$user,$id_caja)");

			mysqli_close($conection);
			if ($query_insert) {
				$code = '00';
				$msg = "Egreso registrado correctamente";
			} else {
				$code = '2';
				$msg = "Error al registar el egreso";
			}
		}
		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Nueva caja
	if ($_POST['action'] == 'nuevaCaja') {
		//print_r($_POST);
		if (empty($_POST['inicioCaja']) || $_POST['inicioCaja'] < 0) {
			$code = '1';
			$msg = "Todos los campos son abligatorios y la cantidad no puede ser negativa.";
		} else {
			$cantidad 	= $_POST['inicioCaja'];
			$user 		= $_SESSION['idUser'];
			$result 	= 0;

			$query_insert = mysqli_query($conection, "INSERT INTO caja(inicio,usuario)
					                                      			VALUES($cantidad,$user)");

			mysqli_close($conection);
			if ($query_insert) {
				$code = '00';
				$msg = "Caja abierta correctamente";
			} else {
				$code = '2';
				$msg = "Error al abrir la caja";
			}
		}
		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}

	//Cerrar caja
	if ($_POST['action'] == 'cerrarCaja') {
		//print_r($_POST);exit;
		if (empty($_POST['id_caja'])) {
			$code = '1';
			$msg = "Todos los campos son abligatorios.";
		} else {
			$id 		= $_POST['id_caja'];
			if ($_POST['cant_inicio'] == '') {
				$inicio = 0;
			} else {
				$inicio = $_POST['cant_inicio'];
			}
			if ($_POST['cant_ventas'] == '') {
				$ventas = 0;
			} else {
				$ventas = $_POST['cant_ventas'];
			}
			if ($_POST['cant_abonos'] == '') {
				$abonos = 0;
			} else {
				$abonos = $_POST['cant_abonos'];
			}
			if ($_POST['cant_creditos'] == '') {
				$creditos = 0;
			} else {
				$creditos = $_POST['cant_creditos'];
			}
			if ($_POST['cant_egreso'] == '') {
				$egreso = 0;
			} else {
				$egreso = $_POST['cant_egreso'];
			}
			if ($_POST['total_cierre'] == '') {
				$total 	= 0;
			} else {
				$total 	= $_POST['total_cierre'];
			}

			$user 		= $_SESSION['idUser'];
			$result 	= 0;
			//print_r($total);exit;

			$query_insert = mysqli_query($conection, "UPDATE caja SET ventas 	= $ventas,
																		 abonos 	= $abonos,
																		 egresos 	= $egreso,
																		 creditos 	= $creditos,
																		 total_efectivo = $total,
																		 status 	= 2 
															       WHERE id  		= $id");

			mysqli_close($conection);
			if ($query_insert) {
				$code = '00';
				$msg = "Caja cerrada correctamente";
			} else {
				$code = '2';
				$msg = "No se puede cerrar la caja";
			}
		}
		$arrData = array('cod' => $code, 'msg' => $msg);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}
	
	// Obtener detalle de una venta (Productos) para vista rápida
	if ($_POST['action'] == 'getDetalleVenta') {
		if (empty($_POST['noventa'])) {
			echo 'error';
		} else {
			$noventa = $_POST['noventa'];
			$query = mysqli_query($conection, "SELECT d.cantidad, d.precio_venta, p.descripcion 
												FROM detalleventa d 
												INNER JOIN producto p ON d.codproducto = p.codproducto 
												WHERE d.noventa = $noventa");
			$result = mysqli_num_rows($query);
			$detalle = '';
			if ($result > 0) {
				$detalle .= '<table class="tabla_detalle_rapido" style="width: 100%; border-collapse: collapse; margin: 10px 0; background: #f9f9f9; border: 1px solid #ddd;">
								<thead>
									<tr style="background: #eee; text-align: left;">
										<th style="padding: 8px; border-bottom: 2px solid #ddd;">Cant.</th>
										<th style="padding: 8px; border-bottom: 2px solid #ddd;">Descripción</th>
										<th style="padding: 8px; border-bottom: 2px solid #ddd;" class="textright">Precio</th>
										<th style="padding: 8px; border-bottom: 2px solid #ddd;" class="textright">Subtotal</th>
									</tr>
								</thead>
								<tbody>';
				while ($data = mysqli_fetch_assoc($query)) {
					$subtotal = $data['cantidad'] * $data['precio_venta'];
					$detalle .= '<tr>
									<td style="padding: 8px; border-bottom: 1px solid #eee;">' . $data['cantidad'] . '</td>
									<td style="padding: 8px; border-bottom: 1px solid #eee;">' . $data['descripcion'] . '</td>
									<td style="padding: 8px; border-bottom: 1px solid #eee;" class="textright">' . formatCant($data['precio_venta']) . '</td>
									<td style="padding: 8px; border-bottom: 1px solid #eee;" class="textright">' . formatCant($subtotal) . '</td>
								</tr>';
				}
				$detalle .= '</tbody></table>';
				echo $detalle;
			} else {
				echo '<div style="padding: 10px; color: #666;">No hay detalles registrados para esta venta.</div>';
			}
		}
		mysqli_close($conection);
		exit;
	}

	// ============================================
	// EDITAR MONTO INICIAL DE CAJA
	// ============================================
	if ($_POST['action'] == 'editarInicioCaja') {
		if ($_SESSION['rol'] != 1) {
			echo json_encode(['cod' => '1', 'msg' => 'No tiene permisos para esta acción'], JSON_UNESCAPED_UNICODE);
			exit;
		}
		if (empty($_POST['id_caja']) || !isset($_POST['nuevo_inicio'])) {
			echo json_encode(['cod' => '1', 'msg' => 'Datos incompletos'], JSON_UNESCAPED_UNICODE);
			exit;
		}
		$id_caja = (int)$_POST['id_caja'];
		$nuevo_inicio = (float)$_POST['nuevo_inicio'];

		$query_upd = mysqli_query($conection, "UPDATE caja SET inicio = $nuevo_inicio WHERE id = $id_caja AND status = 1");
		if ($query_upd) {
			echo json_encode(['cod' => '00', 'msg' => 'Monto inicial actualizado correctamente'], JSON_UNESCAPED_UNICODE);
		} else {
			echo json_encode(['cod' => '2', 'msg' => 'Error al actualizar: ' . mysqli_error($conection)], JSON_UNESCAPED_UNICODE);
		}
		mysqli_close($conection);
		exit;
	}

}

