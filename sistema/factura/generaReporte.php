<?php
	session_start();
	if(empty($_SESSION['active']))
	{
		header('location: ../');
	}

	include "../../conexion.php";
	require_once '../pdf/vendor/autoload.php';
	use Dompdf\Dompdf;

	$query_conf = mysqli_query($conection,"SELECT * FROM configuracion");
	$result_conf = mysqli_num_rows($query_conf);
	if($result_conf > 0){
		$configuracion = mysqli_fetch_assoc($query_conf);
	}

	$usuario = $_SESSION['idUser'];

	// Construcción dinámica de la consulta unificada
	$cond_extra = " AND f.status != 3 ";

	// Filtro por rol (Vendedor solo ve lo suyo)
	if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2) {
		$cond_extra .= " AND f.usuario = $usuario";
	}

	// 1. Filtro por búsqueda general
	if (!empty($_REQUEST['busqueda'])) {
		$busqueda = mysqli_escape_string($conection, $_REQUEST['busqueda']);
		$cond_extra .= " AND (u.nombre LIKE '%$busqueda%' OR cl.nombre LIKE '%$busqueda%' OR f.noventa LIKE '%$busqueda%' OR f.fecha LIKE '%$busqueda%')";
	}

	// 2. Filtro por producto
	$join_detalle = "";
	if (!empty($_REQUEST['producto'])) {
		$producto = mysqli_escape_string($conection, $_REQUEST['producto']);
		$join_detalle = " INNER JOIN detalleventa dv ON f.noventa = dv.noventa ";
		$cond_extra .= " AND dv.codproducto = '$producto'";
	}

	// 3. Filtro por tipo de pago
	if (!empty($_REQUEST['tipo_pago'])) {
		$tp = mysqli_escape_string($conection, $_REQUEST['tipo_pago']);
		if ($tp == '1') {
			$cond_extra .= " AND (f.tipo_pago_detalle IS NULL OR f.tipo_pago_detalle = 1)";
		} else if ($tp == 'otros') {
			$cond_extra .= " AND f.tipo_pago_detalle IN (2,4,5)";
		} else {
			$cond_extra .= " AND f.tipo_pago_detalle = '$tp'";
		}
	}

	$sql = "SELECT f.noventa, f.fecha, f.totalventa, f.codcliente, f.status, f.abono, f.tipo_pago_detalle,
				   u.nombre as vendedor, cl.nombre as cliente
			FROM venta f
			INNER JOIN usuario u ON f.usuario = u.idusuario
			INNER JOIN cliente cl ON f.codcliente = cl.idcliente
			$join_detalle
			WHERE 1=1 $cond_extra
			ORDER BY f.fecha DESC, f.noventa DESC";

	$query = mysqli_query($conection, $sql);

	// Verificar si la consulta fue ejecutada correctamente
	if(!$query){
		die('Error en la consulta: ' . mysqli_error($conection));
	}

	// IMPORTANTE: Obtener el número de resultados AQUÍ
	$result = mysqli_num_rows($query);

	// ============================================
	// FUNCIÓN PARA OBTENER TIPO DE PAGO
	// ============================================
	function obtenerTipoPago($conection, $tipo_pago_detalle, $noventa) {
		$tipos = array(
			1 => 'Efectivo',
			2 => 'Transferencia',
			3 => 'Crédito',
			4 => 'QR',
			5 => 'Tarjeta'
		);
		
		$tipoPagoTexto = isset($tipos[$tipo_pago_detalle]) ? $tipos[$tipo_pago_detalle] : 'Efectivo';
		
		// Si es transferencia, obtener banco
		if ($tipo_pago_detalle == 2) {
			$query_trans = mysqli_query($conection, "SELECT b.nombre as banco 
													  FROM transferencias t
													  INNER JOIN bancos b ON t.banco_id = b.id
													  WHERE t.noventa = $noventa LIMIT 1");
			if ($query_trans && mysqli_num_rows($query_trans) > 0) {
				$trans = mysqli_fetch_assoc($query_trans);
				$tipoPagoTexto .= ' (' . $trans['banco'] . ')';
			}
		}
		
		// Si es QR, obtener tipo de QR
		if ($tipo_pago_detalle == 4) {
			$query_qr = mysqli_query($conection, "SELECT tipo_qr FROM pagos_qr WHERE noventa = $noventa LIMIT 1");
			if ($query_qr && mysqli_num_rows($query_qr) > 0) {
				$qr = mysqli_fetch_assoc($query_qr);
				$tipoQR = str_replace('_', ' ', ucfirst($qr['tipo_qr']));
				$tipoPagoTexto .= ' (' . $tipoQR . ')';
			}
		}
		
		// Si es tarjeta, obtener tipo y banco
		if ($tipo_pago_detalle == 5) {
			$query_tarjeta = mysqli_query($conection, "SELECT tipo_tarjeta, banco, ultimos_digitos 
														FROM pagos_tarjeta 
														WHERE noventa = $noventa LIMIT 1");
			if ($query_tarjeta && mysqli_num_rows($query_tarjeta) > 0) {
				$tarjeta = mysqli_fetch_assoc($query_tarjeta);
				$tipoTarjeta = ucfirst($tarjeta['tipo_tarjeta']);
				$tipoPagoTexto .= ' (' . $tipoTarjeta . ' ' . $tarjeta['banco'] . ' ****' . $tarjeta['ultimos_digitos'] . ')';
			}
		}
		
		return $tipoPagoTexto;
	}

	ob_start();
	include(dirname('__FILE__').'/reportePdf.php');
	$html = ob_get_clean();

	// instantiate and use the dompdf class
	$dompdf = new Dompdf();

	$dompdf->loadHtml($html);
	// (Optional) Setup the paper size and orientation
	$dompdf->setPaper('letter', 'portrait');
	// Render the HTML as PDF
	$dompdf->render();
	// Output the generated PDF to Browser
	$attachment = isset($_REQUEST['download']) ? 1 : 0;
	$dompdf->stream('reporte_ventas.pdf',array('Attachment'=>$attachment));
	exit;

?>