<?php

	//print_r($_REQUEST);
	//exit;
	//echo base64_encode('2');
	//exit;
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

	//Busqueda por rango de fecha administrador
				if (isset($_REQUEST['fecha_de']) || isset($_REQUEST['fecha_a'])) {
					$fecha_de = mysqli_escape_string($conection,$_REQUEST['fecha_de']);
					$fecha_a = mysqli_escape_string($conection,$_REQUEST['fecha_a']);
					$f_de = $fecha_de.' 00:00:00';
					$f_a = $fecha_a.' 23:59:59';
					
					$cond_extra = " AND f.fecha BETWEEN '{$f_de}' AND '{$f_a}' AND (f.status = 1 OR f.status = 4)";

					// Filtro por rol
					if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2) {
						$cond_extra .= " AND f.usuario = " . $_SESSION['idUser'];
					}

					// 1. Filtro por búsqueda general
					if (!empty($_REQUEST['busqueda'])) {
						$Busqueda = mysqli_escape_string($conection, $_REQUEST['busqueda']);
						$cond_extra .= " AND (u.nombre LIKE '%$Busqueda%' OR cl.nombre LIKE '%$Busqueda%' OR f.noventa LIKE '%$Busqueda%' OR f.fecha LIKE '%$Busqueda%')";
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
				}

				$result = mysqli_num_rows($query);

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
			$dompdf->stream('reporte.pdf',array('Attachment'=>$attachment));
			exit;
		
	

?>