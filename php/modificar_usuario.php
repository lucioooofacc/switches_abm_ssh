<?php
// modificar_usuario.php - cambia la contraseña de una credencial en la base de datos Y en el switch.
//
// Mismo esquema que el alta, con transacción:
//   1) Buscar usuario e IP de la credencial (el formulario solo manda el id)
//   2) UPDATE en la BD (sin confirmar)
//   3) Cambiar la contraseña en el switch por SSH
//   4) COMMIT si salió bien, ROLLBACK si falló
include 'conexion.php';
require 'switch_cli.php';

echo "<h3>Resultado modificar usuario:</h3>";

if(isset($_POST['id_credencial']) && isset($_POST['password'])) {

    $id = (int) $_POST['id_credencial'];
    $password = $_POST['password'];

    // La nueva contraseña también va a la consola del switch: se valida igual que en el alta
    if (!validar_password($password)) {
        exit("Contraseña inválida: sin espacios ni '?' (máx. 64).<br><a href='index.php'>Volver</a>");
    }

    // 1) Buscar el nombre de usuario y la IP del switch de esa credencial
    $stmt = mysqli_prepare($conn,
    "SELECT c.usuario, s.ip
     FROM credenciales c
     INNER JOIN switches s ON s.id_switch = c.id_switch
     WHERE c.id_credencial = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $usuario, $ip);   // columnas del SELECT, en el mismo orden

    if (!mysqli_stmt_fetch($stmt)) {                  // fetch devuelve false si no hay fila
        exit("No existe la credencial $id<br><a href='index.php'>Volver</a>");
    }
    mysqli_stmt_close($stmt);

    // Por las dudas: si la cuenta de servicio quedó cargada en la tabla, no se toca desde acá
    if (es_cuenta_de_servicio($usuario)) {
        exit("Esa cuenta es la que usa la web y no se puede modificar desde el ABM.<br><a href='index.php'>Volver</a>");
    }

    mysqli_begin_transaction($conn);

    try {
        // 2) UPDATE (todavía sin confirmar)
        $stmt = mysqli_prepare($conn, "UPDATE credenciales SET password = ? WHERE id_credencial = ?");
        mysqli_stmt_bind_param($stmt, "si", $password, $id);   // el orden es el de los ?
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // 3) En Cisco IOS, volver a ejecutar "username X ... secret Y" sobre un usuario que ya
        //    existe le reemplaza la contraseña. Si el usuario no existiera en el switch, lo crea,
        //    así que también sirve para volver a sincronizar BD y switch.
        //    Se aplica aunque la contraseña sea la misma que antes, por ese mismo motivo.
        aplicar_en_switch($ip, ["username $usuario privilege 15 secret $password"]);

        // 4) Confirmar el cambio en la BD
        mysqli_commit($conn);
        echo "CONTRASEÑA MODIFICADA en la base de datos y en el switch $ip<br>Usuario: " . htmlspecialchars($usuario) . "<br>";

    } catch (mysqli_sql_exception $e) {
        // Error de la base de datos
        mysqli_rollback($conn);
        echo "FALLÓ: " . htmlspecialchars($e->getMessage()) . "<br>";

    } catch (Exception $e) {
        // Error del switch: la BD vuelve a la contraseña anterior, que es la que sigue en el switch
        mysqli_rollback($conn);
        echo "No se pudo aplicar en el switch, no se modificó nada:<br>" . htmlspecialchars($e->getMessage()) . "<br>";
    }
} else {
    echo "Faltan datos para modificar<br>";
}

mysqli_close($conn);
echo "<br><a href='index.php'>Volver</a>";
?>
