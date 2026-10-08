<?php 
session_start();
include "../conexion.php";

// ✅ Verificar que hay caja abierta
$query_caja = mysqli_query($conection, "SELECT * FROM caja WHERE status = 1");
$result_caja = mysqli_num_rows($query_caja);
if ($result_caja == 0) {
    header("Location: index.php");
    exit;
}

// ✅ Productos que vencen en los próximos 30 días
$query = mysqli_query($conection,"
    SELECT 
        p.codigo,
        p.descripcion,
        p.existencia,
        e.fecha_vencimiento,
        DATEDIFF(e.fecha_vencimiento, CURDATE()) as dias_restantes
    FROM producto p
    INNER JOIN entradas e ON p.codproducto = e.codproducto
    WHERE e.fecha_vencimiento IS NOT NULL
    AND e.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND e.fecha_vencimiento >= CURDATE()
    AND p.status = 1
    GROUP BY p.codproducto, e.fecha_vencimiento
    ORDER BY e.fecha_vencimiento ASC
");

$result = mysqli_num_rows($query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Productos por Vencer | Sistema Facturación</title>
    <?php include "includes/scripts.php"; ?>
    <style>
        .vence-urgente { 
            background-color: #ffebee !important; 
            font-weight: bold;
        }
        .vence-proximo { 
            background-color: #fff3e0 !important; 
        }
        .vence-normal { 
            background-color: #f1f8e9 !important; 
        }
        .alert-info {
            padding: 15px;
            margin: 20px 0;
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            color: #0c5460;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .legend {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .legend-box {
            width: 30px;
            height: 20px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>
    
    <section id="container">
        <div class="title_page">
            <h1><i class="fas fa-exclamation-triangle"></i> Productos Próximos a Vencer</h1>
        </div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-box" style="background-color: #ffebee;"></div>
                <span>Urgente (≤ 7 días)</span>
            </div>
            <div class="legend-item">
                <div class="legend-box" style="background-color: #fff3e0;"></div>
                <span>Próximo (8-15 días)</span>
            </div>
            <div class="legend-item">
                <div class="legend-box" style="background-color: #f1f8e9;"></div>
                <span>Normal (16-30 días)</span>
            </div>
        </div>

        <?php if($result > 0){ ?>
            <div class="alert-info">
                <i class="fas fa-info-circle"></i> 
                Se encontraron <?php echo $result; ?> producto(s) próximo(s) a vencer
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Existencia</th>
                        <th>Fecha Vencimiento</th>
                        <th>Días Restantes</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    while($data = mysqli_fetch_array($query)){
                        $clase = '';
                        $estado = '';
                        
                        if($data['dias_restantes'] <= 7){
                            $clase = 'vence-urgente';
                            $estado = '<span style="color: #c62828;">⚠️ URGENTE</span>';
                        }elseif($data['dias_restantes'] <= 15){
                            $clase = 'vence-proximo';
                            $estado = '<span style="color: #f57c00;">⚠ Próximo</span>';
                        }else{
                            $clase = 'vence-normal';
                            $estado = '<span style="color: #558b2f;">✓ Normal</span>';
                        }
                    ?>
                    <tr class="<?php echo $clase; ?>">
                        <td><?php echo $data['codigo']; ?></td>
                        <td><?php echo $data['descripcion']; ?></td>
                        <td class="textcenter"><?php echo $data['existencia']; ?></td>
                        <td class="textcenter">
                            <?php echo date('d/m/Y', strtotime($data['fecha_vencimiento'])); ?>
                        </td>
                        <td class="textcenter">
                            <strong><?php echo $data['dias_restantes']; ?></strong> días
                        </td>
                        <td class="textcenter"><?php echo $estado; ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>

        <?php }else{ ?>
            <div class="no-data">
                <i class="fas fa-check-circle" style="font-size: 60px; color: #4caf50;"></i>
                <h2>¡Todo en orden!</h2>
                <p>No hay productos próximos a vencer en los próximos 30 días.</p>
            </div>
        <?php } ?>

        <?php 
        // ✅ Liberar recursos
        mysqli_free_result($query);
        mysqli_close($conection);
        ?>
    </section>
</body>
</html>