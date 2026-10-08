<?php
$host = 'localhost';
$user = 'root';
$password = '';
$db = 'ventas_bd';

$conection = mysqli_connect($host, $user, $password, $db);

if (!$conection) {
    die("Error en la conexión: " . mysqli_connect_error());
}

if (!function_exists('ensureProductoColumns')) {
    function ensureProductoColumns($conection) {
        $checks = [
            'codigo_interno' => "ALTER TABLE producto ADD COLUMN codigo_interno VARCHAR(50) NULL DEFAULT NULL AFTER codigo",
            'precio_mayorista' => "ALTER TABLE producto ADD COLUMN precio_mayorista DECIMAL(10,2) NULL DEFAULT NULL AFTER precio",
            'categoria_id' => "ALTER TABLE producto ADD COLUMN categoria_id INT NULL DEFAULT NULL AFTER usuario_id",
            'marca_id' => "ALTER TABLE producto ADD COLUMN marca_id INT NULL DEFAULT NULL AFTER categoria_id",
            'tamano_id' => "ALTER TABLE producto ADD COLUMN tamano_id INT NULL DEFAULT NULL AFTER marca_id"
        ];

        foreach ($checks as $column => $sql) {
            $check = mysqli_query($conection, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'producto' AND COLUMN_NAME = '$column'");
            if ($check && mysqli_num_rows($check) == 0) {
                @mysqli_query($conection, $sql);
            }
        }
    }
}

ensureProductoColumns($conection);

if (!function_exists('ensureRelationTables')) {
    function ensureRelationTables($conection) {
        $tables = [
            'categoria' => "CREATE TABLE IF NOT EXISTS `categoria` (
                `idcategoria` INT AUTO_INCREMENT PRIMARY KEY,
                `nombre` VARCHAR(100) NOT NULL,
                `status` INT DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            'marca' => "CREATE TABLE IF NOT EXISTS `marca` (
                `idmarca` INT AUTO_INCREMENT PRIMARY KEY,
                `nombre` VARCHAR(100) NOT NULL,
                `status` INT DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            'tamano' => "CREATE TABLE IF NOT EXISTS `tamano` (
                `idtamano` INT AUTO_INCREMENT PRIMARY KEY,
                `nombre` VARCHAR(100) NOT NULL,
                `status` INT DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            'categoria_marca' => "CREATE TABLE IF NOT EXISTS `categoria_marca` (
                `categoria_id` INT NOT NULL,
                `marca_id` INT NOT NULL,
                PRIMARY KEY (`categoria_id`,`marca_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            'categoria_tamano' => "CREATE TABLE IF NOT EXISTS `categoria_tamano` (
                `categoria_id` INT NOT NULL,
                `tamano_id` INT NOT NULL,
                PRIMARY KEY (`categoria_id`,`tamano_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            'marca_tamano' => "CREATE TABLE IF NOT EXISTS `marca_tamano` (
                `marca_id` INT NOT NULL,
                `tamano_id` INT NOT NULL,
                PRIMARY KEY (`marca_id`,`tamano_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];

        foreach ($tables as $table => $sql) {
            $check = mysqli_query($conection, "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'");
            if ($check && mysqli_num_rows($check) == 0) {
                @mysqli_query($conection, $sql);
            }
        }
    }
}

ensureRelationTables($conection);

if (!function_exists('formatCant')) {
    function formatCant($cantidad) {
        if ($cantidad == "" || $cantidad == null) return "0";
        return number_format((float)$cantidad, 0, ',', '.');
    }
}
