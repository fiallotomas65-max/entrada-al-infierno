<?php
// api de preguntas y opciones de respuesta de una encuesta
require_once __DIR__ . '/auth.php';
requerir_rol(array("Administrador", "Recepcionista"));

$con = conectar();
$metodo = $_SERVER["REQUEST_METHOD"];

// ------------------------------------------------
// GET ?id_encuesta=x : preguntas de esa encuesta con sus opciones
// ------------------------------------------------
if ($metodo == "GET") {
    $id_encuesta = isset($_GET["id_encuesta"]) ? $_GET["id_encuesta"] : "";
    if (!is_numeric($id_encuesta)) {
        error_json("Debe indicar una encuesta valida.");
    }

    $preguntas = array();
    $stmt = $con->prepare("SELECT * FROM preguntas WHERE id_encuesta = ? ORDER BY id_pregunta ASC");
    $stmt->bind_param("i", $id_encuesta);
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

    ok_json(array("preguntas" => $preguntas));
}

$datos = leer_json_body();

// ------------------------------------------------
// POST accion=crear: nueva pregunta (con sus opciones si es cerrada)
// ------------------------------------------------
if ($metodo == "POST" && isset($datos["accion"]) && $datos["accion"] == "crear") {
    $id_encuesta = isset($datos["id_encuesta"]) ? $datos["id_encuesta"] : "";
    $texto = isset($datos["texto"]) ? trim($datos["texto"]) : "";
    $tipo = isset($datos["tipo"]) ? $datos["tipo"] : "";
    $opciones_texto = isset($datos["opciones"]) ? trim($datos["opciones"]) : "";

    if (!is_numeric($id_encuesta) || !campo_obligatorio($texto) ||
        ($tipo != "cerrada" && $tipo != "abierta")) {
        error_json("Revise los datos de la pregunta.");
    }

    // la encuesta tiene que existir de verdad, para no llegar a un error de clave foranea
    $stmt = $con->prepare("SELECT COUNT(*) AS total FROM encuestas WHERE id_encuesta = ?");
    $stmt->bind_param("i", $id_encuesta);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()["total"] == 0) {
        error_json("La encuesta no existe.");
    }

    $lista_opciones = array();
    if ($tipo == "cerrada") {
        foreach (explode(";", $opciones_texto) as $opcion) {
            $opcion = trim($opcion);
            if ($opcion != "") {
                $lista_opciones[] = $opcion;
            }
        }
        if (count($lista_opciones) < 2) {
            error_json("Una pregunta cerrada necesita al menos dos opciones, separadas por punto y coma.");
        }
    }

    $stmt = $con->prepare("INSERT INTO preguntas (id_encuesta, texto, tipo) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $id_encuesta, $texto, $tipo);
    $stmt->execute();
    $id_pregunta = $stmt->insert_id;

    if ($tipo == "cerrada") {
        $stmt = $con->prepare("INSERT INTO opciones (id_pregunta, texto) VALUES (?, ?)");
        foreach ($lista_opciones as $opcion) {
            $stmt->bind_param("is", $id_pregunta, $opcion);
            $stmt->execute();
        }
    }

    ok_json(array("mensaje" => "Pregunta guardada correctamente."));
}

// ------------------------------------------------
// POST accion=eliminar: borra la pregunta y lo que depende de ella
// ------------------------------------------------
if ($metodo == "POST" && isset($datos["accion"]) && $datos["accion"] == "eliminar") {
    $id = isset($datos["id_pregunta"]) ? $datos["id_pregunta"] : "";
    if (!is_numeric($id)) {
        error_json("Debe indicar una pregunta valida.");
    }

    // se borra en este orden por las claves foraneas
    $stmt = $con->prepare("DELETE FROM respuestas_detalle WHERE id_pregunta = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt = $con->prepare("DELETE FROM opciones WHERE id_pregunta = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt = $con->prepare("DELETE FROM preguntas WHERE id_pregunta = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    ok_json(array("mensaje" => "Pregunta eliminada correctamente."));
}

error_json("Solicitud no reconocida.");
