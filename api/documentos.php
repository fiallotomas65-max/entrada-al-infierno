<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuarioSesion = $_SESSION['usuario'] ?? 'amartinez';
$rolUsuario = $_SESSION['rol'] ?? 'Administrador';

require_once __DIR__ . '/conexion.php';

try {
    $pdo = obtenerConexion();

    $busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
    $categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
    $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $limite = isset($_GET['limite']) ? max(1, min(100, (int)$_GET['limite'])) : 20;
    $offset = ($pagina - 1) * $limite;

    $condiciones = [];
    $parametros = [];

    if ($busqueda !== '') {
        $condiciones[] = "(d.titulo LIKE ? OR d.descripcion LIKE ?)";
        $parametros[] = '%' . $busqueda . '%';
        $parametros[] = '%' . $busqueda . '%';
    }

    if ($categoria !== '') {
        $condiciones[] = "d.categoria = ?";
        $parametros[] = $categoria;
    }

    $sqlBase = "FROM documentos d LEFT JOIN usuarios u ON d.id_usuario = u.id_usuario";
    if (!empty($condiciones)) {
        $sqlBase .= " WHERE " . implode(" AND ", $condiciones);
    }

    $sqlConteo = "SELECT COUNT(*) as total " . $sqlBase;
    $stmtConteo = $pdo->prepare($sqlConteo);
    $stmtConteo->execute($parametros);
    $totalRegistros = (int)$stmtConteo->fetchColumn();

    $sqlDatos = "SELECT d.id_documento, d.titulo, d.descripcion, d.categoria, d.archivo, d.fecha_alta, CONCAT(u.nombre, ' ', u.apellido) AS subido_por " . $sqlBase . " ORDER BY d.id_documento DESC LIMIT ? OFFSET ?";
    $stmtDatos = $pdo->prepare($sqlDatos);

    $indiceParametro = 1;
    foreach ($parametros as $val) {
        $stmtDatos->bindValue($indiceParametro++, $val, PDO::PARAM_STR);
    }
    $stmtDatos->bindValue($indiceParametro++, $limite, PDO::PARAM_INT);
    $stmtDatos->bindValue($indiceParametro++, $offset, PDO::PARAM_INT);

    $stmtDatos->execute();
    $documentos = $stmtDatos->fetchAll();

    $totalPaginas = ($totalRegistros > 0) ? (int)ceil($totalRegistros / $limite) : 1;

    echo json_encode([
        'ok' => true,
        'documentos' => $documentos,
        'paginacion' => [
            'total_registros' => $totalRegistros,
            'pagina_actual' => $pagina,
            'total_paginas' => $totalPaginas,
            'registros_por_pagina' => $limite
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => $e->getMessage()
    ]);
}