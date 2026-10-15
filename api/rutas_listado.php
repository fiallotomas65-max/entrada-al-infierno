<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

try {
    $pdo = obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmtCols = $pdo->query("SHOW COLUMNS FROM rutas");
    $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

    if (empty($cols)) {
        throw new Exception("La tabla 'rutas' no existe o no tiene columnas.");
    }

    $colId = in_array('id_ruta', $cols) ? 'id_ruta' : (in_array('id', $cols) ? 'id AS id_ruta' : $cols[0] . ' AS id_ruta');
    $colNombre = in_array('nombre_ruta', $cols) ? 'nombre_ruta' : (in_array('nombre', $cols) ? 'nombre AS nombre_ruta' : "'' AS nombre_ruta");
    $colOrigen = in_array('origen', $cols) ? 'origen' : "'' AS origen";
    $colDestino = in_array('destino', $cols) ? 'destino' : "'' AS destino";
    $colTiempo = in_array('tiempo_estimado_min', $cols) ? 'tiempo_estimado_min' : (in_array('tiempo_estimado', $cols) ? 'tiempo_estimado AS tiempo_estimado_min' : "NULL AS tiempo_estimado_min");
    $colDistancia = in_array('distancia_km', $cols) ? 'distancia_km' : (in_array('distancia', $cols) ? 'distancia AS distancia_km' : "NULL AS distancia_km");
    $colObs = in_array('observaciones', $cols) ? 'observaciones' : "'' AS observaciones";

    $sql = "SELECT {$colId}, {$colNombre}, {$colOrigen}, {$colDestino}, {$colDistancia}, {$colTiempo}, {$colObs} 
            FROM rutas 
            ORDER BY 1 DESC";

    $stmt = $pdo->query($sql);
    $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode([
        'ok' => true,
        'rutas' => $rutas
    ]);
} catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}