<?php
include "../conexion.php";

echo "<h1>Reparación de Procedimiento procesar_venta</h1>";

// 1. Eliminar anterior
$sql_drop = "DROP PROCEDURE IF EXISTS `procesar_venta` ;";
if(mysqli_query($conection, $sql_drop)){
    echo "<p>Procedimiento anterior eliminado.</p>";
}

// 2. Crear nuevo con lógica de estado corregida
$sql_create = "
CREATE PROCEDURE `procesar_venta`(
    IN cod_usuario INT, 
    IN cod_cliente INT, 
    IN token VARCHAR(50), 
    IN tipo_pago INT, 
    IN id_caja INT, 
    IN descuento DECIMAL(10,2), 
    IN pago_con DECIMAL(10,2), 
    IN vuelto DECIMAL(10,2)
)
BEGIN
    DECLARE venta INT;
    DECLARE registros INT;
    DECLARE subtotal DECIMAL(10,2);
    DECLARE total DECIMAL(10,2);
    DECLARE actual_status INT;
    DECLARE a INT;
    SET a = 1;

    -- Determinar el estado real de la venta
    -- 1 = Pagado, 3 = Crédito
    IF (tipo_pago = 3) THEN
        SET actual_status = 3;
    ELSE
        SET actual_status = 1;
    END IF;

    CREATE TEMPORARY TABLE tbl_tmp_tokenuser(
        id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        cod_prod BIGINT,
        cant_prod int
    );

    SET registros = (SELECT COUNT(*) FROM detalle_temp WHERE token_user = token);

    IF registros > 0 THEN
        INSERT INTO tbl_tmp_tokenuser(cod_prod,cant_prod) 
        SELECT codproducto,cantidad FROM detalle_temp WHERE token_user = token;

        -- Insertar con el estado corregido y guardar el tipo de pago en su columna detalle
        INSERT INTO venta(usuario,caja,codcliente,status,descuento,pago_con,vuelto,tipo_pago_detalle) 
        VALUES(cod_usuario,id_caja,cod_cliente,actual_status,descuento,pago_con,vuelto,tipo_pago);
        
        SET venta = LAST_INSERT_ID();

        INSERT INTO detalleventa(noventa,codproducto,cantidad,costo,precio_venta) 
        SELECT(venta) as noventa, codproducto,cantidad,costo,precio_venta 
        FROM detalle_temp WHERE token_user = token;

        SET subtotal = (SELECT SUM(cantidad * precio_venta) FROM detalle_temp WHERE token_user = token);
        SET total = subtotal - descuento;
        
        UPDATE venta SET totalventa = total WHERE noventa = venta;
        
        DELETE FROM detalle_temp WHERE token_user = token;
        TRUNCATE TABLE tbl_tmp_tokenuser;
        
        SELECT * FROM venta WHERE noventa = venta;
    ELSE
        SELECT 0;
    END IF;
END;
";

if(mysqli_multi_query($conection, $sql_create)){
    echo "<h2>¡REPARACIÓN COMPLETADA!</h2>";
    echo "<p>Ahora las ventas con Transferencia y QR se guardarán con estado 1 (Pagado) correctamente.</p>";
} else {
    echo "<h2>ERROR</h2><p>".mysqli_error($conection)."</p>";
}

mysqli_close($conection);
?>
