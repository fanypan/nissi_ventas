<?php
/**
 * IMPORTACIÓN MASIVA DE PRODUCTOS DESDE FOTOS
 * Datos transcritos manualmente de las 16 fotos en C:\xampp\htdocs\Sistemaventas\fotos
 * 
 * Ejecutar desde el navegador: http://localhost/Sistemaventas/sistema/importar_fotos.php
 * Solo admins pueden ejecutar este script.
 */
session_start();
include "../conexion.php";

if (empty($_SESSION['active']) || $_SESSION['rol'] != 1) {
    die("Acceso denegado. Solo administradores.");
}

// =========================================================================
// DEFINIR TODOS LOS PRODUCTOS AGRUPADOS POR CATEGORÍA / PROVEEDOR
// Cada grupo tiene: titulo, proveedor, categoria, y lista de productos
// =========================================================================
$grupos = [];

// -----------------------------------------------------------------------
// FOTO 1: Souvenir (1.13.49 PM (2))
// Título: Nombre Souvenir — sin proveedor explícito
// -----------------------------------------------------------------------
$grupos[] = [
    'titulo'    => 'Souvenir',
    'proveedor' => '', // dejar vacío para asignar manualmente
    'categoria' => 'Souvenir',
    'marca'     => '',
    'tamano'    => '',
    'productos' => [
        ['nombre' => 'Bincha Doctora',              'cantidad' => 15,  'costo' => 4000,  'precio' => 5000],
        ['nombre' => 'Antifas negro Mujer',          'cantidad' => 174, 'costo' => 3500,  'precio' => 5000],
        ['nombre' => 'Vincha corrugado frase',       'cantidad' => 10,  'costo' => 2000,  'precio' => 5000],
        ['nombre' => 'Antifas de cartón',            'cantidad' => 46,  'costo' => 1500,  'precio' => 2000],
        ['nombre' => 'Llavero',                      'cantidad' => 6,   'costo' => 2000,  'precio' => 3000],
        ['nombre' => 'Bincha con velo rosa',         'cantidad' => 1,   'costo' => 12000, 'precio' => 15000],
        ['nombre' => 'Hen Night goma/Muñe',          'cantidad' => 7,   'costo' => 8000,  'precio' => 12000],
        ['nombre' => 'Moño de plástico',             'cantidad' => 20,  'costo' => 1500,  'precio' => 2000],
        ['nombre' => 'Castilla Masculina',           'cantidad' => 2,   'costo' => 5000,  'precio' => 8000],
        ['nombre' => 'Machete',                      'cantidad' => 3,   'costo' => 8000,  'precio' => 10000],
        ['nombre' => 'Tatto Pintura p/cara',         'cantidad' => 1,   'costo' => 20000, 'precio' => 25000],
        ['nombre' => 'Carita pintada Rectángulo',    'cantidad' => 27,  'costo' => 8000,  'precio' => 10000],
        ['nombre' => 'Carita pintada Cuadrado',      'cantidad' => 27,  'costo' => 8000,  'precio' => 10000],
        ['nombre' => 'Esposa',                       'cantidad' => 2,   'costo' => 8000,  'precio' => 10000],
        ['nombre' => 'Neón para la cara',            'cantidad' => 15,  'costo' => 5000,  'precio' => 6000],
        ['nombre' => 'Bigote',                       'cantidad' => 91,  'costo' => 3000,  'precio' => 5000],
        ['nombre' => 'Mecha',                        'cantidad' => 3,   'costo' => 4000,  'precio' => 5000],
        ['nombre' => 'Barba',                        'cantidad' => 2,   'costo' => 8000,  'precio' => 10000],
        ['nombre' => 'Collar Neón',                  'cantidad' => 1,   'costo' => 3000,  'precio' => 5000],
        ['nombre' => 'Anillo Neón',                  'cantidad' => 9,   'costo' => 3000,  'precio' => 5000],
        ['nombre' => 'Paleta Neón',                  'cantidad' => 4,   'costo' => 7000,  'precio' => 10000],
        ['nombre' => 'Bincha Minnie sin moño',       'cantidad' => 16,  'costo' => 5000,  'precio' => 7000],
        ['nombre' => 'Bincha Minnie con moño',       'cantidad' => 24,  'costo' => 7000,  'precio' => 10000],
        ['nombre' => 'Bincha Unicornio',             'cantidad' => 44,  'costo' => 10000, 'precio' => 15000],
        ['nombre' => 'Bincha Unicornio Lentejuela',  'cantidad' => 1,   'costo' => 6000,  'precio' => 9000],
    ]
];

