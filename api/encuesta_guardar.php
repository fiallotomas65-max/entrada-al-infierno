<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

$titulo = trim($data['titulo'] ?? '');
$descripcion = trim($data['descripcion'] ?? '');
$preguntas = $data['preguntas'] ?? [];

if (empty($titulo)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'El título de la encuesta es obligatorio.']);
    exit;
}

if (empty($preguntas) || !is_array($preguntas)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Debe agregar al menos una pregunta a la encuesta.']);
    exit;
}

try {
    $pdo = obtenerConexion();
    $pdo->beginTransaction();

    $stmtE = $pdo->prepare("INSERT INTO encuestas (titulo, descripcion, estado, fecha_creacion) VALUES (?, ?, 'Activa', NOW())");
    $stmtE->execute([$titulo, $descripcion]);
    $idEncuesta = $pdo->lastInsertId();

    $stmtP = $pdo->prepare("INSERT INTO preguntas (id_encuesta, texto, tipo) VALUES (?, ?, ?)");
    $stmtO = $pdo->prepare("INSERT INTO opciones (id_pregunta, texto) VALUES (?, ?)");

    foreach ($preguntas as $p) {
        $textoP = trim($p['texto'] ?? '');
        $tipoP = ($p['tipo'] === 'cerrada') ? 'cerrada' : 'abierta';

        if (empty($textoP)) {
            continue;
        }

        $stmtP->execute([$idEncuesta, $textoP, $tipoP]);
        $idPregunta = $pdo->lastInsertId();

        if ($tipoP === 'cerrada' && isset($p['opciones']) && is_array($p['opciones'])) {
            foreach ($p['opciones'] as $opcionTexto) {
                $opcionTexto = trim($opcionTexto);
                if ($opcionTexto !== '') {
                    $stmtO->execute([$idPregunta, $opcionTexto]);
                }
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Encuesta creada exitosamente.'
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al guardar la encuesta: ' . $e->getMessage()
    ]);
}