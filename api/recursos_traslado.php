<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

try {
    $pdo = obtenerConexion();

    $ambulancias = $pdo->query("SELECT id_ambulancia, patente, modelo, estado FROM ambulancias ORDER BY patente ASC")->fetchAll();
    $personal = $pdo->query("SELECT id_personal, nombre, apellido, cargo, documento FROM personal ORDER BY apellido, nombre ASC")->fetchAll();
    $rutas = $pdo->query("SELECT id_ruta, nombre, origen, destino, distancia_km, tiempo_min FROM rutas ORDER BY nombre ASC")->fetchAll();

    echo json_encode([
        'ok' => true,
        'ambulancias' => $ambulancias,
        'personal' => $personal,
        'rutas' => $rutas
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al obtener los recursos: ' . $e->getMessage()]);
}