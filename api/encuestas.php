<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

try {
    $pdo = obtenerConexion();
    $stmt = $pdo->query("SELECT id_encuesta, titulo, descripcion, estado, fecha_creacion FROM encuestas ORDER BY id_encuesta DESC");
    $encuestas = $stmt->fetchAll();

    echo json_encode([
        'ok' => true,
        'encuestas' => $encuestas
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al recuperar las encuestas: ' . $e->getMessage()
    ]);
}