-- ==========================================================
-- SCRIPT DE LIMPIEZA PARA NUEVO NEGOCIO (MODO SEGURO)
-- ==========================================================
-- Este script usa DELETE en lugar de TRUNCATE para evitar
-- conflictos con llaves foráneas en algunas versiones de MySQL.
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Limpiar tablas
DELETE FROM `detalleventa`;
DELETE FROM `detalle_recibo`;
DELETE FROM `detalle_temp`;
DELETE FROM `transferencias`;
DELETE FROM `pagos_qr`;
DELETE FROM `pagos_tarjeta`;
DELETE FROM `venta`;

DELETE FROM `entradas`;
DELETE FROM `detalle_recibo_compra`;
DELETE FROM `detalle_temp_compra`;
DELETE FROM `compras`;

DELETE FROM `egresos`;
DELETE FROM `caja`;

DELETE FROM `movimientos_lotes`;
DELETE FROM `lotes`;

DELETE FROM `producto`;

-- Resetear Auto-Incrementos
ALTER TABLE `detalleventa` AUTO_INCREMENT = 1;
ALTER TABLE `detalle_recibo` AUTO_INCREMENT = 1;
ALTER TABLE `detalle_temp` AUTO_INCREMENT = 1;
ALTER TABLE `transferencias` AUTO_INCREMENT = 1;
ALTER TABLE `pagos_qr` AUTO_INCREMENT = 1;
ALTER TABLE `pagos_tarjeta` AUTO_INCREMENT = 1;
ALTER TABLE `venta` AUTO_INCREMENT = 1;
ALTER TABLE `entradas` AUTO_INCREMENT = 1;
ALTER TABLE `detalle_recibo_compra` AUTO_INCREMENT = 1;
ALTER TABLE `detalle_temp_compra` AUTO_INCREMENT = 1;
ALTER TABLE `compras` AUTO_INCREMENT = 1;
ALTER TABLE `egresos` AUTO_INCREMENT = 1;
ALTER TABLE `caja` AUTO_INCREMENT = 1;
ALTER TABLE `movimientos_lotes` AUTO_INCREMENT = 1;
ALTER TABLE `lotes` AUTO_INCREMENT = 1;
ALTER TABLE `producto` AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;
