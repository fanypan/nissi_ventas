DELIMITER $$

DROP PROCEDURE IF EXISTS `dataDashboard`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `dataDashboard` (IN `caja_id` INT)  BEGIN
        DECLARE usuarios int;
        DECLARE clientes int;
        DECLARE proveedores int;
        DECLARE productos int;
        DECLARE ventas decimal(10,2);
        DECLARE abonos decimal(10,2);
        DECLARE pagos decimal(10,2);
        DECLARE compra decimal(10,2);
        DECLARE cobrar decimal(10,2);
        DECLARE pagar decimal(10,2);
        DECLARE egreso decimal(10,2);
        DECLARE credito decimal(10,2);
        DECLARE inicios decimal(10,2);
        
        SELECT COUNT(*) INTO usuarios FROM usuario WHERE status !=10;
        SELECT COUNT(*) INTO clientes FROM cliente WHERE status !=10;
        SELECT COUNT(*) INTO proveedores FROM proveedor WHERE status !=10;
        SELECT COUNT(*) INTO productos FROM producto WHERE status !=10;
        SELECT SUM(totalventa) INTO ventas FROM venta WHERE caja = caja_id AND status =1;
        SELECT SUM(totalventa) INTO credito FROM venta WHERE caja = caja_id AND status =3;
        SELECT SUM(cantidad) INTO abonos FROM detalle_recibo WHERE caja = caja_id;
        SELECT SUM(cantidad) INTO pagos FROM detalle_recibo_compra WHERE caja = caja_id;
        SELECT SUM(totalcompra) INTO compra FROM compras WHERE caja = caja_id AND status =1;
        SELECT SUM(cantidad) INTO egreso FROM egresos WHERE caja = caja_id;
        SELECT SUM(totalventa-abono) INTO cobrar FROM venta WHERE status =3;
        SELECT SUM(totalcompra-abono) INTO pagar FROM compras WHERE status =3;
        
        -- FIX: Use the specific caja_id instead of status=1 to prevent multiple rows error
        SELECT inicio INTO inicios FROM caja WHERE id = caja_id;
        
        SELECT usuarios,clientes,proveedores,productos,ventas,abonos,pagos,compra,cobrar,pagar,egreso,credito,inicios;
    END$$

DELIMITER ;
