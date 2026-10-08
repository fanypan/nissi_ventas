<?php
// Simular sesión
session_start();
$_SESSION['idUser'] = 1;
$_SESSION['rol'] = 1;

// Simular POST
$_POST['action'] = 'procesarAjuste';
$_POST['producto_id'] = 1; // Ajustar si es necesario
$_POST['cantidad'] = 10;
$_POST['tipo'] = '1';
$_POST['motivo_id'] = 1;
$_POST['nota'] = 'Prueba depuración';

// Habilitar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "sistema/ajax.php";
?>
