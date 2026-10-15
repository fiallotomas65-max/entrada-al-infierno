<?php
// api de ambulancias
require_once __DIR__ . '/auth.php';
requerir_rol(array("Administrador", "Coordinador"));

$con = conectar();
$metodo = $_SERVER["REQUEST_METHOD"];

if ($metodo == "GET") {
    $listado = array();
    $res = $con->query("SELECT * FROM ambulancias ORDER BY patente ASC");
    while ($fila = $res->fetch_assoc()) {
        $listado[] = $fila;
    }
    ok_json(array("ambulancias" => $listado));
}

$datos = leer_json_body();
$accion = isset($datos["accion"]) ? $datos["accion"] : "guardar";

// ------------------------------------------------
// guardar: alta o edicion
// ------------------------------------------------
if ($metodo == "POST" && $accion == "guardar") {
    $id = isset($datos["id_ambulancia"]) ? $datos["id_ambulancia"] : "";
    $patente = isset($datos["patente"]) ? trim($datos["patente"]) : "";
    $modelo = isset($datos["modelo"]) ? trim($datos["modelo"]) : "";
    $estado = isset($datos["estado"]) ? $datos["estado"] : "";

    $estados_validos = array("Disponible", "En servicio", "Mantenimiento");
    if (!campo_obligatorio($patente) || !campo_obligatorio($modelo) || !in_array($estado, $estados_validos)) {
        error_json("Complete la patente, el modelo y un estado valido.");
    }

    // la patente es unica
    $stmt = $con->prepare("SELECT id_ambulancia FROM ambulancias WHERE patente = ?");
    $stmt->bind_param("s", $patente);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    if ($fila && ($id == "" || $fila["id_ambulancia"] != $id)) {
        error_json("Ya hay una ambulancia con esa patente.");
    }

    if ($id != "" && is_numeric($id)) {
        $stmt = $con->prepare("UPDATE ambulancias SET patente = ?, modelo = ?, estado = ? WHERE id_ambulancia = ?");
        $stmt->bind_param("sssi", $patente, $modelo, $estado, $id);
        $stmt->execute();
        ok_json(array("mensaje" => "Ambulancia actualizada correctamente."));
    } else {
        $stmt = $con->prepare("INSERT INTO ambulancias (patente, modelo, estado) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $patente, $modelo, $estado);
        $stmt->execute();
        ok_json(array("mensaje" => "Ambulancia guardada correctamente."));
    }
}

// ------------------------------------------------
// eliminar: se bloquea si la ambulancia ya tiene traslados (es historial operativo)
// ------------------------------------------------
if ($metodo == "POST" && $accion == "eliminar") {
    $id = isset($datos["id_ambulancia"]) ? $datos["id_ambulancia"] : "";
    if (!is_numeric($id)) {
        error_json("Debe indicar una ambulancia valida.");
    }

    $stmt = $con->prepare("SELECT COUNT(*) AS total FROM traslados WHERE id_ambulancia = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()["total"];

    if ($total >= 1) {
        error_json("No se puede eliminar: la ambulancia tiene " . $total . " traslados registrados. Cambiele el estado en su lugar.");
    }

    $stmt = $con->prepare("DELETE FROM ambulancias WHERE id_ambulancia = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    ok_json(array("mensaje" => "Ambulancia eliminada correctamente."));
}

error_json("Solicitud no reconocida.");
