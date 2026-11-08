<?php
include 'conexion.php';

//Con este bloque de php traemos los datos de la base de datos antes de cargar el resto de la pagina

$switches = mysqli_query($conn, "SELECT id_switch, nombre, ip FROM switches ORDER BY nombre");
$responsables = mysqli_query($conn, "SELECT id_responsable, nombre_completo FROM responsables ORDER BY nombre_completo");

$res = mysqli_query($conn,
  "SELECT c.id_credencial, c.usuario, c.fecha_registro,
          s.nombre AS switch_nombre, s.ip, r.nombre_completo AS responsable
   FROM credenciales c
   JOIN switches s     ON s.id_switch = c.id_switch
   JOIN responsables r ON r.id_responsable = c.id_responsable
   ORDER BY s.nombre, c.usuario");

// fetch_all lo guarda en un array, así se puede recorrer varias veces (tabla + 2 selects).
$credenciales = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_close($conn);
?>



<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ABM de credenciales / Prueba semestral II </title>
</head>
<body>

<hr>
<h2>Alta de credencial</h2>
<form action="alta_usuario.php" method="POST">
  Switch:
  <select name="id_switch" required>
    <option value="">-- Elegir --</option>
    <!-- Mostramos las opciones con un while, para que se carguen todas las opciones de la tabla switches -->
    <?php while ($s = mysqli_fetch_assoc($switches)): ?>
      <option value="<?= $s['id_switch'] ?>"><?= htmlspecialchars($s['nombre'] . ' (' . $s['ip'] . ')') ?></option> 
    <?php endwhile; ?>
  </select><br>
  Responsable:
  <select name="id_responsable" required>
    <option value="">-- Elegir --</option>
    <?php while ($r = mysqli_fetch_assoc($responsables)): ?>
      <option value="<?= $r['id_responsable'] ?>"><?= htmlspecialchars($r['nombre_completo']) ?></option>
    <?php endwhile; ?>
  </select><br>
  Usuario: <input type="text" name="usuario" maxlength="30" required><br>
  Contraseña: <input type="text" name="password" maxlength="100" required><br>
  <input type="submit" value="Agregar">
</form>

<hr>
<h2>Baja de credencial</h2>
<form action="baja_usuario.php" method="POST">
  <select name="id_credencial" required>
    <option value="">-- Elegir --</option>
    <?php foreach ($credenciales as $c): ?>
      <option value="<?= $c['id_credencial'] ?>"><?= htmlspecialchars($c['usuario'] . ' @ ' . $c['switch_nombre']) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="submit" value="Eliminar">
</form>

<hr>
<h2>Modificar contraseña</h2>
<form action="modificar_usuario.php" method="POST">
  <select name="id_credencial" required>
    <option value="">-- Elegir --</option>
    <?php foreach ($credenciales as $c): ?>
      <option value="<?= $c['id_credencial'] ?>"><?= htmlspecialchars($c['usuario'] . ' @ ' . $c['switch_nombre']) ?></option>
    <?php endforeach; ?>
  </select><br>
  Nueva contraseña: <input type="text" name="password" maxlength="100" required><br>
  <input type="submit" value="Modificar">
</form>

<hr>
<a href="ssh_form.php">Ir a conexión SSH</a>
</body>
</html>
