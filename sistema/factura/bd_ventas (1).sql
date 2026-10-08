-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-12-2025 a las 20:42:24
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ventas_bd`
--
CREATE DATABASE IF NOT EXISTS `ventas_bd` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ventas_bd`;

DELIMITER $$
--
-- Procedimientos
--
DROP PROCEDURE IF EXISTS `actualizar_precio_producto`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_precio_producto` (IN `n_cantidad` INT, IN `n_precio` DECIMAL(10,2), IN `codigo` INT)   BEGIN
    	DECLARE nueva_existencia int;
        DECLARE nuevo_total decimal(10,2);
        DECLARE nuevo_precio decimal(10,2);
        
        DECLARE cant_actual int;
        DECLARE pre_actual decimal(10,2);
        
        DECLARE actual_existencia int;
        DECLARE actual_precio decimal(10,2);
        
        SELECT precio,existencia INTO actual_precio,actual_existencia FROM producto WHERE codproducto = codigo;
        SET nueva_existencia = actual_existencia + n_cantidad;
        
        UPDATE producto SET existencia = nueva_existencia, precio = n_precio WHERE codproducto = codigo;
        
        SELECT nueva_existencia,nuevo_precio;
        
     END$$

DROP PROCEDURE IF EXISTS `add_detalle_temp`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_detalle_temp` (IN `codigo` INT, IN `cantidad` INT, IN `token_user` VARCHAR(50))   BEGIN
    
    	DECLARE precio_actual decimal(10,2);
        DECLARE costo_actual decimal(10,2);
        DECLARE existencia_actual int;
        DECLARE nueva_existencia int;
        
SELECT costo,precio INTO costo_actual,precio_actual FROM producto WHERE codproducto = codigo;
        
        INSERT INTO detalle_temp(token_user,codproducto,cantidad,costo,precio_venta) VALUES(token_user,codigo,cantidad,costo_actual,precio_actual);
        
        SELECT existencia INTO existencia_actual FROM producto WHERE codproducto = codigo;

                SET nueva_existencia = existencia_actual - cantidad;
                UPDATE producto SET existencia = nueva_existencia WHERE codproducto = codigo;
        
        SELECT tmp.correlativo, tmp.codproducto,p.descripcion,tmp.cantidad,tmp.precio_venta FROM detalle_temp tmp
        INNER JOIN producto p 
        ON tmp.codproducto = p.codproducto
        WHERE tmp.token_user = token_user;
    END$$

