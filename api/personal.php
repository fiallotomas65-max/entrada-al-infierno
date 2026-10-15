<?php
// api del personal de transporte (conductores, enfermeras, auxiliares)
require_once __DIR__ . '/auth.php';
requerir_rol(array("Administrador", "Coordinador"));

$con = conectar();
$metodo = $_SERVER["REQUEST_METHOD"];

if ($metodo == "GET") {
    $listado = array();
    $res = $con->query("SELECT * FROM personal ORDER BY apellido ASC, nombre ASC");
    while ($fila = $res->fetch_assoc()) {
        $listado[] = $fila;
    }
    ok_json(array("personal" => $listado));
}

$datos = leer_json_body();
$accion = isset($datos["accion"]) ? $datos["accion"] : "guardar";

if ($metodo == "POST" && $accion == "guardar") {
    $id = isset($datos["id_personal"]) ? $datos["id_personal"] : "";
    $nombre = isset($datos["nombre"]) ? trim($datos["nombre"]) : "";
    $apellido = isset($datos["apellido"]) ? trim($datos["apellido"]) : "";
    $documento = isset($datos["documento"]) ? trim($datos["documento"]) : "";
    $cargo = isset($datos["cargo"]) ? $datos["cargo"] : "";
    $telefono = isset($datos["telefono"]) ? trim($datos["telefono"]) : "";

    $cargos_validos = array("Conductor", "Enfermera", "Auxiliar");
    if (!campo_obligatorio($nombre) || !campo_obligatorio($apellido) ||
        !campo_obligatorio($documento) || !in_array($cargo, $cargos_validos)) {
        error_json("Complete nombre, apellido, documento y un cargo valido.");
    }

    $stmt = $con->prepare("SELECT id_personal FROM personal WHERE documento = ?");
    $stmt->bind_param("s", $documento);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    if ($fila && ($id == "" || $fila["id_personal"] != $id)) {
        error_json("Ya hay una persona con ese documento.");
    }

    if ($id != "" && is_numeric($id)) {
        $stmt = $con->prepare("UPDATE personal SET nombre=?, apellido=?, documento=?, cargo=?, telefono=? WHERE id_personal=?");
        $stmt->bind_param("sssssi", $nombre, $apellido, $documento, $cargo, $telefono, $id);
        $stmt->execute();
        ok_json(array("mensaje" => "Personal actualizado correctamente."));
    } else {
        $stmt = $con->prepare("INSERT INTO personal (nombre, apellido, documento, cargo, telefono) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nombre, $apellido, $documento, $cargo, $telefono);
        $stmt->execute();
        ok_json(array("mensaje" => "Personal guardado correctamente."));
    }
}

if ($metodo == "POST" && $accion == "eliminar") {
    $id = isset($datos["id_personal"]) ? $datos["id_personal"] : "";
    if (!is_numeric($id)) {
        error_json("Debe indicar una persona valida.");
    }

    $stmt = $con->prepare("SELECT COUNT(*) AS total FROM traslados WHERE id_conductor = ? OR id_copiloto = ?");
    $stmt->bind_param("ii", $id, $id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()["total"];

    if ($total >= 1) {
        error_json("No se puede eliminar: la persona participo en " . $total . " traslados.");
    }

    $stmt = $con->prepare("DELETE FROM personal WHERE id_personal = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    ok_json(array("mensaje" => "Persona eliminada correctamente."));
}

error_json("Solicitud no reconocida.");
