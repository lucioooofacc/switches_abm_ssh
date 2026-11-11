<?php
// alta_usuario.php - agrega una credencial en la base de datos Y crea el usuario en el switch.
//
// Para que la BD y el switch queden siempre iguales se usa una TRANSACCIÓN:
//   1) INSERT en la BD (todavía sin confirmar)
//   2) Crear el usuario en el switch por SSH
//   3) Si el switch respondió bien -> COMMIT (se confirma el INSERT)
//      Si algo falló               -> ROLLBACK (se deshace el INSERT, como si nunca hubiera pasado)
include 'conexion.php';
require 'switch_cli.php';   // aplicar_en_switch(), validar_usuario(), validar_password()...

echo "<h3>Resultado agregar usuario:</h3>";

if(isset($_POST['usuario']) && isset($_POST['password']) && isset($_POST['id_responsable']) && isset($_POST['id_switch'])) {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['password'];
    $id_responsable = (int)$_POST['id_responsable']; //Nos aseguramos de que sea un numero entero
    $id_switch = (int)$_POST['id_switch'];

    // Validamos ANTES de tocar la BD o el switch.
    // El usuario y la contraseña se escriben en la consola del switch, así que no pueden
    // tener espacios, saltos de línea ni '?' (ver switch_cli.php).
    if (!validar_usuario($usuario)) {
        exit("Usuario inválido: solo letras, números, '.', '-' y '_' (máx. 30).<br><a href='index.php'>Volver</a>");
    }
    if (!validar_password($contrasena)) {
        exit("Contraseña inválida: sin espacios ni '?' (máx. 64).<br><a href='index.php'>Volver</a>");
    }
    // No se permite crear un usuario con el mismo nombre que la cuenta de servicio:
    // en el switch eso le cambiaría la contraseña y la web perdería el acceso.
    if (es_cuenta_de_servicio($usuario)) {
        exit("Ese nombre de usuario está reservado para la web.<br><a href='index.php'>Volver</a>");
    }

    // Desde acá ningún cambio queda guardado hasta que hagamos commit
    mysqli_begin_transaction($conn);

    try {
        // 1) INSERT con consulta preparada (igual que antes)
        $stmt = mysqli_prepare($conn,
        "INSERT INTO credenciales (usuario, password, id_responsable, id_switch) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssii", $usuario, $contrasena, $id_responsable, $id_switch); // "ssii" indica que los dos primeros parámetros son strings y los dos últimos son enteros.
        mysqli_stmt_execute($stmt);   // si el usuario ya existe (1062) o la FK falla (1452) salta al catch
        mysqli_stmt_close($stmt);

        // 2) Buscar la IP del switch elegido, para saber a dónde conectarnos
        $stmt = mysqli_prepare($conn, "SELECT ip FROM switches WHERE id_switch = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_switch);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $ip);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // 3) Crear el usuario en el switch.
        //    - privilege 15: acceso completo (mismo nivel que tienen las credenciales actuales)
        //    - secret: el switch guarda la contraseña hasheada en su configuración (con "password"
        //      quedaría en texto plano o con un cifrado muy débil)
        aplicar_en_switch($ip, ["username $usuario privilege 15 secret $contrasena"]);

        // 4) Todo salió bien: confirmamos el INSERT
        mysqli_commit($conn);
        echo "CREDENCIAL AGREGADA en la base de datos y en el switch $ip<br>Usuario: " . htmlspecialchars($usuario) . "<br>";

    } catch (mysqli_sql_exception $e){
        // Error de la base de datos: deshacemos y explicamos el motivo.
        // Este catch va PRIMERO porque mysqli_sql_exception es un tipo más específico de Exception.
        mysqli_rollback($conn);
        if ($e->getCode() === 1062) echo "Ese usuario ya existe en ese switch<br>"; // UNIQUE switch_usuario
        elseif ($e->getCode() === 1452) echo "Ese switch o responsable no existe <br>"; //Falla la FK
        else echo "FALLÓ: " . htmlspecialchars($e->getMessage()) . "<br>";

    } catch (Exception $e) {
        // Error del switch (no conecta, timeout, comando rechazado...):
        // deshacemos el INSERT para que la BD no diga que existe un usuario que el switch no tiene.
        mysqli_rollback($conn);
        echo "No se pudo aplicar en el switch, no se guardó nada:<br>" . htmlspecialchars($e->getMessage()) . "<br>";
    }
} else {
    echo "No se recibieron todos los datos necesarios para agregar el usuario<br>";
}
mysqli_close($conn);
echo "<br><a href='index.php'>Volver</a>";
?>