// -----------------------------------------------------------------------
// FOTO 2: Globo Tamaño 32 Fuccia (10.13.53 PM)
// -----------------------------------------------------------------------
$grupos[] = [
    'titulo'    => 'Globo Tamaño 32 Fuccia',
    'proveedor' => 'Asa Import',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => '32',
    'productos' => [
        ['nombre' => 'Número 1 Fuccia', 'cantidad' => 26, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 2 Fuccia', 'cantidad' => 23, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 3 Fuccia', 'cantidad' => 8,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 4 Fuccia', 'cantidad' => 37, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 5 Fuccia', 'cantidad' => 24, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 6 Fuccia', 'cantidad' => 24, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 7 Fuccia', 'cantidad' => 43, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 8 Fuccia', 'cantidad' => 17, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 9 Fuccia', 'cantidad' => 26, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 0 Fuccia', 'cantidad' => 79, 'costo' => 5000, 'precio' => 10000],
    ]
];

// Globo Tamaño 32 Azul (misma foto)
$grupos[] = [
    'titulo'    => 'Globo Tamaño 32 Azul',
    'proveedor' => 'Asa Import',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => '32',
    'productos' => [
        ['nombre' => 'Número 1 Azul', 'cantidad' => 7,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 2 Azul', 'cantidad' => 29, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 3 Azul', 'cantidad' => 40, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 4 Azul', 'cantidad' => 14, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 5 Azul', 'cantidad' => 33, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 6 Azul', 'cantidad' => 15, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 7 Azul', 'cantidad' => 30, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 8 Azul', 'cantidad' => 32, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 9 Azul', 'cantidad' => 32, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 0 Azul', 'cantidad' => 32, 'costo' => 5000, 'precio' => 10000],
    ]
];

// -----------------------------------------------------------------------
// FOTO 3: Globo Piñata - Proveedor Angel House (2.31.27 PM)
// -----------------------------------------------------------------------
$grupos[] = [
    'titulo'    => 'Globo Piñata - Angel House (colores)',
    'proveedor' => 'Angel House',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => 'Piñata',
    'productos' => [
        ['nombre' => 'Azul Marino',        'cantidad' => 146, 'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Nude',               'cantidad' => 64,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Marrón Capibara',    'cantidad' => 98,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Amarillo Pastel',    'cantidad' => 60,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Verde Eucalipto',    'cantidad' => 22,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Verde oscuro',       'cantidad' => 119, 'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Verde agua',         'cantidad' => 84,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Beige',              'cantidad' => 47,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Rosa',               'cantidad' => 11,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Lila Pastel Oscuro', 'cantidad' => 87,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Bordó',              'cantidad' => 33,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Lila claro',         'cantidad' => 10,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Naranja',            'cantidad' => 19,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Rosa claro',         'cantidad' => 62,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Rosa oscuro',        'cantidad' => 69,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Amarillo',           'cantidad' => 36,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Fuccia',             'cantidad' => 1,   'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Marrón oscuro',      'cantidad' => 69,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Lila claro cromado', 'cantidad' => 27,  'costo' => 2500, 'precio' => 4000],
        ['nombre' => 'Negro',              'cantidad' => 91,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Celeste',            'cantidad' => 86,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Blanco',             'cantidad' => 36,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Color piel oscuro',  'cantidad' => 82,  'costo' => 1500, 'precio' => 3000],
    ]
];

// -----------------------------------------------------------------------
// FOTO 4: Globo Piñata - Angel House (cromados) (2.47.41 PM)
// -----------------------------------------------------------------------
$grupos[] = [
    'titulo'    => 'Globo Piñata - Angel House (cromados)',
    'proveedor' => 'Angel House',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => 'Piñata',
    'productos' => [
        ['nombre' => 'Celeste Claro',            'cantidad' => 26,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Plata Cromado',            'cantidad' => 75,  'costo' => 2500, 'precio' => 4000],
        ['nombre' => 'Rose Bronce',              'cantidad' => 2,   'costo' => 2500, 'precio' => 4000],
        ['nombre' => 'Verde limón cromado',      'cantidad' => 25,  'costo' => 2500, 'precio' => 4000],
        ['nombre' => 'Verde agua cromado',       'cantidad' => 31,  'costo' => 2500, 'precio' => 4000],
        ['nombre' => 'Lila oscuro cromado',      'cantidad' => 10,  'costo' => 2500, 'precio' => 4000],
        ['nombre' => 'Rose Gold cromado',        'cantidad' => 98,  'costo' => 2500, 'precio' => 4000],
        ['nombre' => 'Dorado cromado',           'cantidad' => 93,  'costo' => 2500, 'precio' => 4000],
        ['nombre' => 'Rojo oscuro',              'cantidad' => 13,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Lila oscuro',              'cantidad' => 83,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Azul oscuro',              'cantidad' => 140, 'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Púrpura',                  'cantidad' => 25,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Marrón Estilo Naranja',    'cantidad' => 4,   'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Piel claro',               'cantidad' => 7,   'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Verde limón oscuro',       'cantidad' => 15,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Gris',                     'cantidad' => 113, 'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Coral Fuerte',             'cantidad' => 37,  'costo' => 1500, 'precio' => 3000],
        ['nombre' => 'Azul cromado',             'cantidad' => 45,  'costo' => 2500, 'precio' => 4000],
    ]
];

// -----------------------------------------------------------------------
// FOTO 5: Globo Piñata Tamaño 40 - Distribuidora del Rey + Globo Neón (2.49.08 PM)
// -----------------------------------------------------------------------
$grupos[] = [
    'titulo'    => 'Globo Piñata Tamaño 40',
    'proveedor' => 'Distribuidora del Rey',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => '40',
    'productos' => [
        ['nombre' => 'Lila',         'cantidad' => 14, 'costo' => 13500, 'precio' => 15000],
        ['nombre' => 'Celeste',      'cantidad' => 15, 'costo' => 13500, 'precio' => 15000],
        ['nombre' => 'Amarillo',     'cantidad' => 18, 'costo' => 13500, 'precio' => 15000],
        ['nombre' => 'Azul',         'cantidad' => 17, 'costo' => 13500, 'precio' => 15000],
        ['nombre' => 'Negro',        'cantidad' => 27, 'costo' => 13500, 'precio' => 15000],
        ['nombre' => 'Rosa oscuro',  'cantidad' => 22, 'costo' => 13500, 'precio' => 15000],
        ['nombre' => 'Verde',        'cantidad' => 19, 'costo' => 13500, 'precio' => 15000],
        ['nombre' => 'Naranja',      'cantidad' => 32, 'costo' => 13500, 'precio' => 15000],
    ]
];

$grupos[] = [
    'titulo'    => 'Globo Neón',
    'proveedor' => 'Distribuidora del Rey',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => '',
    'productos' => [
        ['nombre' => 'Neón Naranja',      'cantidad' => 18, 'costo' => 8000, 'precio' => 10000],
        ['nombre' => 'Neón Rosa Oscuro',  'cantidad' => 2,  'costo' => 8000, 'precio' => 10000],
        ['nombre' => 'Neón Amarillo',     'cantidad' => 3,  'costo' => 8000, 'precio' => 10000],
        ['nombre' => 'Neón Azul',         'cantidad' => 5,  'costo' => 8000, 'precio' => 10000],
        ['nombre' => 'Neón Surtido',      'cantidad' => 3,  'costo' => 8000, 'precio' => 10000],
    ]
];

// -----------------------------------------------------------------------
// FOTO 6: Kit de Globo + Arco Globos - Asa Import (3.31.48 PM)
// -----------------------------------------------------------------------
$grupos[] = [
    'titulo'    => 'Kit de Globo',
    'proveedor' => 'Asa Import',
    'categoria' => 'Kit Globos',
    'marca'     => '',
    'tamano'    => '',
    'productos' => [
        ['nombre' => 'Kit Flamenco',                              'cantidad' => 4,  'costo' => 55000, 'precio' => 70000],
        ['nombre' => 'Kit Ballons Dorado Plateado y Blanco',      'cantidad' => 32, 'costo' => 35000, 'precio' => 45000],
        ['nombre' => 'Kit Feliz Cumpleaños Pelota',               'cantidad' => 4,  'costo' => 35000, 'precio' => 45000],
        ['nombre' => 'Kit Juego Poker',                           'cantidad' => 5,  'costo' => 60000, 'precio' => 75000],
        ['nombre' => 'Kit Ballon Garland Celeste Dorado con Rosa','cantidad' => 7,  'costo' => 20000, 'precio' => 25000],
    ]
];

$grupos[] = [
    'titulo'    => 'Arco Globos',
    'proveedor' => 'Asa Import',
    'categoria' => 'Arco Globos',
    'marca'     => '',
    'tamano'    => '',
    'productos' => [
        ['nombre' => 'Arco Plateado Rosa Azul',                 'cantidad' => 4,  'costo' => 20000, 'precio' => 25000],
        ['nombre' => 'Globo latex con impresión colores 8',      'cantidad' => 1,  'costo' => 12000, 'precio' => 15000],
        ['nombre' => 'Arco Dorado Negro Blanco',                'cantidad' => 5,  'costo' => 20000, 'precio' => 25000],
        ['nombre' => 'Arco Lila Rosa Celeste',                  'cantidad' => 4,  'costo' => 20000, 'precio' => 25000],
        ['nombre' => 'Arco Rosaclaro Rosaoscuro Dorado',        'cantidad' => 9,  'costo' => 20000, 'precio' => 25000],
        ['nombre' => 'Arco Verde oscuro Verde limón y Blanco',  'cantidad' => 8,  'costo' => 20000, 'precio' => 25000],
        ['nombre' => 'Arco Rojo Blanco',                        'cantidad' => 5,  'costo' => 20000, 'precio' => 25000],
        ['nombre' => 'Arco Marrón Plateado Gris',               'cantidad' => 1,  'costo' => 20000, 'precio' => 25000],
        ['nombre' => 'Ballon Chaik Arco Azul con Blanco',       'cantidad' => 6,  'costo' => 20000, 'precio' => 25000],
        ['nombre' => 'Arco Fuccia con Rosa',                    'cantidad' => 2,  'costo' => 20000, 'precio' => 25000],
        ['nombre' => 'Arco Dorado Blanco y Dorado oscuro',      'cantidad' => 2,  'costo' => 20000, 'precio' => 25000],
    ]
];

// -----------------------------------------------------------------------
// FOTO 7: Set de Globos - Asa Import (3.34.18 PM)
// -----------------------------------------------------------------------
$grupos[] = [
    'titulo'    => 'Set de Globos',
    'proveedor' => 'Asa Import',
    'categoria' => 'Set Globos',
    'marca'     => '',
    'tamano'    => '',
    'productos' => [
        ['nombre' => 'Corazón Rosa Oscuro',                    'cantidad' => 4,  'costo' => 10000, 'precio' => 15000],
        ['nombre' => 'Corazón Rojo',                           'cantidad' => 5,  'costo' => 10000, 'precio' => 15000],
        ['nombre' => 'Corazón dorado con negro y blanco',      'cantidad' => 5,  'costo' => 10000, 'precio' => 15000],
        ['nombre' => 'Corazón lila',                           'cantidad' => 5,  'costo' => 10000, 'precio' => 15000],
        ['nombre' => 'Corazón Plateado',                       'cantidad' => 5,  'costo' => 10000, 'precio' => 15000],
        ['nombre' => 'Corazón dorado con globos dorado y blanco','cantidad' => 4, 'costo' => 16000, 'precio' => 15000],
        ['nombre' => 'Corazón Rose Gold',                      'cantidad' => 5,  'costo' => 10000, 'precio' => 15000],
        ['nombre' => 'Corazón azul',                           'cantidad' => 5,  'costo' => 10000, 'precio' => 15000],
        ['nombre' => 'Corazón rosa pastel',                    'cantidad' => 8,  'costo' => 10000, 'precio' => 15000],
        ['nombre' => 'Corazón celeste pastel',                 'cantidad' => 7,  'costo' => 10000, 'precio' => 15000],
        ['nombre' => 'Estrella Rose Gold',                     'cantidad' => 3,  'costo' => 10000, 'precio' => 15000],
        ['nombre' => 'Estrella Rojo',                          'cantidad' => 2,  'costo' => 10000, 'precio' => 15000],
    ]
];

// -----------------------------------------------------------------------
// FOTO 8: Globo Tamaño 12 - Asa Import (3.46.55 PM (1))
// -----------------------------------------------------------------------
$grupos[] = [
    'titulo'    => 'Globo Tamaño 12',
    'proveedor' => 'Asa Import',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => '12',
    'productos' => [
        ['nombre' => 'Rosa',           'cantidad' => 6,  'costo' => 15000, 'precio' => 18000],
        ['nombre' => 'Marrón oscuro',  'cantidad' => 7,  'costo' => 15000, 'precio' => 18000],
        ['nombre' => 'Naranja',        'cantidad' => 7,  'costo' => 15000, 'precio' => 18000],
        ['nombre' => 'Verde hoja',     'cantidad' => 5,  'costo' => 15000, 'precio' => 18000],
        ['nombre' => 'Verde limón',    'cantidad' => 11, 'costo' => 15000, 'precio' => 18000],
        ['nombre' => 'Turquesa',       'cantidad' => 10, 'costo' => 15000, 'precio' => 18000],
        ['nombre' => 'Verde petroleo', 'cantidad' => 12, 'costo' => 15000, 'precio' => 18000],
        ['nombre' => 'Amarillo',       'cantidad' => 2,  'costo' => 15000, 'precio' => 18000],
        ['nombre' => 'Negro',          'cantidad' => 4,  'costo' => 15000, 'precio' => 18000],
        ['nombre' => 'Celeste',        'cantidad' => 5,  'costo' => 15000, 'precio' => 18000],
    ]
];

// -----------------------------------------------------------------------
// FOTO 9+10: Letra Dorado (Goma Eva) - Ocasional (3.50.41 PM + 3.50.52 PM)
// -----------------------------------------------------------------------
$grupos[] = [
    'titulo'    => 'Letra Dorado (Goma Eva)',
    'proveedor' => 'Ocasional',
    'categoria' => 'Letras Goma Eva',
    'marca'     => '',
    'tamano'    => '',
    'productos' => [
        ['nombre' => 'Letra Dorada A', 'cantidad' => 29, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada B', 'cantidad' => 25, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada C', 'cantidad' => 14, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada D', 'cantidad' => 8,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada E', 'cantidad' => 11, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada F', 'cantidad' => 16, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada G', 'cantidad' => 32, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada H', 'cantidad' => 47, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada I', 'cantidad' => 43, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada J', 'cantidad' => 36, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada K', 'cantidad' => 40, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada L', 'cantidad' => 19, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada M', 'cantidad' => 13, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada N', 'cantidad' => 4,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada Ñ', 'cantidad' => 29, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada O', 'cantidad' => 6,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada P', 'cantidad' => 24, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada Q', 'cantidad' => 27, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada R', 'cantidad' => 9,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada S', 'cantidad' => 4,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada T', 'cantidad' => 24, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada U', 'cantidad' => 35, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada V', 'cantidad' => 23, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada W', 'cantidad' => 31, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada X', 'cantidad' => 42, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada Y', 'cantidad' => 23, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Dorada Z', 'cantidad' => 23, 'costo' => 500, 'precio' => 1000],
    ]
];

// Números Dorado (Goma Eva) - continuación foto 10
$grupos[] = [
    'titulo'    => 'Número Dorado (Goma Eva)',
    'proveedor' => 'Ocasional',
    'categoria' => 'Letras Goma Eva',
    'marca'     => '',
    'tamano'    => '',
    'productos' => [
        ['nombre' => 'Número Dorado 0', 'cantidad' => 1,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Dorado 1', 'cantidad' => 6,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Dorado 2', 'cantidad' => 5,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Dorado 3', 'cantidad' => 13, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Dorado 4', 'cantidad' => 25, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Dorado 5', 'cantidad' => 20, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Dorado 6', 'cantidad' => 7,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Dorado 7', 'cantidad' => 6,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Dorado 8', 'cantidad' => 7,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Dorado 9', 'cantidad' => 3,  'costo' => 500, 'precio' => 1000],
    ]
];

// -----------------------------------------------------------------------
// FOTO 11: Letras Plateado (Goma Eva) - Ocasional (3.57.20 PM (1))
// + continuación foto 12 (4.19.48 PM)
// -----------------------------------------------------------------------
$grupos[] = [
    'titulo'    => 'Letra Plateado (Goma Eva)',
    'proveedor' => 'Ocasional',
    'categoria' => 'Letras Goma Eva',
    'marca'     => '',
    'tamano'    => '',
    'productos' => [
        ['nombre' => 'Letra Plateada A', 'cantidad' => 19, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada B', 'cantidad' => 27, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada C', 'cantidad' => 33, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada D', 'cantidad' => 43, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada E', 'cantidad' => 15, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada F', 'cantidad' => 34, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada G', 'cantidad' => 18, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada H', 'cantidad' => 11, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada I', 'cantidad' => 35, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada J', 'cantidad' => 18, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada K', 'cantidad' => 23, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada L', 'cantidad' => 24, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada M', 'cantidad' => 50, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada N', 'cantidad' => 26, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada Ñ', 'cantidad' => 29, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada O', 'cantidad' => 17, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada P', 'cantidad' => 25, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada Q', 'cantidad' => 14, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada R', 'cantidad' => 24, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada S', 'cantidad' => 47, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada T', 'cantidad' => 23, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada U', 'cantidad' => 6,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada V', 'cantidad' => 29, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada W', 'cantidad' => 15, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada X', 'cantidad' => 19, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada Y', 'cantidad' => 7,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Letra Plateada Z', 'cantidad' => 18, 'costo' => 500, 'precio' => 1000],
    ]
];

// Número Plateado (Goma Eva) - foto 12
$grupos[] = [
    'titulo'    => 'Número Plateado (Goma Eva)',
    'proveedor' => 'Ocasional',
    'categoria' => 'Letras Goma Eva',
    'marca'     => '',
    'tamano'    => '',
    'productos' => [
        ['nombre' => 'Número Plateado 0', 'cantidad' => 11, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Plateado 1', 'cantidad' => 6,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Plateado 2', 'cantidad' => 5,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Plateado 3', 'cantidad' => 19, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Plateado 4', 'cantidad' => 13, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Plateado 5', 'cantidad' => 12, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Plateado 6', 'cantidad' => 4,  'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Plateado 7', 'cantidad' => 12, 'costo' => 500, 'precio' => 1000],
        ['nombre' => 'Número Plateado 8', 'cantidad' => 5,  'costo' => 500, 'precio' => 1000],
    ]
];

// -----------------------------------------------------------------------
// FOTO 13: Cinta para Moño - (6.05.28 PM + 6.07.13 PM)
// -----------------------------------------------------------------------
$grupos[] = [
    'titulo'    => 'Cinta para Moño 1cm',
    'proveedor' => 'Asa Import',
    'categoria' => 'Cintas',
    'marca'     => '',
    'tamano'    => '1cm',
    'productos' => [
        ['nombre' => 'Cinta 1cm Rosa',      'cantidad' => 8,  'costo' => 4000, 'precio' => 5000],
        ['nombre' => 'Cinta 1cm Rojo',      'cantidad' => 9,  'costo' => 4000, 'precio' => 5000],
        ['nombre' => 'Cinta 1cm Negro',     'cantidad' => 9,  'costo' => 4000, 'precio' => 5000],
        ['nombre' => 'Cinta 1cm Gris',      'cantidad' => 8,  'costo' => 4000, 'precio' => 5000],
        ['nombre' => 'Cinta 1cm Verde',     'cantidad' => 10, 'costo' => 4000, 'precio' => 5000],
        ['nombre' => 'Cinta 1cm Blanco',    'cantidad' => 8,  'costo' => 4000, 'precio' => 5000],
        ['nombre' => 'Cinta 1cm Amarillo',  'cantidad' => 10, 'costo' => 4000, 'precio' => 5000],
        ['nombre' => 'Cinta 1cm Dorado',    'cantidad' => 10, 'costo' => 4000, 'precio' => 5000],
        ['nombre' => 'Cinta 1cm Celeste',   'cantidad' => 17, 'costo' => 4000, 'precio' => 5000],
    ]
];

$grupos[] = [
    'titulo'    => 'Cinta para Moño 2cm',
    'proveedor' => 'Asa Import',
    'categoria' => 'Cintas',
    'marca'     => '',
    'tamano'    => '2cm',
    'productos' => [
        ['nombre' => 'Cinta 2cm Verde oscuro', 'cantidad' => 8, 'costo' => 5000, 'precio' => 8000],
        ['nombre' => 'Cinta 2cm Verde limón',  'cantidad' => 5, 'costo' => 5000, 'precio' => 8000],
        ['nombre' => 'Cinta 2cm Rojo',         'cantidad' => 5, 'costo' => 5000, 'precio' => 8000],
        ['nombre' => 'Cinta 2cm Turquesa',     'cantidad' => 4, 'costo' => 5000, 'precio' => 8000],
        ['nombre' => 'Cinta 2cm Naranja',      'cantidad' => 6, 'costo' => 5000, 'precio' => 8000],
        ['nombre' => 'Cinta 2cm Rosa',         'cantidad' => 5, 'costo' => 5000, 'precio' => 8000],
        ['nombre' => 'Cinta 2cm Amarillo',     'cantidad' => 5, 'costo' => 5000, 'precio' => 8000],
        ['nombre' => 'Cinta 2cm Celeste',      'cantidad' => 2, 'costo' => 5000, 'precio' => 8000],
    ]
];

// FOTO: 6.07.13 PM - Cinta para Moño 3cm y 5cm
$grupos[] = [
    'titulo'    => 'Cinta para Moño 3cm',
    'proveedor' => 'Asa Import',
    'categoria' => 'Cintas',
    'marca'     => '',
    'tamano'    => '3cm',
    'productos' => [
        ['nombre' => 'Cinta 3cm Plateado',  'cantidad' => 5, 'costo' => 5500, 'precio' => 8000],
        ['nombre' => 'Cinta 3cm Lila Claro', 'cantidad' => 5, 'costo' => 5500, 'precio' => 8000],
        ['nombre' => 'Cinta 3cm Negro',      'cantidad' => 5, 'costo' => 5500, 'precio' => 8000],
    ]
];

$grupos[] = [
    'titulo'    => 'Cinta para Moño 5cm',
    'proveedor' => 'Asa Import',
    'categoria' => 'Cintas',
    'marca'     => '',
    'tamano'    => '5cm',
    'productos' => [
        ['nombre' => 'Cinta 5cm Blanco', 'cantidad' => 4, 'costo' => 6000, 'precio' => 10000],
    ]
];

// -----------------------------------------------------------------------
// FOTO 14: Globo Tamaño 32 Dorado + Plateado - Asa Import (6.27.35 PM)
// -----------------------------------------------------------------------
$grupos[] = [
    'titulo'    => 'Globo Tamaño 32 Dorado',
    'proveedor' => 'Asa Import',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => '32',
    'productos' => [
        ['nombre' => 'Número 1 Dorado',  'cantidad' => 66,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 2 Dorado',  'cantidad' => 158, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 3 Dorado',  'cantidad' => 108, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 4 Dorado',  'cantidad' => 95,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 5 Dorado',  'cantidad' => 80,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 6 Dorado',  'cantidad' => 113, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 7 Dorado',  'cantidad' => 82,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 8 Dorado',  'cantidad' => 78,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 9 Dorado',  'cantidad' => 56,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 0 Dorado',  'cantidad' => 27,  'costo' => 5000, 'precio' => 10000],
    ]
];

$grupos[] = [
    'titulo'    => 'Globo Tamaño 32 Plateado',
    'proveedor' => 'Asa Import',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => '32',
    'productos' => [
        ['nombre' => 'Número 1 Plateado',  'cantidad' => 21,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 2 Plateado',  'cantidad' => 203, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 3 Plateado',  'cantidad' => 56,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 4 Plateado',  'cantidad' => 52,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 5 Plateado',  'cantidad' => 67,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 6 Plateado',  'cantidad' => 120, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 7 Plateado',  'cantidad' => 34,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 8 Plateado',  'cantidad' => 49,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 9 Plateado',  'cantidad' => 72,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 0 Plateado',  'cantidad' => 50,  'costo' => 5000, 'precio' => 10000],
    ]
];

// -----------------------------------------------------------------------
// FOTO 15: Globo Tamaño 32 Negro + Rosa Claro (7.03.58 PM)
// -----------------------------------------------------------------------
$grupos[] = [
    'titulo'    => 'Globo Tamaño 32 Negro',
    'proveedor' => 'Asa Import',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => '32',
    'productos' => [
        ['nombre' => 'Número 2 Negro', 'cantidad' => 42, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 3 Negro', 'cantidad' => 11, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 4 Negro', 'cantidad' => 6,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 5 Negro', 'cantidad' => 17, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 6 Negro', 'cantidad' => 6,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 7 Negro', 'cantidad' => 19, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 8 Negro', 'cantidad' => 9,  'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 9 Negro', 'cantidad' => 11, 'costo' => 5000, 'precio' => 10000],
    ]
];

$grupos[] = [
    'titulo'    => 'Globo Tamaño 32 Rosa Claro',
    'proveedor' => 'Asa Import',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => '32',
    'productos' => [
        ['nombre' => 'Número 1 Rosa Claro',  'cantidad' => 74, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 2 Rosa Claro',  'cantidad' => 40, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 3 Rosa Claro',  'cantidad' => 27, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 4 Rosa Claro',  'cantidad' => 41, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 5 Rosa Claro',  'cantidad' => 61, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 6 Rosa Claro',  'cantidad' => 56, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 7 Rosa Claro',  'cantidad' => 50, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 8 Rosa Claro',  'cantidad' => 64, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 9 Rosa Claro',  'cantidad' => 47, 'costo' => 5000, 'precio' => 10000],
        ['nombre' => 'Número 0 Rosa Claro',  'cantidad' => 38, 'costo' => 5000, 'precio' => 10000],
    ]
];


// -----------------------------------------------------------------------
// FOTOS NUEVAS: Globo Tamaño 16 - Asa Import (Dorado, Plateado, Azul, Rosa Claro)
// -----------------------------------------------------------------------
$grupos[] = [
    'titulo'    => 'Globo Tamaño 16 Dorado',
    'proveedor' => 'Asa Import',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => '16',
    'productos' => [
        ['nombre' => 'Número 0 Dorado T16', 'cantidad' => 39, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 1 Dorado T16', 'cantidad' => 25, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 2 Dorado T16', 'cantidad' => 78, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 3 Dorado T16', 'cantidad' => 38, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 4 Dorado T16', 'cantidad' => 74, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 5 Dorado T16', 'cantidad' => 30, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 6 Dorado T16', 'cantidad' => 40, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 7 Dorado T16', 'cantidad' => 12, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 8 Dorado T16', 'cantidad' => 21, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 9 Dorado T16', 'cantidad' => 22, 'costo' => 3000, 'precio' => 5000],
    ]
];

$grupos[] = [
    'titulo'    => 'Globo Tamaño 16 Plateado',
    'proveedor' => 'Asa Import',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => '16',
    'productos' => [
        ['nombre' => 'Número 0 Plateado T16', 'cantidad' => 50, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 1 Plateado T16', 'cantidad' => 40, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 2 Plateado T16', 'cantidad' => 60, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 3 Plateado T16', 'cantidad' => 46, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 4 Plateado T16', 'cantidad' => 49, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 5 Plateado T16', 'cantidad' => 79, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 6 Plateado T16', 'cantidad' => 16, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 7 Plateado T16', 'cantidad' => 45, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 8 Plateado T16', 'cantidad' => 15, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 9 Plateado T16', 'cantidad' => 48, 'costo' => 3000, 'precio' => 5000],
    ]
];

$grupos[] = [
    'titulo'    => 'Globo Tamaño 16 Azul',
    'proveedor' => 'Asa Import',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => '16',
    'productos' => [
        ['nombre' => 'Número 0 Azul T16', 'cantidad' => 18, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 1 Azul T16', 'cantidad' => 47, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 2 Azul T16', 'cantidad' => 25, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 3 Azul T16', 'cantidad' => 81, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 4 Azul T16', 'cantidad' => 27, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 5 Azul T16', 'cantidad' => 25, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 6 Azul T16', 'cantidad' => 56, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 7 Azul T16', 'cantidad' => 54, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 8 Azul T16', 'cantidad' => 63, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 9 Azul T16', 'cantidad' => 55, 'costo' => 3000, 'precio' => 5000],
    ]
];

$grupos[] = [
    'titulo'    => 'Globo Tamaño 16 Rosa Claro',
    'proveedor' => 'Asa Import',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => '16',
    'productos' => [
        ['nombre' => 'Número 0 Rosa Claro T16', 'cantidad' => 9,  'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 1 Rosa Claro T16', 'cantidad' => 26, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 2 Rosa Claro T16', 'cantidad' => 20, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 3 Rosa Claro T16', 'cantidad' => 29, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 4 Rosa Claro T16', 'cantidad' => 44, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 5 Rosa Claro T16', 'cantidad' => 45, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 6 Rosa Claro T16', 'cantidad' => 2,  'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 7 Rosa Claro T16', 'cantidad' => 23, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 8 Rosa Claro T16', 'cantidad' => 27, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 9 Rosa Claro T16', 'cantidad' => 29, 'costo' => 3000, 'precio' => 5000],
    ]
];

$grupos[] = [
    'titulo'    => 'Globo Tamaño 16 Fuccia',
    'proveedor' => 'Asa Import',
    'categoria' => 'Globos',
    'marca'     => '',
    'tamano'    => '16',
    'productos' => [
        ['nombre' => 'Número 0 Fuccia T16', 'cantidad' => 6,  'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 1 Fuccia T16', 'cantidad' => 63, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 2 Fuccia T16', 'cantidad' => 43, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 3 Fuccia T16', 'cantidad' => 47, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 4 Fuccia T16', 'cantidad' => 34, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 5 Fuccia T16', 'cantidad' => 64, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 6 Fuccia T16', 'cantidad' => 61, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 7 Fuccia T16', 'cantidad' => 43, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 8 Fuccia T16', 'cantidad' => 33, 'costo' => 3000, 'precio' => 5000],
        ['nombre' => 'Número 9 Fuccia T16', 'cantidad' => 55, 'costo' => 3000, 'precio' => 5000],
    ]
];


// =========================================================================
// =========================================================================
//  EJECUTAR IMPORTACIÓN
// =========================================================================
// =========================================================================

$modo = isset($_GET['ejecutar']) ? 'ejecutar' : 'preview';

// --- Contar totales ---
$totalProductos = 0;
foreach ($grupos as $g) {
    $totalProductos += count($g['productos']);
}

$usuario_id = $_SESSION['idUser'];

// --- Función para buscar o crear proveedor ---
function buscarOCrearProveedor($conection, $nombre) {
    if (empty($nombre)) {
        $nombre = 'Varios';
    }
    $safe = mysqli_real_escape_string($conection, $nombre);
    $q = mysqli_query($conection, "SELECT codproveedor FROM proveedor WHERE proveedor = '$safe' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        $r = mysqli_fetch_assoc($q);
        return (int)$r['codproveedor'];
    }
    // Crear proveedor
    mysqli_query($conection, "INSERT INTO proveedor (proveedor) VALUES ('$safe')");
    return (int)mysqli_insert_id($conection);
}

// --- Función para buscar o crear categoría ---
function buscarOCrearCategoria($conection, $nombre) {
    if (empty($nombre)) return 'NULL';
    $safe = mysqli_real_escape_string($conection, $nombre);
    $q = mysqli_query($conection, "SELECT idcategoria FROM categoria WHERE nombre = '$safe' AND status = 1 LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        $r = mysqli_fetch_assoc($q);
        return (int)$r['idcategoria'];
    }
    mysqli_query($conection, "INSERT INTO categoria (nombre, status) VALUES ('$safe', 1)");
    return (int)mysqli_insert_id($conection);
}

// --- Función para buscar o crear tamaño ---
function buscarOCrearTamano($conection, $nombre) {
    if (empty($nombre)) return 'NULL';
    $safe = mysqli_real_escape_string($conection, $nombre);
    $q = mysqli_query($conection, "SELECT idtamano FROM tamano WHERE nombre = '$safe' AND status = 1 LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        $r = mysqli_fetch_assoc($q);
        return (int)$r['idtamano'];
    }
    mysqli_query($conection, "INSERT INTO tamano (nombre, status) VALUES ('$safe', 1)");
    return (int)mysqli_insert_id($conection);
}

// --- Función para generar código único ---
function generarCodigoUnico($conection) {
    do {
        $randomDigits = str_pad(mt_rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        $codigo = '779' . $randomDigits;
        $q = mysqli_query($conection, "SELECT codigo FROM producto WHERE codigo = '$codigo'");
    } while ($q && mysqli_num_rows($q) > 0);
    return $codigo;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar Productos desde Fotos</title>
    <?php include "includes/scripts.php"; ?>
    <style>
        .import-wrapper { padding: 15px 20px; max-width: 1200px; margin: 0 auto; }
        .import-header { border-bottom: 2px solid #286581; padding-bottom: 10px; margin-bottom: 20px; }
        .import-header h1 { color: #286581; font-size: 22px; }
        .import-header p { color: #555; font-size: 14px; margin-top: 5px; }

        .grupo-card {
            background: #fff; border: 1px solid #d0dde6; border-radius: 8px;
            margin-bottom: 16px; overflow: hidden;
            box-shadow: 0 2px 4px rgba(40,101,129,.06);
        }
        .grupo-header {
            background: #286581; color: #fff; padding: 10px 16px;
            display: flex; justify-content: space-between; align-items: center;
            font-size: 14px; font-weight: bold;
        }
        .grupo-header .badge {
            background: rgba(255,255,255,.2); padding: 2px 10px;
            border-radius: 12px; font-size: 12px;
        }
        .grupo-meta {
            background: #f0f6f9; padding: 8px 16px;
            font-size: 12px; color: #555;
            display: flex; gap: 20px; flex-wrap: wrap;
        }
        .grupo-meta span { font-weight: bold; color: #286581; }
        .grupo-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .grupo-table th { background: #eef5f8; text-align: left; padding: 6px 12px; font-size: 11px; color: #555; text-transform: uppercase; }
        .grupo-table td { padding: 5px 12px; border-bottom: 1px solid #eef; }
        .grupo-table tr:hover { background: #f9fcfe; }

        .btn-ejecutar {
            background: #27ae60; color: #fff; border: none; border-radius: 6px;
            padding: 12px 30px; font-size: 15px; cursor: pointer;
            font-weight: bold; margin: 20px 0;
        }
        .btn-ejecutar:hover { background: #219a52; }

        .result-ok  { color: #1a6e3c; background: #d5f5e3; padding: 2px 8px; border-radius: 4px; }
        .result-err { color: #922b21; background: #fde8e8; padding: 2px 8px; border-radius: 4px; }

        .summary-box {
            background: #fff; border: 2px solid #27ae60; border-radius: 8px;
            padding: 20px; margin: 20px 0; text-align: center;
        }
        .summary-box h2 { color: #27ae60; font-size: 24px; }
        .summary-box p  { color: #555; font-size: 14px; margin-top: 8px; }

        .warning-box {
            background: #fef9e7; border-left: 4px solid #f39c12;
            padding: 12px 16px; border-radius: 0 6px 6px 0;
            font-size: 13px; color: #7d6608; margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>
    <section id="container">
        <div class="import-wrapper">

            <div class="import-header">
                <h1><i class="fas fa-file-import"></i> Importar Productos desde Fotos</h1>
                <p>Total: <strong><?= $totalProductos ?></strong> productos en <strong><?= count($grupos) ?></strong> grupos listos para importar.</p>
            </div>

            <?php if ($modo === 'preview'): ?>
                <div class="warning-box">
                    <strong>⚠️ MODO VISTA PREVIA.</strong> Revisá todos los datos abajo antes de ejecutar.
                    Cuando estés seguro, hacé clic en el botón <strong>"Ejecutar Importación"</strong>.
                    Los proveedores y categorías que no existan se crearán automáticamente.
                </div>

                <a href="?ejecutar=1" onclick="return confirm('¿Estás seguro de importar los <?= $totalProductos ?> productos? Esta acción no se puede deshacer fácilmente.');">
                    <button class="btn-ejecutar"><i class="fas fa-play"></i> Ejecutar Importación (<?= $totalProductos ?> productos)</button>
                </a>
            <?php endif; ?>

            <?php
            $totalOk  = 0;
            $totalErr = 0;
            $logAll   = [];

            foreach ($grupos as $gIdx => $grupo):
                $numProds = count($grupo['productos']);

                if ($modo === 'ejecutar') {
                    // Buscar/crear proveedor, categoría y tamaño
                    $provId   = buscarOCrearProveedor($conection, $grupo['proveedor']);
                    $catId    = buscarOCrearCategoria($conection, $grupo['categoria']);
                    $tamanoId = buscarOCrearTamano($conection, $grupo['tamano']);
                }
            ?>
                <div class="grupo-card">
                    <div class="grupo-header">
                        <span><?= htmlspecialchars($grupo['titulo']) ?></span>
                        <span class="badge"><?= $numProds ?> producto<?= $numProds > 1 ? 's' : '' ?></span>
                    </div>
                    <div class="grupo-meta">
                        <div>Proveedor: <span><?= htmlspecialchars($grupo['proveedor'] ?: '(Sin asignar)') ?></span></div>
                        <div>Categoría: <span><?= htmlspecialchars($grupo['categoria'] ?: '(Sin asignar)') ?></span></div>
                        <?php if ($grupo['tamano']): ?>
                            <div>Tamaño: <span><?= htmlspecialchars($grupo['tamano']) ?></span></div>
                        <?php endif; ?>
                    </div>
                    <table class="grupo-table">
                        <tr>
                            <th>#</th>
                            <th>Nombre / Descripción</th>
                            <th>Cantidad</th>
                            <th>Costo</th>
                            <th>Precio</th>
                            <?php if ($modo === 'ejecutar'): ?><th>Estado</th><?php endif; ?>
                        </tr>
                        <?php foreach ($grupo['productos'] as $pIdx => $prod):
                            $status = '';
                            if ($modo === 'ejecutar') {
                                $desc   = mysqli_real_escape_string($conection, $prod['nombre']);
                                $cant   = (int)$prod['cantidad'];
                                $costo  = (float)$prod['costo'];
                                $precio = (float)$prod['precio'];
                                $codigo = generarCodigoUnico($conection);

                                $tamSql = ($tamanoId !== 'NULL') ? $tamanoId : 'NULL';
                                $sql = "INSERT INTO producto (proveedor, codigo, descripcion, costo, precio, existencia, usuario_id, foto, categoria_id, tamano_id)
                                        VALUES ('$provId', '$codigo', '$desc', '$costo', '$precio', '$cant', '$usuario_id', 'img_producto.png', $catId, $tamSql)";
                                $qIns = mysqli_query($conection, $sql);
                                if ($qIns) {
                                    $status = 'ok';
                                    $totalOk++;
                                    $logAll[] = "✔ {$prod['nombre']} → OK (cód. $codigo)";
                                } else {
                                    $status = 'err';
                                    $totalErr++;
                                    $logAll[] = "✘ {$prod['nombre']} → ERROR: " . mysqli_error($conection);
                                }
                            }
                        ?>
                            <tr>
                                <td><?= $pIdx + 1 ?></td>
                                <td><?= htmlspecialchars($prod['nombre']) ?></td>
                                <td><?= $prod['cantidad'] ?></td>
                                <td><?= number_format($prod['costo'], 0, ',', '.') ?></td>
                                <td><?= number_format($prod['precio'], 0, ',', '.') ?></td>
                                <?php if ($modo === 'ejecutar'): ?>
                                    <td><span class="result-<?= $status ?>"><?= $status === 'ok' ? '✔ OK' : '✘ Error' ?></span></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endforeach; ?>

            <?php if ($modo === 'ejecutar'): ?>
                <div class="summary-box">
                    <h2>✔ Importación Completada</h2>
                    <p><strong><?= $totalOk ?></strong> productos registrados correctamente.
                    <?php if ($totalErr > 0): ?>
                        <span style="color:#e74c3c;"><?= $totalErr ?> con errores.</span>
                    <?php endif; ?>
                    </p>
                    <p style="margin-top:12px;"><a href="lista_producto.php" style="color:#286581; font-weight:bold;">→ Ver Lista de Productos</a></p>
                </div>
            <?php else: ?>
                <a href="?ejecutar=1" onclick="return confirm('¿Importar los <?= $totalProductos ?> productos?');">
                    <button class="btn-ejecutar"><i class="fas fa-play"></i> Ejecutar Importación (<?= $totalProductos ?> productos)</button>
                </a>
            <?php endif; ?>

        </div>
    </section>
    <?php include "includes/footer.php"; ?>
</body>
</html>
