<?php
// baja_usuario.php - elimina una credencial de la base de datos Y borra el usuario del switch.
//
// Mismo esquema con transacción:
//   1) Buscar usuario e IP de la credencial (después del DELETE ya no se podrían consultar)
//   2) DELETE en la BD (sin confirmar)
//   3) "no username X" en el switch por SSH
//   4) COMMIT si salió bien, ROLLBACK si falló
include 'conexion.php';
require 'switch_cli.php';

echo "<h3>Resultado eliminar usuario:</h3>";

if(isset($_POST['id_credencial'])) {

    $id_credencial = (int) $_POST['id_credencial'];

    // 1) Buscar el nombre de usuario y la IP del switch ANTES de borrar la fila
    $stmt = mysqli_prepare($conn,
    "SELECT c.usuario, s.ip
     FROM credenciales c
     INNER JOIN switches s ON s.id_switch = c.id_switch
     WHERE c.id_credencial = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_credencial);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $usuario, $ip);

    if (!mysqli_stmt_fetch($stmt)) {
        exit("No existe la credencial $id_credencial<br><a href='index.php'>Volver</a>");
    }
    mysqli_stmt_close($stmt);

    // Nunca borrar del switch la cuenta que usa la web para conectarse
    if (es_cuenta_de_servicio($usuario)) {
        exit("Esa cuenta es la que usa la web y no se puede eliminar desde el ABM.<br><a href='index.php'>Volver</a>");
    }

    // El usuario viene de la BD, pero igual se valida: va a la consola del switch, y la tabla
    // pudo haberse cargado a mano con algún dato raro
    if (!validar_usuario($usuario)) {
        exit("El usuario guardado tiene caracteres no permitidos, revisarlo a mano.<br><a href='index.php'>Volver</a>");
    }

    mysqli_begin_transaction($conn);

    try {
        // 2) DELETE (todavía sin confirmar)
        $stmt = mysqli_prepare($conn, "DELETE FROM credenciales WHERE id_credencial = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_credencial);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // 3) Borrar el usuario del switch.
        //    IOS pide confirmación ("[confirm]"); aplicar_en_switch() responde con Enter.
        aplicar_en_switch($ip, ["no username $usuario"]);

        // 4) Confirmar el borrado en la BD
        mysqli_commit($conn);
        echo "CREDENCIAL ELIMINADA de la base de datos y del switch $ip<br>Usuario: " . htmlspecialchars($usuario) . "<br>";

    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($conn);
        echo "FALLÓ: " . htmlspecialchars($e->getMessage()) . "<br>";

    } catch (Exception $e) {
        // Error del switch: la credencial vuelve a la BD porque en el switch sigue existiendo
        mysqli_rollback($conn);
        echo "No se pudo aplicar en el switch, no se eliminó nada:<br>" . htmlspecialchars($e->getMessage()) . "<br>";
    }
} else {
    echo "No se recibió ID para eliminar<br>";
}
mysqli_close($conn);
echo "<br><a href='index.php'>Volver</a>";
?>
