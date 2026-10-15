<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

$nombre_ruta = trim($_POST['nombre_ruta'] ?? '');
$origen = trim($_POST['origen'] ?? '');
$destino = trim($_POST['destino'] ?? '');
$distancia_km = !empty($_POST['distancia_km']) ? $_POST['distancia_km'] : null;
$tiempo_estimado_min = !empty($_POST['tiempo_estimado_min']) ? $_POST['tiempo_estimado_min'] : null;
$observaciones = trim($_POST['observaciones'] ?? '');

if (empty($nombre_ruta) || empty($origen) || empty($destino)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Completa los campos obligatorios.']);
    exit;
}

try {
    $pdo = obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmtCols = $pdo->query("SHOW COLUMNS FROM rutas");
    $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

    $colNombre = in_array('nombre_ruta', $cols) ? 'nombre_ruta' : (in_array('nombre', $cols) ? 'nombre' : null);
    $colTiempo = in_array('tiempo_estimado_min', $cols) ? 'tiempo_estimado_min' : (in_array('tiempo_estimado', $cols) ? 'tiempo_estimado' : null);
    $colDistancia = in_array('distancia_km', $cols) ? 'distancia_km' : (in_array('distancia', $cols) ? 'distancia' : null);
    $colObs = in_array('observaciones', $cols) ? 'observaciones' : null;

    $campos = ['origen', 'destino'];
    $valores = [$origen, $destino];

    if ($colNombre) { $campos[] = $colNombre; $valores[] = $nombre_ruta; }
    if ($colDistancia) { $campos[] = $colDistancia; $valores[] = $distancia_km; }
    if ($colTiempo) { $campos[] = $colTiempo; $valores[] = $tiempo_estimado_min; }
    if ($colObs) { $campos[] = $colObs; $valores[] = $observaciones; }

    $placeholders = implode(',', array_fill(0, count($campos), '?'));
    $sql = "INSERT INTO rutas (" . implode(',', $campos) . ") VALUES (" . $placeholders . ")";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($valores);

    ob_clean();
    echo json_encode(['ok' => true, 'mensaje' => 'Ruta registrada exitosamente.']);
} catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar: ' . $e->getMessage()]);
}