<?php
// resultados agregados de una encuesta, solo lectura
require_once __DIR__ . '/auth.php';
requerir_rol(array("Administrador", "Recepcionista"));

$con = conectar();

$id_encuesta = isset($_GET["id_encuesta"]) ? $_GET["id_encuesta"] : "";
if (!is_numeric($id_encuesta)) {
    error_json("Debe indicar una encuesta valida.");
}

$stmt = $con->prepare("SELECT COUNT(*) AS total FROM respuestas WHERE id_encuesta = ?");
$stmt->bind_param("i", $id_encuesta);
$stmt->execute();
$total_respuestas = $stmt->get_result()->fetch_assoc()["total"];

$preguntas = array();
$stmt = $con->prepare("SELECT id_pregunta, texto, tipo FROM preguntas WHERE id_encuesta = ? ORDER BY id_pregunta ASC");
$stmt->bind_param("i", $id_encuesta);
$stmt->execute();
$res = $stmt->get_result();
while ($fila = $res->fetch_assoc()) {
    $preguntas[] = $fila;
}

foreach ($preguntas as &$pregunta) {
    if ($pregunta["tipo"] == "cerrada") {
        $opciones = array();
        $stmt = $con->prepare("SELECT o.texto, COUNT(rd.id_detalle) AS veces
                               FROM opciones o
                               LEFT JOIN respuestas_detalle rd ON rd.id_opcion = o.id_opcion
                               WHERE o.id_pregunta = ?
                               GROUP BY o.id_opcion, o.texto
                               ORDER BY o.id_opcion ASC");
        $stmt->bind_param("i", $pregunta["id_pregunta"]);
        $stmt->execute();
        $res = $stmt->get_result();
        $total_pregunta = 0;
        while ($op = $res->fetch_assoc()) {
            $opciones[] = $op;
            $total_pregunta += $op["veces"];
        }
        foreach ($opciones as &$op) {
            $op["porcentaje"] = $total_pregunta > 0 ? round(($op["veces"] / $total_pregunta) * 100, 1) : 0;
        }
        $pregunta["opciones"] = $opciones;
    } else {
        $comentarios = array();
        $stmt = $con->prepare("SELECT texto_libre FROM respuestas_detalle
                               WHERE id_pregunta = ? AND texto_libre IS NOT NULL AND texto_libre <> ''
                               ORDER BY id_detalle ASC");
        $stmt->bind_param("i", $pregunta["id_pregunta"]);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($c = $res->fetch_assoc()) {
            $comentarios[] = $c["texto_libre"];
        }
        $pregunta["comentarios"] = $comentarios;
    }
}

ok_json(array("total_respuestas" => $total_respuestas, "preguntas" => $preguntas));
