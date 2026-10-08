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
		$moned = $configuracion['moneda'];
	}

	$usuario = $_SESSION['idUser'];

	// Filtros
	$fecha_de = isset($_GET['f_de']) ? mysqli_real_escape_string($conection, $_GET['f_de']) : date('Y-m-d');
	$fecha_a = isset($_GET['f_a']) ? mysqli_real_escape_string($conection, $_GET['f_a']) : date('Y-m-d');
	$tipo = isset($_GET['t']) ? $_GET['t'] : '';
    $busqueda = isset($_GET['b']) ? mysqli_real_escape_string($conection, $_GET['b']) : '';

	$where = "DATE(e.fecha) BETWEEN '$fecha_de' AND '$fecha_a'";
	if ($tipo != '') {
		$where .= " AND e.tipo_egreso = " . (int)$tipo;
	}
    if ($busqueda != '') {
        $where .= " AND e.descripcion LIKE '%$busqueda%'";
    }

	// Obtener totales para el resumen del PDF
	$query_totales = mysqli_query($conection, "
		SELECT 
			SUM(CASE WHEN tipo_egreso = 1 THEN cantidad ELSE 0 END) as total_general,
			SUM(CASE WHEN tipo_egreso = 2 THEN cantidad ELSE 0 END) as total_insumos,
			SUM(cantidad) as total_egresos
		FROM egresos e
		WHERE $where
	");
	$resumen = mysqli_fetch_assoc($query_totales);

	// Obtener lista detallada
	$sql = "SELECT e.*, u.nombre as usuario_nombre
			FROM egresos e
			INNER JOIN usuario u ON e.usuario = u.idusuario
			WHERE $where
			ORDER BY e.fecha DESC";

	$query = mysqli_query($conection, $sql);
	$result = mysqli_num_rows($query);

	ob_start();
	include(dirname(__FILE__).'/reporteEgresoPdf.php');
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
	$dompdf->stream('reporte_egresos.pdf',array('Attachment'=>$attachment));
	exit;
?>
