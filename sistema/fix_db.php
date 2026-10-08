<?php
include "../conexion.php";

// Definir el nuevo procedimiento almacenado
$proc = "DROP PROCEDURE IF EXISTS `procesar_venta`;";
$query_drop = mysqli_query($conection, $proc);

if($query_drop){
    echo "<p>Procedimiento antiguo eliminado.</p>";
} else {
    echo "<p>Error al eliminar procedimiento: " . mysqli_error($conection) . "</p>";
}

$proc_create = "
CREATE PROCEDURE `procesar_venta` (IN `cod_usuario` INT, IN `cod_cliente` INT, IN `token` VARCHAR(50), IN `tipo_pago` INT, IN `id_caja` INT, IN `descuento` INT)
BEGIN
    DECLARE venta INT;
    DECLARE registros INT;
    DECLARE subtotal DECIMAL(10,2);
    DECLARE total DECIMAL(10,2);
    
    DECLARE nueva_existencia int;
    DECLARE existencia_actual int;
    
    DECLARE tmp_cod_producto int;
    DECLARE tmp_cant_producto int;
    DECLARE a INT;
    SET a = 1;
    
    CREATE TEMPORARY TABLE tbl_tmp_tokenuser(
            id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            cod_prod BIGINT,
            cant_prod int);
            
    SET registros = (SELECT COUNT(*) FROM detalle_temp WHERE token_user = token);
    
    IF registros > 0 THEN
        INSERT INTO tbl_tmp_tokenuser(cod_prod,cant_prod) SELECT codproducto,cantidad FROM detalle_temp WHERE token_user = token;
        
        -- CORRECCION: Status fijo en 1 y tipo_pago en su columna correcta
        INSERT INTO venta(usuario,caja,codcliente,status,descuento,tipo_pago_detalle) VALUES(cod_usuario,id_caja,cod_cliente,1,descuento,tipo_pago);
        SET venta = LAST_INSERT_ID();
        
        INSERT INTO detalleventa(noventa,codproducto,cantidad,costo,precio_venta) SELECT(venta) as noventa, codproducto,cantidad,costo,precio_venta FROM detalle_temp WHERE token_user = token;
        
        WHILE a <= registros DO
            SELECT cod_prod,cant_prod INTO tmp_cod_producto,tmp_cant_producto FROM tbl_tmp_tokenuser WHERE id = a;
            SET a=a+1;
        END WHILE;
        
        SET subtotal = (SELECT SUM(cantidad * precio_venta) FROM detalle_temp WHERE token_user = token);
        SET total = subtotal - descuento;
        UPDATE venta SET totalventa = total WHERE noventa = venta;
        DELETE FROM detalle_temp WHERE token_user = token;
        TRUNCATE TABLE tbl_tmp_tokenuser;
        SELECT * FROM venta WHERE noventa = venta;
        
    ELSE
        SELECT 0;
    END IF;
END";

if(mysqli_query($conection, $proc_create)){
    echo "<h1>¡Base de datos reparada con EXITO!</h1>";
    echo "<p>El procedimiento 'procesar_venta' ha sido corregido.</p>";
    echo "<p>Ahora las ventas guardarán correctamente el Estado en 1 y el Tipo de Pago en su columna correspondiente.</p>";
    echo "<p>Puede borrar este archivo.</p>";
} else {
    echo "<h1>ERROR</h1>";
    echo "<p>No se pudo crear el procedimiento: " . mysqli_error($conection) . "</p>";
}
?>
