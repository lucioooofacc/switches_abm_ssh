<?php
// switch_cli.php - funciones compartidas para CONFIGURAR un switch Cisco por SSH.
//
// Lo usan alta_usuario.php, baja_usuario.php y modificar_usuario.php para que cada cambio
// del ABM también se aplique en el switch (no solo en la base de datos).
//
// Diferencia con conexion_ssh.php:
//  - conexion_ssh.php usa $ssh->exec(): manda UN comando "show ..." y devuelve la salida.
//  - Acá necesitamos una sesión INTERACTIVA: entrar a "configure terminal", mandar comandos,
//    salir y guardar, todo en la misma conexión. Para eso se usan write() y read(), que
//    funcionan como si escribiéramos en la consola y leyéramos lo que responde el switch.

require __DIR__ . '/vendor/autoload.php';   // phpseclib instalada con composer
require __DIR__ . '/config_switch.php';     // define $ADMIN_USER y $ADMIN_PASS (cuenta de servicio)

use phpseclib3\Net\SSH2;

// VALIDACIONES
// El usuario y la contraseña se escriben directamente en la consola del switch.
// Si alguien mete un salto de línea, podría "agregar" comandos propios (inyección de comandos).
// Por eso se valida con listas BLANCAS (solo se acepta lo que está explícitamente permitido).

// Usuario: letras, números, guion, guion bajo y punto. Entre 1 y 30 caracteres
// (30 es el largo de la columna usuario en la tabla credenciales).
function validar_usuario($usuario) {
    return preg_match('/^[A-Za-z0-9_.-]{1,30}$/', $usuario) === 1;
}

// Contraseña: caracteres ASCII imprimibles SIN espacio (\x21 a \x7E), de 1 a 64.
// Se excluye además el '?' porque en Cisco IOS, al escribir '?', la consola muestra la ayuda
// en lugar de tomarlo como parte de la contraseña.
function validar_password($password) {
    return preg_match('/^[\x21-\x7E]{1,64}$/', $password) === 1
        && strpos($password, '?') === false;
}

// Evita que desde el ABM se cree, modifique o borre la cuenta que usa la propia web.
// Si eso pasara, la web podría perder el acceso a los switches.
function es_cuenta_de_servicio($usuario) {
    global $ADMIN_USER;
    return strcasecmp($usuario, $ADMIN_USER) === 0;
}

// FUNCIÓN PRINCIPAL
// Se conecta al switch con la cuenta de servicio, entra en modo configuración, ejecuta los
// comandos recibidos, sale y guarda la configuración.
// Si algo falla lanza una Exception: el archivo que la llama hace rollback en la base de datos,
// así la BD y el switch nunca quedan distintos.
function aplicar_en_switch($ip, array $comandos) {
    global $ADMIN_USER, $ADMIN_PASS;

    // Expresión regular del prompt: la línea termina en ">" o "#"
    // Ejemplos: "SW-3>", "SW-3#", "SW-3(config)#"
    $prompt = '/[>#]\s*$/';

    // 1) Conectarse e iniciar sesión (IP, puerto 22, 10 segundos de timeout)
    $ssh = new SSH2($ip, 22, 10);
    if (!$ssh->login($ADMIN_USER, $ADMIN_PASS)) {
        throw new Exception("La cuenta de servicio no pudo iniciar sesión en $ip");
    }

    // Tiempo máximo que esperamos cada respuesta del switch
    $ssh->setTimeout(5);

    // 2) Esperar el primer prompt y comprobar el nivel de privilegio.
    //    Con privilegio 15 el prompt termina en "#". Si termina en ">" la cuenta no puede configurar.
    $inicio = esperar($ssh, $prompt);
    if (!preg_match('/#\s*$/', $inicio)) {
        $ssh->disconnect();
        throw new Exception("La cuenta de servicio no tiene privilegio 15 en $ip (prompt '>')");
    }

    // 3) Entrar en modo configuración global
    $ssh->write("configure terminal\n");
    esperar($ssh, $prompt);

    // 4) Ejecutar cada comando
    foreach ($comandos as $cmd) {
        $ssh->write($cmd . "\n");

        // Esperamos el prompt O el texto "[confirm]".
        // "no username X" en IOS pregunta: "Do you want to continue? [confirm]"
        $salida = esperar($ssh, '/[>#]\s*$|\[confirm\]/');

        if (strpos($salida, '[confirm]') !== false) {
            $ssh->write("\n");                 // Enter = confirmar
            $salida .= esperar($ssh, $prompt);
        }

        // IOS marca los errores empezando la línea con "%",
        // por ejemplo "% Invalid input detected at '^' marker."
        if (preg_match_all('/^\s*%.*$/m', $salida, $errores)) {
            $ssh->disconnect();
            // Solo mostramos las líneas de error ("% ..."). No mostramos $salida completa porque
            // el switch repite (eco) el comando que escribimos, y ese comando tiene la contraseña.
            throw new Exception("El switch $ip rechazó el comando: " . trim(implode(' ', $errores[0])));
        }
    }

    // 5) Salir del modo configuración y guardar.
    //    Sin "write memory" el cambio queda solo en la running-config y se pierde al reiniciar.
    $ssh->write("end\n");
    esperar($ssh, $prompt);

    $ssh->setTimeout(15);                      // guardar puede tardar algunos segundos más
    $ssh->write("write memory\n");
    esperar($ssh, $prompt);

    $ssh->disconnect();
}

// Lee la salida del switch hasta encontrar el patrón (normalmente el prompt).
// Si se cumple el timeout sin encontrarlo, lanza una Exception en lugar de seguir "a ciegas".
function esperar(SSH2 $ssh, $patron) {
    $salida = $ssh->read($patron, SSH2::READ_REGEX);
    if ($ssh->isTimeout()) {
        $ssh->disconnect();
        throw new Exception("El switch no respondió a tiempo");
    }
    return (string) $salida;
}
?>
