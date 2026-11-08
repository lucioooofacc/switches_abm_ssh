<?php
// Archivo: conexion.ejemplo.php
// Este archivo se usa de plantilla para crear el archivo conexion.php con las credenciales propias

$host = "localhost";
$user = "nombre_de_usuario";
$pass = "contraseña";
$db = "nombre_de_base_de_datos";

$conn = mysqli_connect($host, $user, $pass, $db);
//mostrar error si falla la conexion
if (!$conn) {
    die("No se pudo conectar: " . mysqli_connect_error());
}
?>
