<?php
	session_start();
	if(empty($_SESSION['active']))
	{
		header('location: ../');
	}

	include "../../conexion.php";
	require_once '../numeroaletrasphp/numeroaletras.php';
	require_once '../pdf/vendor/autoload.php';
	use Dompdf\Dompdf;
	
	$numtoletra = new modelonumero(); 
	$numeroaletra = new numeroaletras();
	
	if(empty($_REQUEST['cl']) || empty($_REQUEST['f']))
	{
		echo "No es posible generar la factura.";
		exit;
	}
	
	$codCliente = $_REQUEST['cl'];
	$noFactura = $_REQUEST['f'];
	$anulada = '';

	// Inicializar variables que se usarán en recibo.php
	$configuracion = [];
	$result_config = 0;
	$detalles = [];
	$result_detalle = 0;

	// Consultar configuración
	$query_config = mysqli_query($conection,"SELECT * FROM configuracion");
	$result_config = mysqli_num_rows($query_config);

	if($result_config > 0){
		$configuracion = mysqli_fetch_assoc($query_config);
	}

	// Consultar datos de la venta
	$query = mysqli_query($conection,"SELECT 
											f.noventa, 
											DATE_FORMAT(f.fecha, '%d/%m/%Y') as fecha, 
											DATE_FORMAT(f.fecha,'%H:%i:%s') as hora, 
											f.codcliente, 
											f.status,
											COALESCE(f.abono, 0) as abono,
											v.nombre as vendedor,
											cl.nit, 
											cl.nombre, 
											cl.telefono,
											cl.direccion
										FROM venta f
										INNER JOIN usuario v
										ON f.usuario = v.idusuario
										INNER JOIN cliente cl
										ON f.codcliente = cl.idcliente
										WHERE f.noventa = $noFactura AND f.codcliente = $codCliente ");

	$result = mysqli_num_rows($query);
	
	if($result > 0){

		$factura = mysqli_fetch_assoc($query);
		$no_factura = $factura['noventa'];
		$idcliente = $factura['codcliente'];
		
		// Sanitizar datos numéricos de la factura
		$factura['abono'] = floatval($factura['abono']);

		// Verificar si está anulada
		if($factura['status'] == 2){
			$anulada = '<img class="anulada" src="img/anulado.png" alt="Anulada" style="max-width: 100px;">';
		}

		// Consultar detalle del recibo
		$query_productos = mysqli_query($conection,"SELECT 
												dt.id,
												dt.noventa,
												DATE_FORMAT(dt.fecha, '%d/%m/%Y') as fecha,
												DATE_FORMAT(dt.fecha,'%H:%i:%s') as hora,
												COALESCE(dt.saldo_anterior, 0) as saldo_anterior,
												COALESCE(dt.cantidad, 0) as cantidad,
												COALESCE(dt.saldo_actual, 0) as saldo_actual,
												u.nombre 
												FROM detalle_recibo dt 
												INNER JOIN usuario u
												ON dt.usuario = u.idusuario
												WHERE noventa = $noFactura");
		
		$result_detalle = mysqli_num_rows($query_productos);

		// Sanitizar los datos de detalle antes de pasar al PDF
		if($result_detalle > 0) {
			while($row = mysqli_fetch_assoc($query_productos)) {
				$detalles[] = [
					'id' => $row['id'],
					'noventa' => $row['noventa'],
					'fecha' => $row['fecha'],
					'hora' => $row['hora'],
					'saldo_anterior' => floatval($row['saldo_anterior']),
					'cantidad' => floatval($row['cantidad']),
					'saldo_actual' => floatval($row['saldo_actual']),
					'nombre' => $row['nombre']
				];
			}
		}

		// Generar HTML del recibo
		ob_start();
		include(dirname('__FILE__').'/recibo.php');
		$html = ob_get_clean();

		// Crear PDF con Dompdf
		$dompdf = new Dompdf();
		$dompdf->loadHtml($html);
		
		// Configurar tamaño de papel para ticket térmico
		$paper_size = array(0, 0, 204, 650);
		$dompdf->setPaper($paper_size);
		
		// Renderizar PDF
		$dompdf->render();
		
		// Enviar PDF al navegador
		$dompdf->stream('recibo_'.$noFactura.'.pdf', array('Attachment' => 0));
		exit;

	} else {
		echo "No se encontró la factura especificada.";
		exit;
	}

?>