<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

$idTraslado = (int)($data['id_traslado'] ?? 0);
$nuevoEstado = trim($data['nuevo_estado'] ?? '');
$comentario = trim($data['comentario'] ?? '');

$estadosValidos = ['Pendiente', 'En curso', 'Finalizado', 'Cancelado'];

if ($idTraslado <= 0 || !in_array($nuevoEstado, $estadosValidos, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Datos de actualización no válidos.']);
    exit;
}

try {
    $pdo = obtenerConexion();
    $pdo->beginTransaction();

    $stmtT = $pdo->prepare("SELECT id_ambulancia FROM traslados WHERE id_traslado = ?");
    $stmtT->execute([$idTraslado]);
    $idAmbulancia = $stmtT->fetchColumn();

    if (!$idAmbulancia) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'mensaje' => 'El traslado no existe.']);
        exit;
    }

    $usuarioNombre = $_SESSION['usuario'] ?? 'amartinez';
    $stmtU = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ? LIMIT 1");
    $stmtU->execute([$usuarioNombre]);
    $idUsuario = $stmtU->fetchColumn() ?: 1;

    if ($nuevoEstado === 'En curso') {
        $stmtUpd = $pdo->prepare("UPDATE traslados SET estado = ?, hora_salida = NOW() WHERE id_traslado = ?");
        $stmtUpd->execute([$nuevoEstado, $idTraslado]);

        $stmtAmb = $pdo->prepare("UPDATE ambulancias SET estado = 'En servicio' WHERE id_ambulancia = ?");
        $stmtAmb->execute([$idAmbulancia]);
    } elseif ($nuevoEstado === 'Finalizado' || $nuevoEstado === 'Cancelado') {
        $stmtUpd = $pdo->prepare("UPDATE traslados SET estado = ?, hora_llegada = NOW() WHERE id_traslado = ?");
        $stmtUpd->execute([$nuevoEstado, $idTraslado]);

        $stmtAmb = $pdo->prepare("UPDATE ambulancias SET estado = 'Disponible' WHERE id_ambulancia = ?");
        $stmtAmb->execute([$idAmbulancia]);
    } else {
        $stmtUpd = $pdo->prepare("UPDATE traslados SET estado = ? WHERE id_traslado = ?");
        $stmtUpd->execute([$nuevoEstado, $idTraslado]);
    }

    $stmtSeg = $pdo->prepare("INSERT INTO seguimiento (id_traslado, estado, fecha_hora, comentario, id_usuario) VALUES (?, ?, NOW(), ?, ?)");
    $stmtSeg->execute([$idTraslado, $nuevoEstado, $comentario ?: ("Avance de línea de tiempo: " . $nuevoEstado), $idUsuario]);

    $pdo->commit();

    echo json_encode(['ok' => true, 'mensaje' => 'Estado actualizado a: ' . $nuevoEstado]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al cambiar estado: ' . $e->getMessage()]);
}