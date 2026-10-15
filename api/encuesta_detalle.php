<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

$idEncuesta = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idEncuesta <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Identificador de encuesta no válido.']);
    exit;
}

try {
    $pdo = obtenerConexion();

    $stmtE = $pdo->prepare("SELECT id_encuesta, titulo, descripcion, estado, fecha_creacion FROM encuestas WHERE id_encuesta = ?");
    $stmtE->execute([$idEncuesta]);
    $encuesta = $stmtE->fetch();

    if (!$encuesta) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'mensaje' => 'La encuesta solicitada no existe.']);
        exit;
    }

    $stmtP = $pdo->prepare("SELECT id_pregunta, texto, tipo FROM preguntas WHERE id_encuesta = ? ORDER BY id_pregunta ASC");
    $stmtP->execute([$idEncuesta]);
    $preguntas = $stmtP->fetchAll();

    $stmtO = $pdo->prepare("SELECT id_opcion, texto FROM opciones WHERE id_pregunta = ? ORDER BY id_opcion ASC");

    foreach ($preguntas as &$preg) {
        if ($preg['tipo'] === 'cerrada') {
            $stmtO->execute([$preg['id_pregunta']]);
            $preg['opciones'] = $stmtO->fetchAll();
        } else {
            $preg['opciones'] = [];
        }
    }
    unset($preg);

    $stmtR = $pdo->prepare("SELECT COUNT(*) FROM respuestas WHERE id_encuesta = ?");
    $stmtR->execute([$idEncuesta]);
    $totalRespuestas = (int)$stmtR->fetchColumn();

    echo json_encode([
        'ok' => true,
        'encuesta' => $encuesta,
        'preguntas' => $preguntas,
        'total_respuestas' => $totalRespuestas
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al recuperar el detalle de la encuesta: ' . $e->getMessage()]);
}