DROP PROCEDURE IF EXISTS `add_detalle_temp_compra`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_detalle_temp_compra` (IN `codigo` INT, IN `cantidad` INT, IN `token_user` VARCHAR(50), IN `costo` DECIMAL(10,2))   BEGIN 
	    DECLARE precio_actual decimal(10,2);
        DECLARE existencia_actual int;
        DECLARE nueva_existencia int;
        
        INSERT INTO detalle_temp_compra(token_user,codproducto,cantidad,precio_venta) VALUES(token_user,codigo,cantidad,costo);
        
        SELECT tmp.correlativo, tmp.codproducto,p.descripcion,tmp.cantidad,tmp.precio_venta FROM detalle_temp_compra tmp
        INNER JOIN producto p 
        ON tmp.codproducto = p.codproducto
        WHERE tmp.token_user = token_user;
END$$

DROP PROCEDURE IF EXISTS `anular_compra`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `anular_compra` (IN `no_venta` INT)   BEGIN
	DECLARE existe_venta int;
        DECLARE registros int;
        DECLARE a int;
        
        DECLARE cod_producto int;
        DECLARE cant_producto int;
        DECLARE existencia_actual int;
        DECLARE nueva_existencia int;
        
        SET existe_venta = (SELECT COUNT(*) FROM compras WHERE nocompra = no_venta and status != 2);
        
        IF existe_venta > 0 THEN
        	CREATE TEMPORARY TABLE tbl_tmp (
                id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                cod_prod BIGINT,
                cant_prod int);
                
                SET a = 1;
                
                SET registros = (SELECT COUNT(*)FROM entradas WHERE nocompra = no_venta);
                
                IF registros > 0 THEN
                	INSERT INTO tbl_tmp(cod_prod,cant_prod) SELECT codproducto,cantidad FROM entradas WHERE nocompra = no_venta;
                    
                    WHILE a <= registros DO
                    	SELECT cod_prod,cant_prod INTO cod_producto,cant_producto FROM tbl_tmp WHERE id = a;
                        SELECT existencia INTO existencia_actual FROM producto WHERE codproducto = cod_producto;
                        SET nueva_existencia = existencia_actual - cant_producto;
                        UPDATE producto SET existencia = nueva_existencia WHERE codproducto = cod_producto;
                        
                        SET a=a+1;
                    
                    END WHILE;
                    UPDATE compras SET status = 2 WHERE nocompra = no_venta;
                    DROP TABLE tbl_tmp;
                    SELECT * FROM compras WHERE nocompra = no_venta;
                
                END IF;
        
        ELSE
        	SELECT 0 compras;
        END IF;
    
END$$

DROP PROCEDURE IF EXISTS `anular_venta`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `anular_venta` (IN `no_venta` INT)   BEGIN
    	DECLARE existe_venta int;
        DECLARE registros int;
        DECLARE a int;
        
        DECLARE cod_producto int;
        DECLARE cant_producto int;
        DECLARE existencia_actual int;
        DECLARE nueva_existencia int;
        
        SET existe_venta = (SELECT COUNT(*) FROM venta WHERE noventa = no_venta and status != 2);
        
        IF existe_venta > 0 THEN
        	CREATE TEMPORARY TABLE tbl_tmp (
                id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                cod_prod BIGINT,
                cant_prod int);
                
                SET a = 1;
                
                SET registros = (SELECT COUNT(*)FROM detalleventa WHERE noventa = no_venta);
                
                IF registros > 0 THEN
                	INSERT INTO tbl_tmp(cod_prod,cant_prod) SELECT codproducto,cantidad FROM detalleventa WHERE noventa = no_venta;
                    
                    WHILE a <= registros DO
                    	SELECT cod_prod,cant_prod INTO cod_producto,cant_producto FROM tbl_tmp WHERE id = a;
                        SELECT existencia INTO existencia_actual FROM producto WHERE codproducto = cod_producto;
                        SET nueva_existencia = existencia_actual + cant_producto;
                        UPDATE producto SET existencia = nueva_existencia WHERE codproducto = cod_producto;
           UPDATE detalleventa SET status = 2 WHERE noventa = no_venta;
                        
                        SET a=a+1;
                    
                    END WHILE;
                    UPDATE venta SET status = 2 WHERE noventa = no_venta;
                    DROP TABLE tbl_tmp;
                    SELECT * FROM venta WHERE noventa = no_venta;
                
                END IF;
        
        ELSE
        	SELECT 0 venta;
        END IF;
    
    END$$

DROP PROCEDURE IF EXISTS `cancelar_compra`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `cancelar_compra` (IN `token` INT)   BEGIN
    	DECLARE existe_venta int;
        DECLARE registros int;
        DECLARE a int;
        
        DECLARE cod_producto int; 
        DECLARE cant_producto int;
        DECLARE existencia_actual int;
        DECLARE nueva_existencia int;
        
        SET existe_venta = (SELECT COUNT(*) FROM detalle_temp_compra WHERE token_user = token);
        
        IF existe_venta > 0 THEN
        	CREATE TEMPORARY TABLE tbl_tmp (
                id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                cod_prod BIGINT,
                cant_prod int);
                
                SET a = 1;
                
                SET registros = (SELECT COUNT(*)FROM detalle_temp_compra WHERE token_user = token);
                
                IF registros > 0 THEN
                	INSERT INTO tbl_tmp(cod_prod,cant_prod) SELECT codproducto,cantidad FROM detalle_temp_compra WHERE token_user = token;
                    
                    WHILE a <= registros DO
                    	SELECT cod_prod,cant_prod INTO cod_producto,cant_producto FROM tbl_tmp WHERE id = a;
                        SELECT existencia INTO existencia_actual FROM producto WHERE codproducto = cod_producto;
                        SET nueva_existencia = existencia_actual + cant_producto;
                        UPDATE producto SET existencia = nueva_existencia WHERE codproducto = cod_producto;
                        
                        SET a=a+1;
                    
                    END WHILE;
       DELETE FROM detalle_temp_compra WHERE token_user = token;
              DROP TABLE tbl_tmp;
       SELECT * FROM detalle_temp_compra WHERE token_user = token;
                
                END IF;
        
        ELSE
        	SELECT 0 detalle_temp_compra;
        END IF;
    
    END$$

DROP PROCEDURE IF EXISTS `cancelar_venta`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `cancelar_venta` (IN `token` INT)   BEGIN
    	DECLARE existe_venta int;
        DECLARE registros int;
        DECLARE a int;
        
        DECLARE cod_producto int; 
        DECLARE cant_producto int;
        DECLARE existencia_actual int;
        DECLARE nueva_existencia int;
        
        SET existe_venta = (SELECT COUNT(*) FROM detalle_temp WHERE token_user = token);
        
        IF existe_venta > 0 THEN
        	CREATE TEMPORARY TABLE tbl_tmp (
                id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                cod_prod BIGINT,
                cant_prod int);
                
                SET a = 1;
                
                SET registros = (SELECT COUNT(*)FROM detalle_temp WHERE token_user = token);
                
                IF registros > 0 THEN
                	INSERT INTO tbl_tmp(cod_prod,cant_prod) SELECT codproducto,cantidad FROM detalle_temp WHERE token_user = token;
                    
                    WHILE a <= registros DO
                    	SELECT cod_prod,cant_prod INTO cod_producto,cant_producto FROM tbl_tmp WHERE id = a;
                        SELECT existencia INTO existencia_actual FROM producto WHERE codproducto = cod_producto;
                        SET nueva_existencia = existencia_actual + cant_producto;
                        UPDATE producto SET existencia = nueva_existencia WHERE codproducto = cod_producto;
                        
                        SET a=a+1;
                    
                    END WHILE;
       DELETE FROM detalle_temp WHERE token_user = token;
              DROP TABLE tbl_tmp;
       SELECT * FROM detalle_temp WHERE token_user = token;
                
                END IF;
        
        ELSE
        	SELECT 0 detalle_temp;
        END IF;
    
    END$$

DROP PROCEDURE IF EXISTS `dataDashboard`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `dataDashboard` (IN `caja_id` INT)   BEGIN
    	
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
        SELECT inicio INTO inicios FROM caja WHERE status =1;
        
        SELECT usuarios,clientes,proveedores,productos,ventas,abonos,pagos,compra,cobrar,pagar,egreso,credito,inicios;
    END$$

DROP PROCEDURE IF EXISTS `del_detalle_temp`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `del_detalle_temp` (IN `id_detalle` INT, IN `token` VARCHAR(50))   BEGIN    
		DECLARE existe_venta int;
        DECLARE registros int;
        DECLARE a int;
        
        DECLARE cod_producto int;
        DECLARE cant_producto int;
        DECLARE existencia_actual int;
        DECLARE nueva_existencia int;
        

        	CREATE TEMPORARY TABLE tbl_tmp (id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                cod_prod BIGINT,
                cant_prod int);
                
                SET a = 1;
                
                SET registros = (SELECT COUNT(*)FROM detalle_temp WHERE correlativo = id_detalle);
                

                	INSERT INTO tbl_tmp(cod_prod,cant_prod) SELECT codproducto,cantidad FROM detalle_temp WHERE correlativo = id_detalle;
                    
                    	SELECT cod_prod,cant_prod INTO cod_producto,cant_producto FROM tbl_tmp WHERE id = a;
                        SELECT existencia INTO existencia_actual FROM producto WHERE codproducto = cod_producto;
                        SET nueva_existencia = existencia_actual + cant_producto;
                        UPDATE producto SET existencia = nueva_existencia WHERE codproducto = cod_producto;

            DELETE FROM detalle_temp WHERE correlativo = id_detalle;
  
            SELECT tmp.correlativo, tmp.codproducto,p.descripcion,tmp.cantidad,tmp.precio_venta FROM detalle_temp tmp
            INNER JOIN producto p 
            ON tmp.codproducto = p.codproducto
            WHERE tmp.token_user = token;
        END$$

DROP PROCEDURE IF EXISTS `del_detalle_temp_compra`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `del_detalle_temp_compra` (IN `p_correlativo` INT, IN `p_token` VARCHAR(50))   BEGIN
    -- Eliminar el producto del detalle
    DELETE FROM detalle_temp_compra 
    WHERE correlativo = p_correlativo 
      AND token_user = p_token;
    
    -- Retornar detalle actualizado
    SELECT 
        tmp.correlativo,
        tmp.codproducto,
        p.descripcion,
        tmp.cantidad,
        tmp.precio_venta
    FROM detalle_temp_compra tmp
    INNER JOIN producto p ON tmp.codproducto = p.codproducto
    WHERE tmp.token_user = p_token;
END$$

DROP PROCEDURE IF EXISTS `procesar_compra`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `procesar_compra` (IN `usuario` INT, IN `codproveedor` INT, IN `token` VARCHAR(50), IN `tipo_pago` INT, IN `id_caja` INT)   BEGIN
    DECLARE nocompra INT;
    DECLARE total DECIMAL(10,2) DEFAULT 0;
    
    -- Calcular total
    SELECT IFNULL(SUM(cantidad * precio_venta), 0) INTO total 
    FROM detalle_temp_compra 
    WHERE token_user = token;
    
    -- Insertar compra
    INSERT INTO compras(usuario, codproveedor, totalcompra, status, caja)
    VALUES(usuario, codproveedor, total, 1, id_caja);
    
    SET nocompra = LAST_INSERT_ID();
    
    -- Insertar entradas con fecha de vencimiento
    INSERT INTO entradas(nocompra, codproducto, cantidad, precio, usuario_id, fecha_vencimiento)
    SELECT nocompra, codproducto, cantidad, precio_venta, usuario, fecha_vencimiento
    FROM detalle_temp_compra
    WHERE token_user = token;
    
    -- Actualizar stock
    UPDATE producto p
    INNER JOIN detalle_temp_compra dt ON p.codproducto = dt.codproducto
    SET p.existencia = p.existencia + dt.cantidad
    WHERE dt.token_user = token;
    
    -- Limpiar temporal
    DELETE FROM detalle_temp_compra WHERE token_user = token;
    
    -- Retornar resultado
    SELECT nocompra;
END$$

DROP PROCEDURE IF EXISTS `procesar_venta`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `procesar_venta` (IN `cod_usuario` INT, IN `cod_cliente` INT, IN `token` VARCHAR(50), IN `tipo_pago` INT, IN `id_caja` INT, IN `descuento` INT)   BEGIN
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
            
            INSERT INTO venta(usuario,caja,codcliente,status,descuento) VALUES(cod_usuario,id_caja,cod_cliente,tipo_pago,descuento);
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
    END$$

--
-- Funciones
--
DROP FUNCTION IF EXISTS `fn_stock_disponible_fefo`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_stock_disponible_fefo` (`p_codproducto` INT) RETURNS INT(11) DETERMINISTIC BEGIN
    DECLARE v_stock INT;
    
    SELECT COALESCE(SUM(cantidad_actual), 0) INTO v_stock
    FROM lotes
    WHERE codproducto = p_codproducto
      AND status = 1
      AND cantidad_actual > 0
      AND fecha_vencimiento > CURDATE();
    
    RETURN v_stock;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bancos`
--

DROP TABLE IF EXISTS `bancos`;
CREATE TABLE `bancos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `numero_cuenta` varchar(50) DEFAULT NULL,
  `tipo_cuenta` varchar(50) DEFAULT NULL,
  `titular` varchar(100) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `date_add` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `bancos`
--

