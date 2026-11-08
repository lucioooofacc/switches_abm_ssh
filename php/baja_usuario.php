<?php
include 'conexion.php';

echo "<h3>Resultado eliminar usuario:</h3>";

if(isset($_POST['id_credencial'])) {
 
    $id_credencial = (int) $_POST['id_credencial'];
    $stmt = mysqli_prepare($conn, "DELETE FROM credenciales WHERE id_credencial = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_credencial);
 
try {
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) echo "CREDENCIAL ELIMINADA<br>ID: $id_credencial<br>";
        else echo "No existe la credencial $id_credencial<br>";
    } catch (mysqli_sql_exception $e) {
        echo "FALLÓ: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "No se recibió ID para eliminar<br>";
}
mysqli_close($conn);
echo "<br><a href='index.php'>Volver</a>";
?>
