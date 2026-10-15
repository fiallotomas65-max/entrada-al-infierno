<?php
// sirve el pdf de un documento al navegador. Es publico: se llega aca
// escaneando el QR de la pared, no requiere sesion.
require_once __DIR__ . '/funciones.php';

function documento_no_encontrado() {
    http_response_code(404);
    echo "Documento no encontrado";
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    documento_no_encontrado();
}

$con = conectar();
$id = (int) $_GET['id'];

$stmt = $con->prepare("SELECT titulo, archivo FROM documentos WHERE id_documento = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();

if (!$doc) {
    documento_no_encontrado();
}

// basename() evita que se manipule la ruta para leer otras carpetas
$nombre_archivo = basename($doc['archivo']);
$ruta = CARPETA_DOCUMENTOS . '/' . $nombre_archivo;

if (!file_exists($ruta) || !is_file($ruta)) {
    documento_no_encontrado();
}

header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"" . $nombre_archivo . "\"");
header("Content-Length: " . filesize($ruta));
readfile($ruta);
exit;
