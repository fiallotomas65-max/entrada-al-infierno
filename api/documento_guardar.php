<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');

if (empty($titulo) || empty($descripcion) || empty($categoria)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Todos los campos del formulario son obligatorios.'
    ]);
    exit;
}

if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Debe seleccionar un archivo PDF válido para subir.'
    ]);
    exit;
}

$nombreOriginal = $_FILES['archivo']['name'];
$ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

if ($ext !== 'pdf') {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Solo se admiten archivos con extensión PDF.'
    ]);
    exit;
}

$directorioDestino = __DIR__ . '/../documentos/';
if (!file_exists($directorioDestino)) {
    mkdir($directorioDestino, 0777, true);
}

$nombreFinal = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $nombreOriginal);
$rutaCompleta = $directorioDestino . $nombreFinal;

if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaCompleta)) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al guardar el archivo PDF en el servidor.'
    ]);
    exit;
}

try {
    $pdo = obtenerConexion();

    $usuarioNombre = $_SESSION['usuario'] ?? 'amartinez';
    $stmtU = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ? LIMIT 1");
    $stmtU->execute([$usuarioNombre]);
    $idUsuario = $stmtU->fetchColumn();

    if (!$idUsuario) {
        $idUsuario = 1;
    }

    $stmt = $pdo->prepare("INSERT INTO documentos (titulo, descripcion, categoria, archivo, fecha_alta, id_usuario) VALUES (?, ?, ?, ?, NOW(), ?)");
    $stmt->execute([$titulo, $descripcion, $categoria, $nombreFinal, $idUsuario]);

    echo json_encode([
        'ok' => true,
        'mensaje' => 'El documento ha sido guardado exitosamente.'
    ]);
} catch (Exception $e) {
    if (file_exists($rutaCompleta)) {
        unlink($rutaCompleta);
    }
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}