<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

$busqueda = trim($_GET['busqueda'] ?? '');
$especialidad = trim($_GET['especialidad'] ?? '');
$turno = trim($_GET['turno'] ?? '');

try {
    $pdo = obtenerConexion();

    $where = [];
    $params = [];

    if (!empty($busqueda)) {
        $where[] = "(nombre LIKE ? OR apellido LIKE ? OR documento LIKE ?)";
        $term = "%{$busqueda}%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    if (!empty($especialidad)) {
        $where[] = "especialidad = ?";
        $params[] = $especialidad;
    }

    if (!empty($turno)) {
        $where[] = "turno = ?";
        $params[] = $turno;
    }

    $sql = "SELECT id_personal, nombre, apellido, documento, telefono, 
                   COALESCE(clasificacion_operativa, cargo) AS clasificacion_operativa, 
                   especialidad, vencimiento_libreta, certificaciones, turno, 
                   COALESCE(estado, 'Disponible') AS estado 
            FROM personal";

    if (count($where) > 0) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY apellido, nombre ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $personal = $stmt->fetchAll();

    $stmtEsp = $pdo->query("SELECT DISTINCT especialidad FROM personal WHERE especialidad IS NOT NULL AND especialidad != '' ORDER BY especialidad ASC");
    $especialidades = $stmtEsp->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'ok' => true,
        'personal' => $personal,
        'especialidades' => $especialidades
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al obtener la lista de personal: ' . $e->getMessage()]);
}