<?php
//conexion_ssh.php - toma la credencial de la base de datos, se conecta por SSH al switch y ejecuta el comando
include 'conexion.php';
require __DIR__ . '/vendor/autoload.php'; // phpseclib instalada con composer

use phpseclib3\Net\SSH2;

//Solo se permiten estos comandos
//El <select> se puede alterar desde el navegador, por eso se vuelve a controlar del lado del servidor
$permitidos = ['show version', 'show ip interface brief', 'show vlan brief', 'show interfaces status'];

if(!isset($_POST['id_credencial'], $_POST['comando'])) {
    exit("Faltan datos. <a href='ssh_form.php'>Volver</a>");
}

//Si el comando que llega no esta dentro del array declarado anteriormente, mostramos un error.
$id_credencial = (int) $_POST['id_credencial'];
$comando = $_POST['comando'];
if(!in_array($comando, $permitidos, true)) {
    exit("Comando no permitido. <a href='ssh_form.php'>Volver</a>");
}

// 1) Buscar usuario, contraseña e IP en la base de datos con consulta preparada
$stmt = mysqli_prepare($conn,
"SELECT c.usuario, c.password, s.ip, s.nombre 
FROM credenciales c
INNER JOIN switches s ON c.id_switch = s.id_switch
WHERE c.id_credencial = ?");
mysqli_stmt_bind_param($stmt, "i", $id_credencial);
mysqli_stmt_execute($stmt);
//bind_result: guarda cada columna del SELECT en una variable, en el mismo orden que se declaran en la consulta
mysqli_stmt_bind_result($stmt, $usuario, $password, $ip, $nombre);

if(!mysqli_stmt_fetch($stmt)) { //fetch devuelve false si no hay fila
    exit("No se encontró la credencial. <a href='ssh_form.php'>Volver</a>");
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

// 2) Conectarse por SSH al switch

try{
    $ssh = new SSH2($ip, 22, 10); //IP, puerto y timeout
    if (!$ssh->login($usuario, $password)) {
        exit("Error: usuario o contraseña incorrectos para $usuario@$ip. <a href='ssh_form.php'>Volver</a>");
    }
    // 3) Ejecutar el comando y guardar lo que responde el switch
    $salida = $ssh->exec($comando);
    $ssh->disconnect();
} catch (Exception $e) {
    // Por ejemplo: el switch no responde o no hay ruta hasta él
    exit("No se pudo conectar a $ip: " . htmlspecialchars($e->getMessage()) . " <a href='ssh_form.php'>Volver</a>");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado SSH</title>
</head>
<body>
    <h2><?= htmlspecialchars("$nombre ($ip) - $usuario") ?></h2>
  <p>Comando: <code><?= htmlspecialchars($comando) ?></code></p>
  <pre><?= htmlspecialchars($salida) ?></pre>
  <a href="ssh_form.php">Ejecutar otro comando</a> | <a href="index.php">Volver al ABM</a>
</body>
</html>