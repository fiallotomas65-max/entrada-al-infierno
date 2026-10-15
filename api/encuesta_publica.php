<?php
// api publica de encuestas: no requiere sesion, la usa el paciente desde el QR
require_once __DIR__ . '/funciones.php';

$con = conectar();
$metodo = $_SERVER["REQUEST_METHOD"];

// ------------------------------------------------
// GET: la encuesta activa, con sus preguntas y opciones
// ------------------------------------------------
if ($metodo == "GET") {
    $encuesta = $con->query("SELECT * FROM encuestas WHERE estado = 'Activa' ORDER BY id_encuesta ASC LIMIT 1")->fetch_assoc();

    if (!$encuesta) {
        ok_json(array("encuesta" => null));
    }

    $preguntas = array();
    $stmt = $con->prepare("SELECT * FROM preguntas WHERE id_encuesta = ? ORDER BY id_pregunta ASC");
    $stmt->bind_param("i", $encuesta["id_encuesta"]);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $preguntas[] = $fila;
    }

    foreach ($preguntas as &$pregunta) {
        $pregunta["opciones"] = array();
        if ($pregunta["tipo"] == "cerrada") {
            $stmt = $con->prepare("SELECT id_opcion, texto FROM opciones WHERE id_pregunta = ? ORDER BY id_opcion ASC");
            $stmt->bind_param("i", $pregunta["id_pregunta"]);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($op = $res->fetch_assoc()) {
                $pregunta["opciones"][] = $op;
            }
        }
    }

    $encuesta["preguntas"] = $preguntas;
    ok_json(array("encuesta" => $encuesta));
}

// ------------------------------------------------
// POST: el paciente envia sus respuestas (siempre anonimas)
// ------------------------------------------------
if ($metodo == "POST") {
    $datos = leer_json_body();
    $id_encuesta = isset($datos["id_encuesta"]) ? $datos["id_encuesta"] : "";
    $respuestas = isset($datos["respuestas"]) ? $datos["respuestas"] : array();

    if (!is_numeric($id_encuesta)) {
        error_json("Encuesta no valida.");
    }

    $stmt = $con->prepare("SELECT id_encuesta FROM encuestas WHERE id_encuesta = ? AND estado = 'Activa'");
    $stmt->bind_param("i", $id_encuesta);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        error_json("Esta encuesta ya no esta disponible.");
    }

    $fecha_actual = date("Y-m-d H:i:s");
    $stmt = $con->prepare("INSERT INTO respuestas (id_encuesta, fecha_hora) VALUES (?, ?)");
    $stmt->bind_param("is", $id_encuesta, $fecha_actual);
    $stmt->execute();
    $id_respuesta = $stmt->insert_id;

    $guardadas = 0;
    $stmt = $con->prepare("INSERT INTO respuestas_detalle (id_respuesta, id_pregunta, id_opcion, texto_libre) VALUES (?, ?, ?, ?)");
    foreach ($respuestas as $r) {
        $id_pregunta = isset($r["id_pregunta"]) ? $r["id_pregunta"] : "";
        $id_opcion = isset($r["id_opcion"]) && $r["id_opcion"] != "" ? $r["id_opcion"] : null;
        $texto_libre = isset($r["texto_libre"]) ? trim($r["texto_libre"]) : "";

        if (!is_numeric($id_pregunta)) {
            continue;
        }
        if ($id_opcion === null && $texto_libre == "") {
            continue; // no contesto esta pregunta
        }

        $stmt->bind_param("iiis", $id_respuesta, $id_pregunta, $id_opcion, $texto_libre);
        $stmt->execute();
        $guardadas++;
    }

    // si no contesto nada, no queda un envio vacio ensuciando los indicadores
    if ($guardadas == 0) {
        $stmt = $con->prepare("DELETE FROM respuestas WHERE id_respuesta = ?");
        $stmt->bind_param("i", $id_respuesta);
        $stmt->execute();
    }

    ok_json(array("mensaje" => "Gracias por responder la encuesta."));
}

error_json("Solicitud no reconocida.");
