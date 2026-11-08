<?php
include 'conexion.php';

echo "<h3>Resultado agregar usuario:</h3>";

if(isset($_POST['usuario']) && isset($_POST['password']) && isset($_POST['id_responsable']) && isset($_POST['id_switch'])) {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['password'];
    $id_responsable = (int)$_POST['id_responsable']; //Nos aseguramos de que sea un numero entero
    $id_switch = (int)$_POST['id_switch'];
 
    $stmt = mysqli_prepare($conn,
    "INSERT INTO credenciales (usuario, password, id_responsable, id_switch) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssii", $usuario, $contrasena, $id_responsable, $id_switch); // "ssii" indica que los dos primeros parámetros son strings y los dos últimos son enteros.

    try {
        mysqli_stmt_execute($stmt);
        echo "CREDENCIAL AGREGADA<br>Usuario: " . htmlspecialchars($usuario) . "<br>";
    } catch (mysqli_sql_exception $e){
        if ($e->getCode() === 1062) echo "Ese usuario ya existe en ese switch<br>"; // UNIQUE switch_usuario
        elseif ($e->getCode() === 1452) echo "Ese switch o responsable no existe <br>"; //Falla la FK
        else echo "FALLÓ: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "No se recibieron todos los datos necesarios para agregar el usuario<br>";
}
mysqli_close($conn);
echo "<br><a href='index.php'>Volver</a>";
?>