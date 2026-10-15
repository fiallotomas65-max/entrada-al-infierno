<?php
// funciones auxiliares que usan varios endpoints de la api
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// corta la ejecucion y devuelve un error en formato json
function error_json($mensaje, $codigo = 400) {
    http_response_code($codigo);
    echo json_encode(array("ok" => false, "mensaje" => $mensaje));
    exit;
}

// devuelve una respuesta exitosa en formato json
function ok_json($datos = array()) {
    $datos["ok"] = true;
    echo json_encode($datos);
    exit;
}

// true si el campo no viene vacio
function campo_obligatorio($valor) {
    return isset($valor) && trim($valor) != '';
}

// lee el cuerpo de la peticion cuando viene como JSON (fetch con body JSON)
function leer_json_body() {
    $texto = file_get_contents("php://input");
    $datos = json_decode($texto, true);
    return is_array($datos) ? $datos : array();
}
