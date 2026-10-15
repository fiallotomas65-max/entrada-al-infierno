<?php
// le dice al frontend si hay una sesion activa y los datos basicos del usuario
require_once __DIR__ . '/auth.php';

if (esta_logueado()) {
    ok_json(array(
        "logueado" => true,
        "nombre" => $_SESSION['nombre'],
        "rol" => $_SESSION['rol']
    ));
} else {
    ok_json(array("logueado" => false));
}
