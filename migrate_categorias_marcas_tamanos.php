<?php
include "conexion.php";

echo "<h2>Iniciando migración de base de datos...</h2>";

// 1. Crear tabla categoria
$sql_cat = "CREATE TABLE IF NOT EXISTS `categoria` (
    `idcategoria` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `status` INT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if (mysqli_query($conection, $sql_cat)) {
    echo "<p>Tabla `categoria`: OK</p>";
} else {
    echo "<p style='color:red;'>Error creando tabla `categoria`: " . mysqli_error($conection) . "</p>";
}

// 2. Crear tabla marca
$sql_marca = "CREATE TABLE IF NOT EXISTS `marca` (
    `idmarca` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `status` INT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if (mysqli_query($conection, $sql_marca)) {
    echo "<p>Tabla `marca`: OK</p>";
} else {
    echo "<p style='color:red;'>Error creando tabla `marca`: " . mysqli_error($conection) . "</p>";
}

// 3. Crear tabla tamano
$sql_tam = "CREATE TABLE IF NOT EXISTS `tamano` (
    `idtamano` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `status` INT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if (mysqli_query($conection, $sql_tam)) {
    echo "<p>Tabla `tamano`: OK</p>";
} else {
    echo "<p style='color:red;'>Error creando tabla `tamano`: " . mysqli_error($conection) . "</p>";
}

// 3b. Crear tablas de relación
$rel_tables = [
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
foreach ($rel_tables as $tbl => $sql) {
    if (mysqli_query($conection, $sql)) {
        echo "<p>Tabla `$tbl`: OK</p>";
    } else {
        echo "<p style='color:red;'>Error creando tabla `$tbl`: " . mysqli_error($conection) . "</p>";
    }
}

// 4. Agregar columnas a producto si no existen
$columns_to_add = [
    'categoria_id' => "INT DEFAULT NULL",
    'marca_id' => "INT DEFAULT NULL",
    'tamano_id' => "INT DEFAULT NULL"
];

foreach ($columns_to_add as $col => $definition) {
    // Verificar si la columna existe
    $check_col = mysqli_query($conection, "SHOW COLUMNS FROM `producto` LIKE '$col'");
    if (mysqli_num_rows($check_col) == 0) {
        $alter_sql = "ALTER TABLE `producto` ADD COLUMN `$col` $definition;";
        if (mysqli_query($conection, $alter_sql)) {
            echo "<p>Columna `$col` agregada a la tabla `producto`: OK</p>";
        } else {
            echo "<p style='color:red;'>Error agregando columna `$col`: " . mysqli_error($conection) . "</p>";
        }
    } else {
        echo "<p>Columna `$col` ya existe en la tabla `producto`: OK</p>";
    }
}

// 5. Insertar registros por defecto si están vacías
// Categorías
$q_check_cat = mysqli_query($conection, "SELECT COUNT(*) as total FROM `categoria`");
$r_check_cat = mysqli_fetch_assoc($q_check_cat);
if ($r_check_cat['total'] == 0) {
    $insert_cat = "INSERT INTO `categoria` (nombre) VALUES ('General'), ('Bebidas'), ('Alimentos'), ('Limpieza');";
    mysqli_query($conection, $insert_cat);
    echo "<p>Registros por defecto insertados en `categoria`: OK</p>";
}

// Marcas
$q_check_marca = mysqli_query($conection, "SELECT COUNT(*) as total FROM `marca`");
$r_check_marca = mysqli_fetch_assoc($q_check_marca);
if ($r_check_marca['total'] == 0) {
    $insert_marca = "INSERT INTO `marca` (nombre) VALUES ('General'), ('Coca-Cola'), ('Pepsi'), ('Nestlé');";
    mysqli_query($conection, $insert_marca);
    echo "<p>Registros por defecto insertados en `marca`: OK</p>";
}

// Tamaños
$q_check_tam = mysqli_query($conection, "SELECT COUNT(*) as total FROM `tamano`");
$r_check_tam = mysqli_fetch_assoc($q_check_tam);
if ($r_check_tam['total'] == 0) {
    $insert_tam = "INSERT INTO `tamano` (nombre) VALUES ('Estándar'), ('500ml'), ('1L'), ('2L');";
    mysqli_query($conection, $insert_tam);
    echo "<p>Registros por defecto insertados en `tamano`: OK</p>";
}

echo "<h3>Migración finalizada con éxito.</h3>";
?>
