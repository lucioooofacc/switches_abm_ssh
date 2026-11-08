<?php
//ssh_form.php - elige credencial (que ya implica switch) y comando
require 'conexion.php';

$sql = "SELECT c.id_credencial, s.nombre, s.ip, c.usuario
        FROM credenciales c
        INNER JOIN switches s ON c.id_switch = s.id_switch
        ORDER BY s.nombre, c.usuario";
$res = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conexion SSH</title>
</head>
<body>
    <h1>Ejecutar comando en un switch</h1>
    <form action="conexion_ssh.php" method="POST">
        <label>Dispositivo / Credencial: 
            <select name="id_credencial" required>
                <?php while ($r = $res->fetch_assoc()): ?>
                    <option value="<?= $r['id_credencial'] ?>">
                        <?= htmlspecialchars("{$r['nombre']} ({$r['ip']}) - {$r['usuario']}") ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </label>

        <label>Comando:
            <select name="comando">
                <option>show version</option>
                <option>show ip interface brief</option>
                <option>show vlan brief</option>
                <option>show interfaces status</option>
            </select>
        </label>
        <button type="submit">Ejecutar</button>
    </form>
    <p><a href="index.php">Volver al ABM</a></p>
</body>
</html>