<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

$idAmbulancia = (int)($_POST['id_ambulancia'] ?? 0);
$idConductor = (int)($_POST['id_conductor'] ?? 0);
$idCopiloto = !empty($_POST['id_copiloto']) ? (int)$_POST['id_copiloto'] : null;
$idRuta = !empty($_POST['id_ruta']) ? (int)$_POST['id_ruta'] : null;

$tipoElemento = trim($_POST['tipo_elemento'] ?? '');
$elemento = trim($_POST['elemento'] ?? '');
$requerimientos = trim($_POST['requerimientos'] ?? '');
$origen = trim($_POST['origen'] ?? '');
$destino = trim($_POST['destino'] ?? '');
$fechaTraslado = trim($_POST['fecha_traslado'] ?? date('Y-m-d'));
$observacionesText = trim($_POST['observaciones'] ?? '');

if (!$idAmbulancia || !$idConductor || empty($tipoElemento) || empty($elemento) || empty($origen) || empty($destino)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Por favor complete todos los campos obligatorios del traslado.']);
    exit;
}

if ($idCopiloto && $idConductor === $idCopiloto) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'El conductor y el copiloto no pueden ser la misma persona.']);
    exit;
}

$obsFinal = $observacionesText;
if (!empty($requerimientos)) {
    $obsFinal = "[Requerimientos Especiales: " . $requerimientos . "] " . $obsFinal;
}

try {
    $pdo = obtenerConexion();

    // 1. Validación de Personal (Conductor)
    $stmtValC = $pdo->prepare("SELECT COUNT(*) FROM traslados WHERE (id_conductor = ? OR id_copiloto = ?) AND estado IN ('Pendiente', 'En curso') AND fecha_traslado = ?");
    $stmtValC->execute([$idConductor, $idConductor, $fechaTraslado]);
    if ($stmtValC->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'El conductor seleccionado ya tiene un traslado activo asignado para esta fecha.']);
        exit;
    }

    // 2. Validación de Personal (Copiloto)
    if ($idCopiloto) {
        $stmtValCp = $pdo->prepare("SELECT COUNT(*) FROM traslados WHERE (id_conductor = ? OR id_copiloto = ?) AND estado IN ('Pendiente', 'En curso') AND fecha_traslado = ?");
        $stmtValCp->execute([$idCopiloto, $idCopiloto, $fechaTraslado]);
        if ($stmtValCp->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'mensaje' => 'El copiloto seleccionado ya tiene un traslado activo asignado para esta fecha.']);
            exit;
        }
    }

    // 3. Validación de Ambulancia
    $stmtValAmb = $pdo->prepare("SELECT estado FROM ambulancias WHERE id_ambulancia = ?");
    $stmtValAmb->execute([$idAmbulancia]);
    $estadoAmb = $stmtValAmb->fetchColumn();

    if ($estadoAmb === 'Mantenimiento') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'La ambulancia seleccionada está en taller/mantenimiento.']);
        exit;
    }

    $stmtValAmbOcupada = $pdo->prepare("SELECT COUNT(*) FROM traslados WHERE id_ambulancia = ? AND estado IN ('Pendiente', 'En curso') AND fecha_traslado = ?");
    $stmtValAmbOcupada->execute([$idAmbulancia, $fechaTraslado]);
    if ($stmtValAmbOcupada->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'La ambulancia ya está asignada a otra solicitud activa para esta fecha.']);
        exit;
    }

    $usuarioNombre = $_SESSION['usuario'] ?? 'amartinez';
    $stmtU = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ? LIMIT 1");
    $stmtU->execute([$usuarioNombre]);
    $idUsuario = $stmtU->fetchColumn() ?: 1;

    $pdo->beginTransaction();

    $stmtIns = $pdo->prepare("INSERT INTO traslados (id_ambulancia, id_conductor, id_copiloto, id_ruta, tipo_elemento, elemento, origen, destino, fecha_traslado, estado, observaciones, id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente', ?, ?)");
    $stmtIns->execute([$idAmbulancia, $idConductor, $idCopiloto, $idRuta, $tipoElemento, $elemento, $origen, $destino, $fechaTraslado, $obsFinal, $idUsuario]);
    $idTraslado = $pdo->lastInsertId();

    $stmtSeg = $pdo->prepare("INSERT INTO seguimiento (id_traslado, estado, fecha_hora, comentario, id_usuario) VALUES (?, 'Pendiente', NOW(), 'Solicitud de ambulancia creada', ?)");
    $stmtSeg->execute([$idTraslado, $idUsuario]);

    $pdo->commit();

    echo json_encode(['ok' => true, 'mensaje' => 'Solicitud de ambulancia registrada exitosamente.']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar la solicitud: ' . $e->getMessage()]);
}