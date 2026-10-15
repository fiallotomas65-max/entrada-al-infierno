<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$documento = trim($_POST['documento'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$clasificacion = trim($_POST['clasificacion_operativa'] ?? '');
$especialidad = trim($_POST['especialidad'] ?? '');
$vencimientoLibreta = !empty($_POST['vencimiento_libreta']) ? $_POST['vencimiento_libreta'] : null;
$certificaciones = trim($_POST['certificaciones'] ?? '');
$turno = trim($_POST['turno'] ?? '');

if (empty($nombre) || empty($apellido) || empty($documento) || empty($clasificacion)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Complete los campos obligatorios: Nombre, Apellido, Documento y Clasificación.']);
    exit;
}

try {
    $pdo = obtenerConexion();

    $stmtVal = $pdo->prepare("SELECT COUNT(*) FROM personal WHERE documento = ?");
    $stmtVal->execute([$documento]);
    if ($stmtVal->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'Ya existe un funcionario registrado con ese número de documento.']);
        exit;
    }

    $sql = "INSERT INTO personal (nombre, apellido, documento, telefono, clasificacion_operativa, especialidad, vencimiento_libreta, certificaciones, turno, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Disponible')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $apellido, $documento, $telefono, $clasificacion, $especialidad, $vencimientoLibreta, $certificaciones, $turno]);

    echo json_encode(['ok' => true, 'mensaje' => 'Funcionario registrado exitosamente.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al registrar al funcionario: ' . $e->getMessage()]);
}