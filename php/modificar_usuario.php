<?php
include 'conexion.php';

echo "<h3>Resultado modificar usuario:</h3>";

if(isset($_POST['id_credencial']) && isset($_POST['password'])) {
 
    $id = (int) $_POST['id_credencial'];
    $password = $_POST['password'];
    $stmt = mysqli_prepare($conn, "UPDATE credenciales SET password = ? WHERE id_credencial = ?");
    mysqli_stmt_bind_param($stmt, "si", $password, $id);   // el orden es el de los ?
    
try {
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) echo "CONTRASEÑA MODIFICADA<br>ID: $id<br>";
        else echo "No existe la credencial $id o la contraseña es la misma<br>";
    } catch (mysqli_sql_exception $e) {
        echo "FALLÓ: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "Faltan datos para modificar<br>";
}

mysqli_close($conn);
echo "<br><a href='index.php'>Volver</a>";
?>