INSERT INTO `bancos` (`id`, `nombre`, `numero_cuenta`, `tipo_cuenta`, `titular`, `status`, `date_add`) VALUES
(1, 'Banco Continental', '1234567890', 'Cuenta Corriente', 'MI EMPRESA S.A.', 1, '2025-10-17 20:03:41'),
(2, 'Itaú Paraguay', '0987654321', 'Caja de Ahorro', 'MI EMPRESA S.A.', 1, '2025-10-17 20:03:41'),
(3, 'Banco Regional', '1122334455', 'Cuenta Corriente', 'MI EMPRESA S.A.', 1, '2025-10-17 20:03:41'),
(4, 'Banco Nacional de Fomento', '5544332211', 'Caja de Ahorro', 'MI EMPRESA S.A.', 1, '2025-10-17 20:03:41'),
(5, 'Banco Familiar', '6677889900', 'Cuenta Corriente', 'MI EMPRESA S.A.', 1, '2025-10-17 20:03:41'),
(6, 'Banco Continental', '1234567890', 'Cuenta Corriente', 'MI EMPRESA S.A.', 1, '2025-10-17 20:15:36'),
(7, 'Itaú Paraguay', '0987654321', 'Caja de Ahorro', 'MI EMPRESA S.A.', 1, '2025-10-17 20:15:36'),
(8, 'Banco Regional', '1122334455', 'Cuenta Corriente', 'MI EMPRESA S.A.', 1, '2025-10-17 20:15:36'),
(9, 'Banco Nacional de Fomento', '5544332211', 'Caja de Ahorro', 'MI EMPRESA S.A.', 1, '2025-10-17 20:15:36'),
(10, 'Banco Familiar', '6677889900', 'Cuenta Corriente', 'MI EMPRESA S.A.', 1, '2025-10-17 20:15:36'),
(11, 'Banco Continental', '1234567890', 'Cuenta Corriente', 'Empresa S.A.', 1, '2025-10-17 20:16:17'),
(12, 'Banco Mercantil', '0987654321', 'Caja de Ahorro', 'Empresa S.A.', 1, '2025-10-17 20:16:17'),
(13, 'Banco Bisa', '5555666677', 'Cuenta Corriente', 'Empresa S.A.', 1, '2025-10-17 20:16:17'),
(14, 'Banco Continental', '', 'Cuenta Corriente', '', 1, '2025-11-16 21:38:38'),
(15, 'Banco Nacional de Fomento', '', 'Cuenta Corriente', '', 1, '2025-11-16 21:38:38'),
(16, 'Banco Regional', '', 'Caja de Ahorro', '', 1, '2025-11-16 21:38:38'),
(17, 'Itaú', '', 'Cuenta Corriente', '', 1, '2025-11-16 21:38:38'),
(18, 'Visión Banco', '', 'Cuenta Corriente', '', 1, '2025-11-16 21:38:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja`
--

DROP TABLE IF EXISTS `caja`;
CREATE TABLE `caja` (
  `id` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `inicio` decimal(10,2) DEFAULT NULL,
  `ventas` decimal(10,2) DEFAULT NULL,
  `abonos` decimal(10,2) DEFAULT NULL,
  `egresos` decimal(10,2) DEFAULT NULL,
  `creditos` decimal(10,2) DEFAULT NULL,
  `total_efectivo` decimal(10,2) DEFAULT NULL,
  `usuario` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `caja`
--

INSERT INTO `caja` (`id`, `fecha`, `inicio`, `ventas`, `abonos`, `egresos`, `creditos`, `total_efectivo`, `usuario`, `status`) VALUES
(161, '2025-10-17 19:39:48', 1000.00, NULL, NULL, NULL, NULL, NULL, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

DROP TABLE IF EXISTS `cliente`;
CREATE TABLE `cliente` (
  `idcliente` int(11) NOT NULL,
  `nit` varchar(20) DEFAULT NULL,
  `nombre` varchar(80) DEFAULT NULL,
  `telefono` int(11) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`idcliente`, `nit`, `nombre`, `telefono`, `direccion`, `date_add`, `usuario_id`, `status`) VALUES
(1, '6031807920003k', 'Cliente regular', 88888888, 'EL RAMA', '2021-12-05 15:47:39', 1, 1),
(74, '65464', 'Juan Perez', 985254136, 'Asuncion', '2025-10-22 13:20:46', 1, 1),
(75, '5389756', 'Maria Martinez', 985254123, 'Barrio 12 de junio', '2025-11-11 14:43:55', 39, 1),
(76, '456789', 'rtfgyhjukl', 2147483647, 'fghjkl', '2025-11-17 15:15:42', 1, 1),
(77, '4744875', 'Fany Acuña', 975325002, 'Barrio Universitario', '2025-11-17 15:33:20', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras`
--

DROP TABLE IF EXISTS `compras`;
CREATE TABLE `compras` (
  `nocompra` bigint(20) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `usuario` int(11) NOT NULL,
  `caja` int(11) NOT NULL,
  `codproveedor` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `totalcompra` decimal(10,2) NOT NULL,
  `abono` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `compras`
--

INSERT INTO `compras` (`nocompra`, `fecha`, `usuario`, `caja`, `codproveedor`, `status`, `totalcompra`, `abono`) VALUES
(152, '2025-10-22 14:42:31', 39, 161, 52, 1, 157500.00, 0.00),
(153, '2025-11-11 14:43:02', 39, 161, 1, 1, 127500.00, 0.00),
(156, '2025-11-17 21:26:04', 1, 161, 1, 1, 62000.00, 0.00),
(157, '2025-11-17 21:45:55', 1, 161, 1, 1, 150000.00, 0.00),
(158, '2025-11-17 21:48:47', 1, 161, 1, 1, 110000.00, 0.00),
(159, '2025-11-17 21:56:08', 1, 161, 1, 1, 1000.00, 0.00),
(160, '2025-11-17 21:59:57', 1, 161, 1, 1, 60000.00, 0.00),
(161, '2025-11-17 22:00:32', 1, 161, 1, 1, 50000.00, 0.00),
(162, '2025-11-17 22:01:19', 1, 161, 1, 1, 50000.00, 0.00),
(163, '2025-11-18 11:51:19', 1, 161, 1, 1, 30000.00, 0.00),
(164, '2025-11-20 21:54:13', 1, 161, 1, 1, 16000.00, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE `configuracion` (
  `id` bigint(20) NOT NULL,
  `nit` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `razon_social` varchar(100) NOT NULL,
  `telefono` bigint(20) NOT NULL,
  `email` varchar(200) NOT NULL,
  `direccion` text NOT NULL,
  `iva` decimal(10,2) NOT NULL,
  `foto` varchar(200) NOT NULL,
  `moneda` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `nit`, `nombre`, `razon_social`, `telefono`, `email`, `direccion`, `iva`, `foto`, `moneda`) VALUES
(1, '4578852', 'Tu empresa', 'Software y tecnología', 965214365, 'tuempresa@gmail.com', 'Coronel Oviedo-Paraguay', 0.00, ' ', 'Gs');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalleventa`
--

DROP TABLE IF EXISTS `detalleventa`;
CREATE TABLE `detalleventa` (
  `correlativo` bigint(20) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `noventa` bigint(20) DEFAULT NULL,
  `codproducto` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `costo` decimal(10,2) NOT NULL,
  `precio_venta` decimal(10,2) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `id_lote` int(11) DEFAULT NULL COMMENT 'Lote del que salió el producto'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `detalleventa`
--

INSERT INTO `detalleventa` (`correlativo`, `fecha`, `noventa`, `codproducto`, `cantidad`, `costo`, `precio_venta`, `status`, `id_lote`) VALUES
(1543, '2025-10-17 19:39:59', 1188, 1326, 1, 207.00, 235.00, 1, NULL),
(1544, '2025-10-17 20:34:17', 1189, 1326, 1, 207.00, 235.00, 1, NULL),
(1545, '2025-10-17 20:34:17', 1189, 1326, 1, 207.00, 235.00, 1, NULL),
(1546, '2025-10-17 20:34:17', 1189, 1326, 1, 207.00, 235.00, 1, NULL),
(1547, '2025-10-17 20:49:35', 1190, 1326, 1, 207.00, 235.00, 1, NULL),
(1548, '2025-10-17 21:04:11', 1191, 1326, 1, 207.00, 235.00, 1, NULL),
(1549, '2025-10-17 21:10:00', 1192, 1326, 1, 207.00, 235.00, 1, NULL),
(1550, '2025-10-18 09:10:58', 1193, 1326, 1, 207.00, 235.00, 1, NULL),
(1551, '2025-10-18 09:10:58', 1193, 1326, 1, 207.00, 235.00, 1, NULL),
(1552, '2025-10-18 09:10:58', 1193, 1327, 1, 193.50, 220.00, 1, NULL),
(1553, '2025-10-18 09:15:34', 1194, 2012, 1, 110.00, 135.00, 1, NULL),
(1554, '2025-10-18 09:15:34', 1194, 1555, 1, 237.46, 260.00, 1, NULL),
(1555, '2025-10-18 09:15:34', 1194, 1325, 1, 247.50, 280.00, 1, NULL),
(1556, '2025-10-18 09:15:34', 1194, 1553, 1, 123.35, 155.00, 1, NULL),
(1557, '2025-10-18 09:15:34', 1194, 1560, 1, 189.29, 230.00, 1, NULL),
(1558, '2025-10-18 09:15:34', 1194, 1558, 1, 53.69, 65.00, 1, NULL),
(1559, '2025-10-18 09:15:34', 1194, 2011, 1, 305.00, 360.00, 1, NULL),
(1560, '2025-10-18 09:15:34', 1194, 1552, 1, 155.00, 175.00, 1, NULL),
(1561, '2025-10-18 09:15:34', 1194, 1552, 1, 155.00, 175.00, 1, NULL),
(1562, '2025-10-18 09:15:34', 1194, 1557, 1, 145.00, 170.00, 1, NULL),
(1563, '2025-10-18 09:15:34', 1194, 1556, 1, 124.71, 165.00, 1, NULL),
(1564, '2025-10-18 09:15:34', 1194, 1326, 1, 207.00, 235.00, 1, NULL),
(1568, '2025-10-18 09:16:50', 1195, 2012, 1, 110.00, 135.00, 1, NULL),
(1569, '2025-10-18 09:16:50', 1195, 1326, 1, 207.00, 235.00, 1, NULL),
(1570, '2025-10-18 09:16:50', 1195, 1327, 1, 193.50, 220.00, 1, NULL),
(1571, '2025-10-18 09:16:50', 1195, 1325, 1, 247.50, 280.00, 1, NULL),
(1572, '2025-10-18 09:16:50', 1195, 1555, 1, 237.46, 260.00, 1, NULL),
(1573, '2025-10-18 09:16:50', 1195, 1553, 1, 123.35, 155.00, 1, NULL),
(1574, '2025-10-18 09:16:50', 1195, 1560, 1, 189.29, 230.00, 1, NULL),
(1575, '2025-10-18 09:16:50', 1195, 2011, 1, 305.00, 360.00, 1, NULL),
(1576, '2025-10-18 09:16:50', 1195, 1558, 1, 53.69, 65.00, 1, NULL),
(1583, '2025-10-18 09:43:16', 1196, 1555, 1, 237.46, 260.00, 1, NULL),
(1584, '2025-10-18 09:44:27', 1197, 1555, 1, 237.46, 260.00, 1, NULL),
(1585, '2025-10-18 09:44:27', 1197, 1555, 1, 237.46, 260.00, 1, NULL),
(1586, '2025-10-18 09:44:27', 1197, 1560, 1, 189.29, 230.00, 1, NULL),
(1587, '2025-10-18 09:44:27', 1197, 1555, 1, 237.46, 260.00, 1, NULL),
(1588, '2025-10-18 09:44:27', 1197, 1325, 1, 247.50, 280.00, 1, NULL),
(1589, '2025-10-18 09:44:27', 1197, 1325, 1, 247.50, 280.00, 1, NULL),
(1590, '2025-10-20 14:37:01', 1198, 1326, 1, 207.00, 235.00, 1, NULL),
(1591, '2025-10-20 14:37:01', 1198, 1326, 1, 207.00, 235.00, 1, NULL),
(1592, '2025-10-20 14:37:01', 1198, 1553, 1, 123.35, 155.00, 1, NULL),
(1593, '2025-10-20 14:37:01', 1198, 1327, 1, 193.50, 220.00, 1, NULL),
(1594, '2025-10-20 14:37:01', 1198, 1326, 1, 207.00, 235.00, 1, NULL),
(1597, '2025-10-20 15:25:43', 1199, 1326, 1, 207.00, 235.00, 1, NULL),
(1599, '2025-10-22 13:22:11', 1202, 1553, 1, 123.35, 155.00, 1, NULL),
(1600, '2025-10-22 13:23:01', 1203, 1326, 1, 207.00, 235.00, 1, NULL),
(1601, '2025-10-22 13:23:25', 1204, 1555, 1, 237.46, 260.00, 1, NULL),
(1602, '2025-11-11 14:45:11', 1205, 2090, 3, 8500.00, 10000.00, 1, NULL),
(1603, '2025-11-11 14:45:11', 1205, 2088, 1, 2000.00, 3000.00, 1, NULL),
(1605, '2025-11-11 23:54:02', 1206, 2090, 1, 8500.00, 10000.00, 1, NULL),
(1606, '2025-11-12 00:02:21', 1207, 1553, 1, 4500.00, 5000.00, 1, NULL),
(1607, '2025-11-12 00:03:32', 1208, 1553, 1, 4500.00, 5000.00, 1, NULL),
(1608, '2025-11-12 00:22:12', 1209, 1553, 1, 4500.00, 5000.00, 1, NULL),
(1609, '2025-11-12 00:24:22', 1210, 1553, 1, 4500.00, 5000.00, 1, NULL),
(1610, '2025-11-12 00:46:30', 1211, 1553, 1, 4500.00, 5000.00, 1, NULL),
(1611, '2025-11-12 00:46:52', 1212, 1553, 1, 4500.00, 5000.00, 1, NULL),
(1612, '2025-11-12 00:52:41', 1213, 1553, 1, 4500.00, 5000.00, 1, NULL),
(1613, '2025-11-12 18:44:47', 1214, 1555, 1, 237.46, 260.00, 1, NULL),
(1614, '2025-11-17 19:24:16', 1215, 1552, 1, 3120.00, 7000.00, 1, NULL),
(1615, '2025-11-17 19:24:16', 1215, 2090, 1, 8500.00, 10000.00, 1, NULL),
(1617, '2025-11-17 19:33:37', 1216, 2012, 1, 1000.00, 1500.00, 1, NULL),
(1618, '2025-11-17 19:33:37', 1216, 1554, 1, 960.74, 1080.00, 1, NULL),
(1620, '2025-11-17 19:36:45', 1217, 2089, 1, 2000.00, 2500.00, 1, NULL),
(1621, '2025-11-17 19:39:29', 1218, 2012, 1, 1000.00, 1500.00, 1, NULL),
(1622, '2025-11-17 19:39:29', 1218, 2012, 5, 1000.00, 1500.00, 1, NULL),
(1623, '2025-11-18 09:00:51', 1219, 1554, 1, 960.74, 1080.00, 1, NULL),
(1624, '2025-12-25 11:45:38', 1220, 2012, 1, 1000.00, 1500.00, 1, NULL),
(1625, '2025-12-25 11:45:38', 1220, 2087, 1, 2300.00, 3500.00, 1, NULL),
(1626, '2025-12-25 11:45:38', 1220, 1554, 3, 960.74, 1080.00, 1, NULL),
(1627, '2025-12-25 13:08:58', 1221, 1554, 2, 960.74, 1080.00, 1, NULL),
(1628, '2025-12-25 13:08:58', 1221, 1554, 21, 960.74, 1080.00, 1, NULL),
(1629, '2025-12-25 13:08:58', 1221, 2087, 2, 2300.00, 3500.00, 1, NULL),
(1630, '2025-12-25 13:08:58', 1221, 2012, 1, 1000.00, 1500.00, 1, NULL),
(1631, '2025-12-25 13:08:58', 1221, 1554, 1, 960.74, 1080.00, 1, NULL),
(1632, '2025-12-25 13:08:58', 1221, 1554, 1, 960.74, 1080.00, 1, NULL),
(1633, '2025-12-25 13:08:58', 1221, 2087, 2, 2300.00, 3500.00, 1, NULL),
(1634, '2025-12-25 13:08:58', 1221, 2012, 1, 1000.00, 1500.00, 1, NULL),
(1635, '2025-12-25 13:08:58', 1221, 1554, 1, 960.74, 1080.00, 1, NULL),
(1642, '2025-12-25 13:49:25', 1222, 2089, 19, 2000.00, 2500.00, 1, NULL),
(1643, '2025-12-25 13:56:19', 1223, 1554, 5, 960.74, 1080.00, 1, NULL),
(1644, '2025-12-25 16:39:23', 1224, 1554, 1, 960.74, 1080.00, 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_recibo`
--

DROP TABLE IF EXISTS `detalle_recibo`;
CREATE TABLE `detalle_recibo` (
  `id` int(11) NOT NULL,
  `noventa` bigint(20) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `saldo_anterior` decimal(10,2) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `saldo_actual` decimal(10,2) NOT NULL,
  `usuario` int(11) NOT NULL,
  `caja` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_recibo_compra`
--

DROP TABLE IF EXISTS `detalle_recibo_compra`;
CREATE TABLE `detalle_recibo_compra` (
  `id` int(11) NOT NULL,
  `nocompra` bigint(20) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `saldo_anterior` decimal(10,2) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `saldo_actual` decimal(10,2) NOT NULL,
  `usuario` int(11) NOT NULL,
  `caja` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_temp`
--

DROP TABLE IF EXISTS `detalle_temp`;
CREATE TABLE `detalle_temp` (
  `correlativo` int(11) NOT NULL,
  `token_user` varchar(50) NOT NULL,
  `codproducto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `costo` decimal(10,2) NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_temp_compra`
--

DROP TABLE IF EXISTS `detalle_temp_compra`;
CREATE TABLE `detalle_temp_compra` (
  `correlativo` int(11) NOT NULL,
  `token_user` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `codproducto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `numero_lote` varchar(100) DEFAULT NULL COMMENT 'Número de lote temporal',
  `fecha_vencimiento` date DEFAULT NULL COMMENT 'Fecha de vencimiento temporal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_temp_compra`
--

INSERT INTO `detalle_temp_compra` (`correlativo`, `token_user`, `codproducto`, `cantidad`, `precio_venta`, `numero_lote`, `fecha_vencimiento`) VALUES
(166, 'test123', 1, 10, 5000.00, NULL, '2025-12-31'),
(167, 'test123', 1, 10, 5000.00, NULL, '2025-12-31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `egresos`
--

DROP TABLE IF EXISTS `egresos`;
CREATE TABLE `egresos` (
  `id` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `descripcion` text NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `usuario` int(11) NOT NULL,
  `caja` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entradas`
--

DROP TABLE IF EXISTS `entradas`;
CREATE TABLE `entradas` (
  `correlativo` bigint(20) NOT NULL,
  `nocompra` bigint(20) NOT NULL,
  `codproducto` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `cantidad` int(11) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `numero_lote` varchar(100) DEFAULT NULL COMMENT 'Número de lote',
  `fecha_vencimiento` date DEFAULT NULL COMMENT 'Fecha de vencimiento'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `entradas`
--

INSERT INTO `entradas` (`correlativo`, `nocompra`, `codproducto`, `fecha`, `cantidad`, `precio`, `usuario_id`, `numero_lote`, `fecha_vencimiento`) VALUES
(897, 152, 2088, '2025-10-22 14:42:31', 15, 4500.00, 0, NULL, NULL),
(898, 152, 2089, '2025-10-22 14:42:31', 20, 4500.00, 0, NULL, NULL),
(899, 153, 2090, '2025-11-11 14:43:02', 15, 8500.00, 0, NULL, NULL),
(900, 156, 2012, '2025-11-17 18:26:04', 1, 1000.00, 1, NULL, '2026-11-17'),
(901, 156, 2012, '2025-11-17 18:26:04', 1, 1000.00, 1, NULL, '2028-11-12'),
(902, 156, 1552, '2025-11-17 18:26:04', 15, 4000.00, 1, NULL, '2028-11-17'),
(903, 157, 1554, '2025-11-17 18:45:55', 15, 10000.00, 1, NULL, '2025-11-27'),
(904, 158, 1554, '2025-11-17 18:48:47', 10, 11000.00, 1, NULL, '2025-11-30'),
(905, 159, 2012, '2025-11-17 18:56:08', 1, 1000.00, 1, NULL, '2025-12-25'),
(906, 160, 1554, '2025-11-17 18:59:57', 12, 5000.00, 1, NULL, '2025-11-25'),
(907, 161, 2012, '2025-11-17 19:00:32', 10, 5000.00, 1, NULL, '2025-11-24'),
(908, 162, 1555, '2025-11-17 19:01:19', 10, 5000.00, 1, NULL, '2025-12-17'),
(909, 163, 2087, '2025-11-18 08:51:19', 15, 2000.00, 1, NULL, '2026-11-28'),
(910, 164, 2012, '2025-11-20 18:54:13', 16, 1000.00, 1, NULL, '2025-11-30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lotes`
--

DROP TABLE IF EXISTS `lotes`;
CREATE TABLE `lotes` (
  `id` int(11) NOT NULL,
  `codproducto` int(11) NOT NULL COMMENT 'FK a tabla producto',
  `numero_lote` varchar(100) NOT NULL COMMENT 'Identificador único del lote',
  `fecha_vencimiento` date NOT NULL COMMENT 'Fecha de expiración del lote',
  `cantidad_inicial` int(11) NOT NULL COMMENT 'Cantidad original al ingresar',
  `cantidad_actual` int(11) NOT NULL COMMENT 'Cantidad disponible actualmente',
  `precio_compra` decimal(10,2) DEFAULT 0.00 COMMENT 'Costo unitario de compra',
  `nocompra` int(11) DEFAULT NULL COMMENT 'FK a tabla compras (opcional)',
  `fecha_ingreso` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de registro',
  `status` int(11) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Gestión de lotes con fechas de vencimiento';

--
-- Disparadores `lotes`
--
DROP TRIGGER IF EXISTS `trg_log_movimiento_lote`;
DELIMITER $$
CREATE TRIGGER `trg_log_movimiento_lote` AFTER UPDATE ON `lotes` FOR EACH ROW BEGIN
    DECLARE v_diferencia INT;
    
    -- Calcular diferencia
    SET v_diferencia = NEW.cantidad_actual - OLD.cantidad_actual;
    
    -- Si hay cambio en cantidad, registrar movimiento
    IF v_diferencia != 0 THEN
        INSERT INTO movimientos_lotes(
            id_lote, 
            tipo_movimiento, 
            cantidad, 
            usuario_id, 
            observaciones
        ) VALUES (
            NEW.id,
            IF(v_diferencia > 0, 'entrada', 'salida'),
            ABS(v_diferencia),
            IFNULL(@current_user_id, 1),
            'Ajuste automático'
        );
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_validar_fecha_vencimiento`;
DELIMITER $$
CREATE TRIGGER `trg_validar_fecha_vencimiento` BEFORE INSERT ON `lotes` FOR EACH ROW BEGIN
    -- Validar que la fecha de vencimiento no sea pasada
    IF NEW.fecha_vencimiento <= CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de vencimiento debe ser mayor a la fecha actual';
    END IF;
    
    -- Validar cantidades positivas
    IF NEW.cantidad_inicial <= 0 OR NEW.cantidad_actual < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Las cantidades deben ser mayores a cero';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_lotes`
--

DROP TABLE IF EXISTS `movimientos_lotes`;
CREATE TABLE `movimientos_lotes` (
  `id` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL COMMENT 'FK a tabla lotes',
  `tipo_movimiento` enum('entrada','salida','ajuste') NOT NULL COMMENT 'Tipo de movimiento',
  `cantidad` int(11) NOT NULL COMMENT 'Cantidad del movimiento',
  `referencia_id` int(11) DEFAULT NULL COMMENT 'ID de venta/compra relacionada',
  `usuario_id` int(11) NOT NULL COMMENT 'Usuario que realizó el movimiento',
  `observaciones` text DEFAULT NULL COMMENT 'Notas adicionales',
  `fecha_movimiento` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha del movimiento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Historial de movimientos de lotes';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_qr`
--

DROP TABLE IF EXISTS `pagos_qr`;
CREATE TABLE `pagos_qr` (
  `id` int(11) NOT NULL,
  `noventa` int(11) NOT NULL,
  `tipo_qr` varchar(50) NOT NULL,
  `numero_referencia` varchar(100) NOT NULL,
  `numero_celular` varchar(20) DEFAULT NULL,
  `fecha_pago` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos_qr`
--

INSERT INTO `pagos_qr` (`id`, `noventa`, `tipo_qr`, `numero_referencia`, `numero_celular`, `fecha_pago`, `monto`, `observaciones`, `fecha_registro`) VALUES
(1, 1217, 'tigo_money', '450987654', '', '2025-11-17', 2580.00, '', '2025-11-17 22:36:45'),
(2, 1223, 'General', '47455', '', '2025-12-25', 0.00, '', '2025-12-25 16:56:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_tarjeta`
--

DROP TABLE IF EXISTS `pagos_tarjeta`;
CREATE TABLE `pagos_tarjeta` (
  `id` int(11) NOT NULL,
  `noventa` int(11) NOT NULL,
  `tipo_tarjeta` varchar(20) NOT NULL,
  `banco` varchar(100) NOT NULL,
  `ultimos_digitos` varchar(4) NOT NULL,
  `numero_autorizacion` varchar(50) DEFAULT NULL,
  `numero_lote` varchar(50) DEFAULT NULL,
  `fecha_pago` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos_tarjeta`
--

INSERT INTO `pagos_tarjeta` (`id`, `noventa`, `tipo_tarjeta`, `banco`, `ultimos_digitos`, `numero_autorizacion`, `numero_lote`, `fecha_pago`, `monto`, `observaciones`, `fecha_registro`) VALUES
(1, 1218, 'credito', 'Familiar', '5844', '', '', '2025-11-17', 10000.00, '\n', '2025-11-17 22:39:29'),
(2, 1224, 'General', 'General', '0000', '5467', '', '2025-12-25', 0.00, '', '2025-12-25 19:39:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

DROP TABLE IF EXISTS `producto`;
CREATE TABLE `producto` (
  `codproducto` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  `proveedor` int(11) DEFAULT NULL,
  `costo` decimal(10,2) NOT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `existencia` int(11) NOT NULL,
  `foto` text DEFAULT NULL,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  `status` int(11) DEFAULT 1,
  `usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`codproducto`, `codigo`, `descripcion`, `proveedor`, `costo`, `precio`, `existencia`, `foto`, `date_add`, `status`, `usuario_id`) VALUES
(1325, 'DTSI20W5012', 'ACEITE 20W50 1.2 LTS', 1, 247.50, 280.00, 6, 'img_378a975b453639d824d34353a99bacbe.jpg', '2022-01-13 20:23:17', 0, 1),
(1326, 'DTSI20W502R', 'ACEITE 20W50 1 LTS', 1, 207.00, 235.00, 0, 'img_dc399ccd8b13e81ce0326821c3b42ae9.jpg', '2022-01-13 20:24:46', 0, 1),
(1327, '024', 'toallita higienica', 1, 8000.00, 10000.00, 4, 'img_08b718c6b302e9e9462e8a1a4449bc3c.jpg', '2022-01-13 20:25:52', 1, 1),
(1552, '017', 'llavero capibara giratorio', 1, 3120.00, 7000.00, 21, 'img_ffd0cdeb3006a16c5073673d846a5a39.jpg', '2022-02-11 16:11:35', 1, 1),
(1553, '016', 'brigadeiro', 1, 4500.00, 5000.00, 4, 'img_ea10e057fb6c6c85d052180b61762702.jpg', '2022-02-11 16:17:41', 1, 1),
(1554, '15C9BF', 'ACEITE CASTROL CRB MAX 15W-40', 1, 960.74, 1080.00, 0, 'img_ec5afd5d4f512d8b6ccdf3838852fc29.jpg', '2022-02-11 16:19:04', 1, 1),
(1555, '15D9C1', 'ACEITE CASTROL ACTEVO 4T 20W-50', 1, 237.46, 260.00, 10, 'img_b2adf96d67c8e73f4023fb450ae7f656.jpg', '2022-02-11 16:20:17', 1, 1),
(1556, '010', 'morenita helado', 1, 3500.00, 4000.00, 14, 'img_7eab08200a0eac1198a184f47a5ed9eb.jpg', '2022-02-11 16:22:20', 1, 1),
(1557, '009', 'pañuelo desechable', 1, 2500.00, 3500.00, 10, 'img_9005a00d6bfcebe68ac554e4d4a1e7e4.jpg', '2022-02-11 16:23:36', 1, 1),
(1558, '008', 'saladix', 1, 2000.00, 3500.00, 9, 'img_2d3f21673b30a6dcbdd75e36865f23f1.jpg', '2022-02-11 16:24:59', 1, 1),
(1559, '007', 'del valle jugo', 1, 3300.00, 4500.00, 0, 'img_9bef084aa68a55d1b0476f89fab85543.jpg', '2022-02-11 16:25:43', 1, 1),
(1560, '006', 'tortuguita galletita', 1, 1500.00, 2000.00, 2, 'img_cc0f017a87be402fae0d8837ef3926d0.jpg', '2022-02-11 16:26:49', 1, 1),
(2011, '005', 'yogurt chico ', 1, 1200.00, 2000.00, 1, 'img_producto.png', '2022-07-08 18:54:38', 1, 1),
(2012, '004', 'Animados galletita', 1, 1000.00, 1500.00, 18, 'img_producto.png', '2022-07-08 18:55:49', 1, 1),
(2087, '003', 'chocolatada', 1, 2300.00, 3500.00, 10, 'img_producto.png', '2022-09-15 15:04:09', 1, 1),
(2088, '002', 'puro sol', 1, 2000.00, 3000.00, 14, 'img_producto.png', '2025-10-22 14:41:31', 1, 39),
(2089, '001', 'boligrafo', 52, 2000.00, 2500.00, 0, 'img_producto.png', '2025-10-22 14:41:55', 1, 39),
(2090, '45784', 'Frugos 1l', 52, 8500.00, 10000.00, 10, 'img_producto.png', '2025-11-11 14:42:49', 1, 39);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

DROP TABLE IF EXISTS `proveedor`;
CREATE TABLE `proveedor` (
  `codproveedor` int(11) NOT NULL,
  `proveedor` varchar(100) DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `telefono` bigint(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  `status` int(11) DEFAULT 1,
  `usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `proveedor`
--

INSERT INTO `proveedor` (`codproveedor`, `proveedor`, `contacto`, `telefono`, `direccion`, `date_add`, `status`, `usuario_id`) VALUES
(1, 'Mercado', 'Mercado', 99999999, 'Managua', '2021-09-21 15:52:42', 1, 1),
(52, 'Paresa', 'Joel ', 98525465, 'Asuncion', '2025-10-22 14:40:31', 1, NULL),
(53, 'Mia Secret', 'Juli', 985214412, 'Galeria Bonanza', '2025-11-17 14:19:48', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

DROP TABLE IF EXISTS `rol`;
CREATE TABLE `rol` (
  `idrol` int(11) NOT NULL,
  `rol` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`idrol`, `rol`) VALUES
(1, 'Administrador'),
(2, 'Supervisor'),
(3, 'Vendedor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transferencias`
--

DROP TABLE IF EXISTS `transferencias`;
CREATE TABLE `transferencias` (
  `id` int(11) NOT NULL,
  `noventa` bigint(20) NOT NULL,
  `banco_id` int(11) NOT NULL,
  `numero_referencia` varchar(100) NOT NULL,
  `fecha_transferencia` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `comprobante` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `date_add` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `transferencias`
--

INSERT INTO `transferencias` (`id`, `noventa`, `banco_id`, `numero_referencia`, `fecha_transferencia`, `monto`, `comprobante`, `observaciones`, `date_add`) VALUES
(1, 1189, 1, '34567890\'', '2025-10-17', 705.00, NULL, '', '2025-10-17 20:34:17'),
(2, 1190, 6, '34567890\'', '2025-10-17', 50000.00, NULL, '', '2025-10-17 20:49:35'),
(3, 1191, 13, '567890', '2025-10-18', 500000.00, NULL, '', '2025-10-17 21:04:11'),
(4, 1192, 1, '345678', '2025-10-18', 50000.00, NULL, '', '2025-10-17 21:10:00'),
(5, 1193, 10, '43567890\'', '2025-10-18', 50.00, NULL, '', '2025-10-18 09:10:58'),
(6, 1195, 6, '324567890', '2025-10-18', 1840.00, NULL, '', '2025-10-18 09:16:50'),
(7, 1196, 10, '32456789', '2025-10-18', 5000.00, NULL, '', '2025-10-18 09:43:16'),
(8, 1197, 5, '45678', '2025-10-18', 1570.00, NULL, '', '2025-10-18 09:44:27'),
(9, 1198, 11, '4567890\'', '2025-10-20', 1090.00, NULL, '', '2025-10-20 14:37:01'),
(10, 1204, 2, '354664', '2025-10-22', 150.00, NULL, '', '2025-10-22 13:23:25'),
(11, 1205, 6, '5464561', '2025-11-11', 33000.00, NULL, '', '2025-11-11 14:45:11'),
(12, 1213, 6, '3456789', '2025-11-12', 5000.00, NULL, '', '2025-11-12 00:52:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE `usuario` (
  `idusuario` int(11) NOT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `usuario` varchar(15) DEFAULT NULL,
  `clave` varchar(100) DEFAULT NULL,
  `rol` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`idusuario`, `nombre`, `correo`, `usuario`, `clave`, `rol`, `status`) VALUES
(1, 'Admin', 'admin@gmail.com', 'admin', '81dc9bdb52d04dc20036dbd8313ed055', 1, 1),
(39, 'Usuario Demo', 'userdemo@gmail.com', 'demo', 'fe01ce2a7fbac8fafaed7c982a04e229', 2, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

DROP TABLE IF EXISTS `venta`;
CREATE TABLE `venta` (
  `noventa` bigint(20) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario` int(11) DEFAULT NULL,
  `caja` int(11) NOT NULL,
  `codcliente` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `tipo_pago_detalle` varchar(50) DEFAULT NULL,
  `totalventa` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) NOT NULL,
  `abono` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `venta`
--

INSERT INTO `venta` (`noventa`, `fecha`, `usuario`, `caja`, `codcliente`, `status`, `tipo_pago_detalle`, `totalventa`, `descuento`, `abono`) VALUES
(1188, '2025-10-17 19:39:59', 1, 161, 1, 1, NULL, 235.00, 0.00, 0.00),
(1189, '2025-10-17 20:34:17', 1, 161, 1, 1, NULL, 705.00, 0.00, 0.00),
(1190, '2025-10-17 20:49:35', 1, 161, 1, 1, NULL, 235.00, 0.00, 0.00),
(1191, '2025-10-17 21:04:11', 1, 161, 1, 1, NULL, 235.00, 0.00, 0.00),
(1192, '2025-10-17 21:10:00', 1, 161, 1, 1, NULL, 235.00, 0.00, 0.00),
(1193, '2025-10-18 09:10:58', 1, 161, 1, 1, NULL, 690.00, 0.00, 0.00),
(1194, '2025-10-18 09:15:34', 1, 161, 1, 1, NULL, 2405.00, 0.00, 0.00),
(1195, '2025-10-18 09:16:50', 1, 161, 1, 1, NULL, 1940.00, 0.00, 0.00),
(1196, '2025-10-18 09:43:16', 1, 161, 1, 2, NULL, 260.00, 0.00, 0.00),
(1197, '2025-10-18 09:44:27', 1, 161, 1, 1, NULL, 1570.00, 0.00, 0.00),
(1198, '2025-10-20 14:37:01', 1, 161, 1, 1, NULL, 1080.00, 0.00, 0.00),
(1199, '2025-10-20 15:25:43', 1, 161, 1, 1, NULL, 235.00, 0.00, 0.00),
(1200, '2025-10-20 15:26:07', 1, 161, 1, 1, NULL, 0.00, 0.00, 0.00),
(1202, '2025-10-22 13:22:11', 1, 161, 74, 2, NULL, 155.00, 0.00, 0.00),
(1203, '2025-10-22 13:23:01', 1, 161, 74, 1, NULL, 235.00, 0.00, 0.00),
(1204, '2025-10-22 13:23:25', 1, 161, 1, 1, NULL, 260.00, 0.00, 0.00),
(1205, '2025-11-11 14:45:11', 39, 161, 75, 2, NULL, 33000.00, 0.00, 0.00),
(1206, '2025-11-11 23:54:02', 39, 161, 1, 1, NULL, 10000.00, 0.00, 0.00),
(1207, '2025-11-12 00:02:21', 39, 161, 75, 4, NULL, 5000.00, 0.00, 0.00),
(1208, '2025-11-12 00:03:32', 39, 161, 75, 5, NULL, 5000.00, 0.00, 0.00),
(1209, '2025-11-12 00:22:12', 39, 161, 75, 2, NULL, 5000.00, 0.00, 0.00),
(1210, '2025-11-12 00:24:22', 39, 161, 75, 2, NULL, 5000.00, 0.00, 0.00),
(1211, '2025-11-12 00:46:30', 39, 161, 1, 1, NULL, 5000.00, 0.00, 0.00),
(1212, '2025-11-12 00:46:52', 39, 161, 1, 1, NULL, 5000.00, 0.00, 0.00),
(1213, '2025-11-12 00:52:41', 39, 161, 75, 2, NULL, 5000.00, 0.00, 0.00),
(1214, '2025-11-12 18:44:47', 39, 161, 1, 1, NULL, 260.00, 0.00, 0.00),
(1215, '2025-11-17 19:24:16', 1, 161, 74, 4, NULL, 17000.00, 0.00, 0.00),
(1216, '2025-11-17 19:33:37', 1, 161, 1, 1, NULL, 2580.00, 0.00, 0.00),
(1217, '2025-11-17 19:36:45', 1, 161, 1, 1, NULL, 2500.00, 0.00, 0.00),
(1218, '2025-11-17 19:39:29', 1, 161, 1, 1, NULL, 9000.00, 0.00, 0.00),
(1219, '2025-11-18 09:00:51', 1, 161, 1, 1, NULL, 1080.00, 0.00, 0.00),
(1220, '2025-12-25 11:45:38', 39, 161, 77, 1, NULL, 8240.00, 0.00, 0.00),
(1221, '2025-12-25 13:08:58', 39, 161, 1, 1, NULL, 45080.00, 0.00, 0.00),
(1222, '2025-12-25 13:49:25', 39, 161, 77, 1, NULL, 47500.00, 0.00, 0.00),
(1223, '2025-12-25 13:56:19', 39, 161, 1, 1, NULL, 5400.00, 0.00, 0.00),
(1224, '2025-12-25 16:39:23', 39, 161, 77, 5, '5', 1080.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_alertas_vencimiento`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `v_alertas_vencimiento`;
CREATE TABLE `v_alertas_vencimiento` (
`id` int(11)
,`numero_lote` varchar(100)
,`fecha_vencimiento` date
,`cantidad_actual` int(11)
,`codproducto` int(11)
,`codigo` varchar(20)
,`descripcion` varchar(100)
,`dias_restantes` int(7)
,`estado_alerta` varchar(7)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_alertas_vencimiento`
--
DROP TABLE IF EXISTS `v_alertas_vencimiento`;

DROP VIEW IF EXISTS `v_alertas_vencimiento`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_alertas_vencimiento`  AS SELECT `l`.`id` AS `id`, `l`.`numero_lote` AS `numero_lote`, `l`.`fecha_vencimiento` AS `fecha_vencimiento`, `l`.`cantidad_actual` AS `cantidad_actual`, `p`.`codproducto` AS `codproducto`, `p`.`codigo` AS `codigo`, `p`.`descripcion` AS `descripcion`, to_days(`l`.`fecha_vencimiento`) - to_days(curdate()) AS `dias_restantes`, CASE WHEN to_days(`l`.`fecha_vencimiento`) - to_days(curdate()) < 0 THEN 'vencido' WHEN to_days(`l`.`fecha_vencimiento`) - to_days(curdate()) <= 30 THEN 'critico' WHEN to_days(`l`.`fecha_vencimiento`) - to_days(curdate()) <= 90 THEN 'proximo' ELSE 'normal' END AS `estado_alerta` FROM (`lotes` `l` join `producto` `p` on(`l`.`codproducto` = `p`.`codproducto`)) WHERE `l`.`status` = 1 AND `l`.`cantidad_actual` > 0 ORDER BY `l`.`fecha_vencimiento` ASC ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bancos`
--
ALTER TABLE `bancos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `caja`
--
ALTER TABLE `caja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario` (`usuario`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`idcliente`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`nocompra`),
  ADD KEY `usuario` (`usuario`),
  ADD KEY `codproveedor` (`codproveedor`),
  ADD KEY `caja` (`caja`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `detalleventa`
--
ALTER TABLE `detalleventa`
  ADD PRIMARY KEY (`correlativo`),
  ADD KEY `codproducto` (`codproducto`),
  ADD KEY `noventa` (`noventa`),
  ADD KEY `fk_detalleventa_lote` (`id_lote`);

--
-- Indices de la tabla `detalle_recibo`
--
ALTER TABLE `detalle_recibo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `noventa` (`noventa`),
  ADD KEY `usuario` (`usuario`),
  ADD KEY `caja` (`caja`);

--
-- Indices de la tabla `detalle_recibo_compra`
--
ALTER TABLE `detalle_recibo_compra`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nocompra` (`nocompra`),
  ADD KEY `usuario` (`usuario`),
  ADD KEY `caja` (`caja`);

--
-- Indices de la tabla `detalle_temp`
--
ALTER TABLE `detalle_temp`
  ADD PRIMARY KEY (`correlativo`),
  ADD KEY `codproducto` (`codproducto`),
  ADD KEY `token_user` (`token_user`);

--
-- Indices de la tabla `detalle_temp_compra`
--
ALTER TABLE `detalle_temp_compra`
  ADD PRIMARY KEY (`correlativo`),
  ADD KEY `token_user` (`token_user`),
  ADD KEY `codproducto` (`codproducto`);

--
-- Indices de la tabla `egresos`
--
ALTER TABLE `egresos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `caja` (`caja`);

--
-- Indices de la tabla `entradas`
--
ALTER TABLE `entradas`
  ADD PRIMARY KEY (`correlativo`),
  ADD KEY `codproducto` (`codproducto`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `nocompra` (`nocompra`);

--
-- Indices de la tabla `lotes`
--
ALTER TABLE `lotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_producto` (`codproducto`),
  ADD KEY `idx_vencimiento` (`fecha_vencimiento`),
  ADD KEY `idx_lote` (`numero_lote`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_lotes_producto_venc_status` (`codproducto`,`fecha_vencimiento`,`status`),
  ADD KEY `idx_lotes_fecha_rango` (`fecha_vencimiento`,`status`,`cantidad_actual`);

--
-- Indices de la tabla `movimientos_lotes`
--
ALTER TABLE `movimientos_lotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lote` (`id_lote`),
  ADD KEY `idx_fecha` (`fecha_movimiento`),
  ADD KEY `idx_tipo` (`tipo_movimiento`),
  ADD KEY `fk_movimiento_usuario` (`usuario_id`),
  ADD KEY `idx_movimientos_fecha_tipo` (`fecha_movimiento`,`tipo_movimiento`);

--
-- Indices de la tabla `pagos_qr`
--
ALTER TABLE `pagos_qr`
  ADD PRIMARY KEY (`id`),
  ADD KEY `noventa` (`noventa`);

--
-- Indices de la tabla `pagos_tarjeta`
--
ALTER TABLE `pagos_tarjeta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `noventa` (`noventa`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`codproducto`),
  ADD KEY `proveedor` (`proveedor`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`codproveedor`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`idrol`);

--
-- Indices de la tabla `transferencias`
--
ALTER TABLE `transferencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `noventa` (`noventa`),
  ADD KEY `banco_id` (`banco_id`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`idusuario`),
  ADD KEY `rol` (`rol`);

--
-- Indices de la tabla `venta`
--
ALTER TABLE `venta`
  ADD PRIMARY KEY (`noventa`),
  ADD KEY `usuario` (`usuario`),
  ADD KEY `codcliente` (`codcliente`),
  ADD KEY `caja` (`caja`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bancos`
--
ALTER TABLE `bancos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `caja`
--
ALTER TABLE `caja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `idcliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de la tabla `compras`
--
ALTER TABLE `compras`
  MODIFY `nocompra` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `detalleventa`
--
ALTER TABLE `detalleventa`
  MODIFY `correlativo` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1645;

--
-- AUTO_INCREMENT de la tabla `detalle_recibo`
--
ALTER TABLE `detalle_recibo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT de la tabla `detalle_recibo_compra`
--
ALTER TABLE `detalle_recibo_compra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `detalle_temp`
--
ALTER TABLE `detalle_temp`
  MODIFY `correlativo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2043;

--
-- AUTO_INCREMENT de la tabla `detalle_temp_compra`
--
ALTER TABLE `detalle_temp_compra`
  MODIFY `correlativo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=176;

--
-- AUTO_INCREMENT de la tabla `egresos`
--
ALTER TABLE `egresos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT de la tabla `entradas`
--
ALTER TABLE `entradas`
  MODIFY `correlativo` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=911;

--
-- AUTO_INCREMENT de la tabla `lotes`
--
ALTER TABLE `lotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimientos_lotes`
--
ALTER TABLE `movimientos_lotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos_qr`
--
ALTER TABLE `pagos_qr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pagos_tarjeta`
--
ALTER TABLE `pagos_tarjeta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `codproducto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2091;

--
-- AUTO_INCREMENT de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  MODIFY `codproveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `idrol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `transferencias`
--
ALTER TABLE `transferencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `idusuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `venta`
--
ALTER TABLE `venta`
  MODIFY `noventa` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1225;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `caja`
--
ALTER TABLE `caja`
  ADD CONSTRAINT `caja_ibfk_1` FOREIGN KEY (`usuario`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD CONSTRAINT `cliente_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `compras_ibfk_1` FOREIGN KEY (`codproveedor`) REFERENCES `proveedor` (`codproveedor`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `compras_ibfk_2` FOREIGN KEY (`usuario`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `compras_ibfk_3` FOREIGN KEY (`caja`) REFERENCES `caja` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalleventa`
--
ALTER TABLE `detalleventa`
  ADD CONSTRAINT `detalleventa_ibfk_1` FOREIGN KEY (`noventa`) REFERENCES `venta` (`noventa`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detalleventa_ibfk_2` FOREIGN KEY (`codproducto`) REFERENCES `producto` (`codproducto`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalleventa_lote` FOREIGN KEY (`id_lote`) REFERENCES `lotes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_recibo`
--
ALTER TABLE `detalle_recibo`
  ADD CONSTRAINT `detalle_recibo_ibfk_1` FOREIGN KEY (`noventa`) REFERENCES `venta` (`noventa`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detalle_recibo_ibfk_2` FOREIGN KEY (`usuario`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detalle_recibo_ibfk_3` FOREIGN KEY (`caja`) REFERENCES `caja` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_recibo_compra`
--
ALTER TABLE `detalle_recibo_compra`
  ADD CONSTRAINT `detalle_recibo_compra_ibfk_1` FOREIGN KEY (`nocompra`) REFERENCES `compras` (`nocompra`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detalle_recibo_compra_ibfk_2` FOREIGN KEY (`usuario`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detalle_recibo_compra_ibfk_3` FOREIGN KEY (`caja`) REFERENCES `caja` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_temp`
--
ALTER TABLE `detalle_temp`
  ADD CONSTRAINT `detalle_temp_ibfk_1` FOREIGN KEY (`codproducto`) REFERENCES `producto` (`codproducto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `egresos`
--
ALTER TABLE `egresos`
  ADD CONSTRAINT `egresos_ibfk_1` FOREIGN KEY (`caja`) REFERENCES `caja` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `entradas`
--
ALTER TABLE `entradas`
  ADD CONSTRAINT `entradas_ibfk_2` FOREIGN KEY (`codproducto`) REFERENCES `producto` (`codproducto`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `entradas_ibfk_3` FOREIGN KEY (`nocompra`) REFERENCES `compras` (`nocompra`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `lotes`
--
ALTER TABLE `lotes`
  ADD CONSTRAINT `fk_lote_producto` FOREIGN KEY (`codproducto`) REFERENCES `producto` (`codproducto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `movimientos_lotes`
--
ALTER TABLE `movimientos_lotes`
  ADD CONSTRAINT `fk_movimiento_lote` FOREIGN KEY (`id_lote`) REFERENCES `lotes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimiento_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`idusuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `producto_ibfk_1` FOREIGN KEY (`proveedor`) REFERENCES `proveedor` (`codproveedor`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `producto_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD CONSTRAINT `proveedor_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `transferencias`
--
ALTER TABLE `transferencias`
  ADD CONSTRAINT `transferencias_ibfk_1` FOREIGN KEY (`noventa`) REFERENCES `venta` (`noventa`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `transferencias_ibfk_2` FOREIGN KEY (`banco_id`) REFERENCES `bancos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`rol`) REFERENCES `rol` (`idrol`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `venta`
--
ALTER TABLE `venta`
  ADD CONSTRAINT `venta_ibfk_1` FOREIGN KEY (`usuario`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `venta_ibfk_2` FOREIGN KEY (`codcliente`) REFERENCES `cliente` (`idcliente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `venta_ibfk_3` FOREIGN KEY (`caja`) REFERENCES `caja` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
