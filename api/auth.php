<?php
// autenticacion y control de sesion para la api
require_once __DIR__ . '/funciones.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// true si hay un usuario con sesion iniciada
function esta_logueado() {
    return isset($_SESSION['id_usuario']);
}

// corta la ejecucion si no hay sesion iniciada
function requerir_login() {
    if (!esta_logueado()) {
        error_json("Debe iniciar sesion.", 401);
    }
}

// corta la ejecucion si el rol de la sesion no esta en la lista permitida
function requerir_rol($roles_permitidos) {
    requerir_login();
    if (!in_array($_SESSION['rol'], $roles_permitidos)) {
        error_json("No tiene permisos para esta accion.", 403);
    }
}

// valida usuario y contrasena, devuelve la fila del usuario o null
function verificar_usuario($usuario, $contrasena) {
    $con = conectar();
    $stmt = $con->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();

    if ($fila && password_verify($contrasena, $fila['contrasena'])) {
        return $fila;
    }
    return null;
}
