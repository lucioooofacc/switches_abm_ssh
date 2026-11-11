<?php
// Archivo: config_switch.ejemplo.php
// Plantilla para crear php/config_switch.php con la cuenta "de servicio" que usa la web
// para CONFIGURAR los switches (alta, baja y modificación de usuarios).
//
// Pasos:
//   1) Copiar este archivo a php/config_switch.php
//   2) Completar usuario y contraseña reales
//
// Por qué una cuenta aparte y no una de la tabla credenciales:
//  - Tiene que tener privilegio 15 para poder entrar a "configure terminal".
//  - Si estuviera en la tabla, alguien podría borrarla o cambiarle la contraseña desde el ABM
//    y la web se quedaría sin forma de entrar a los switches.
//
// Esta cuenta se crea UNA sola vez, a mano, en cada switch. Por ejemplo:
//   SW(config)# username abm_web privilege 15 secret UnaClaveLarga
//   SW(config)# end
//   SW# write memory
//
// IMPORTANTE: php/config_switch.php está en .gitignore. Nunca subir la contraseña real a git.

$ADMIN_USER = "abm_web";
$ADMIN_PASS = "contraseña_de_la_cuenta_de_servicio";
?>
