<?php
// procesa el ingreso al sistema
require_once __DIR__ . '/auth.php';

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    error_json("Metodo no permitido.", 405);
}

$datos = leer_json_body();
$usuario = isset($datos["usuario"]) ? trim($datos["usuario"]) : "";
$contrasena = isset($datos["contrasena"]) ? $datos["contrasena"] : "";

if (!campo_obligatorio($usuario) || !campo_obligatorio($contrasena)) {
    error_json("Complete usuario y contrasena.");
}

$fila = verificar_usuario($usuario, $contrasena);

if (!$fila) {
    error_json("Usuario o contrasena incorrectos.");
}

// se guarda lo minimo necesario en la sesion
$_SESSION['id_usuario'] = $fila['id_usuario'];
$_SESSION['nombre'] = $fila['nombre'] . ' ' . $fila['apellido'];
$_SESSION['rol'] = $fila['rol'];

ok_json(array("nombre" => $_SESSION['nombre'], "rol" => $_SESSION['rol']));
