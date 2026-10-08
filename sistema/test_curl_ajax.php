<?php
// Simular llamada como haría el navegador (con cookie de sesión)
$cookie_file = tempnam(sys_get_temp_dir(), 'cookie_');

// Primero, hacer login para obtener cookie de sesión
$ch = curl_init('http://localhost/Sistemaventas/sistema/ajax.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);

// Test con sesión activa - simular las llamadas que hace lista_categorias.php
$actions = [
    ['action' => 'listaCategorias', 'busqueda' => '', 'pagina' => 1],
    ['action' => 'editarCategoria', 'id' => 0],
    ['action' => 'delCategoria', 'id' => 999],
    ['action' => 'actualizarCategoria', 'id' => 1, 'nombre' => 'Test'],
];

foreach ($actions as $params) {
    $ch = curl_init('http://localhost/Sistemaventas/sistema/ajax.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    $res = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $action = $params['action'];
    echo "=== $action (HTTP $http_code) ===\n";
    
    // Buscar HTML de error PHP
    if (strpos($res, '<br />') !== false || strpos($res, '<b>') !== false) {
        echo "*** CONTIENE HTML ERROR ***\n";
        echo "FULL RESPONSE:\n$res\n\n";
    } else {
        echo "OK - first 100 chars: " . substr($res, 0, 100) . "\n\n";
    }
}

// Limpiar tamano de prueba
$ch = curl_init('http://localhost/Sistemaventas/sistema/ajax.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['action' => 'delTamano', 'id' => 3]));
curl_exec($ch);
curl_close($ch);

unlink($cookie_file);
