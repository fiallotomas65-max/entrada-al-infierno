<?php
// cambia el estado de un traslado y deja registro en el historial
require_once __DIR__ . '/auth.php';
requerir_rol(array("Administrador", "Coordinador"));

$con = conectar();

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    error_json("Metodo no permitido.", 405);
}

$datos = leer_json_body();
$id_traslado = isset($datos["id_traslado"]) ? $datos["id_traslado"] : "";
$estado_nuevo = isset($datos["estado"]) ? $datos["estado"] : "";
$comentario = isset($datos["comentario"]) ? trim($datos["comentario"]) : "";

if (!is_numeric($id_traslado)) {
    error_json("Traslado no valido.");
}

// a que estados se puede pasar desde cada estado
$transiciones = array(
    "Pendiente"  => array("En curso", "Cancelado"),
    "En curso"   => array("Finalizado", "Cancelado"),
    "Finalizado" => array(),
    "Cancelado"  => array(),
);

// el estado actual se consulta de la base, nunca se confia en lo que manda el formulario
$stmt = $con->prepare("SELECT t.*, a.estado AS estado_ambulancia FROM traslados t
                       JOIN ambulancias a ON a.id_ambulancia = t.id_ambulancia
                       WHERE t.id_traslado = ?");
$stmt->bind_param("i", $id_traslado);
$stmt->execute();
$traslado = $stmt->get_result()->fetch_assoc();

if (!$traslado) {
    error_json("No se encontro el traslado.", 404);
}

$permitidos = $transiciones[$traslado['estado']];
if (!in_array($estado_nuevo, $permitidos)) {
    error_json("El cambio a '" . $estado_nuevo . "' no es valido desde el estado '" . $traslado['estado'] . "'.");
}

$fecha_actual = date("Y-m-d H:i:s");

$hora_salida = $traslado['hora_salida'];
$hora_llegada = $traslado['hora_llegada'];

if ($estado_nuevo == "En curso") {
    $hora_salida = $fecha_actual;
} elseif ($estado_nuevo == "Finalizado") {
    $hora_llegada = $fecha_actual;
}

$stmt = $con->prepare("UPDATE traslados SET estado = ?, hora_salida = ?, hora_llegada = ? WHERE id_traslado = ?");
$stmt->bind_param("sssi", $estado_nuevo, $hora_salida, $hora_llegada, $id_traslado);
$stmt->execute();

// se sincroniza el estado de la ambulancia, salvo que este en mantenimiento
if ($traslado['estado_ambulancia'] != "Mantenimiento") {
    if ($estado_nuevo == "En curso") {
        $stmt = $con->prepare("UPDATE ambulancias SET estado = 'En servicio' WHERE id_ambulancia = ?");
        $stmt->bind_param("i", $traslado['id_ambulancia']);
        $stmt->execute();
    } elseif ($estado_nuevo == "Finalizado" || $estado_nuevo == "Cancelado") {
        $stmt = $con->prepare("UPDATE ambulancias SET estado = 'Disponible' WHERE id_ambulancia = ?");
        $stmt->bind_param("i", $traslado['id_ambulancia']);
        $stmt->execute();
    }
}

$id_usuario = $_SESSION["id_usuario"];
$stmt = $con->prepare("INSERT INTO seguimiento (id_traslado, estado, fecha_hora, comentario, id_usuario) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isssi", $id_traslado, $estado_nuevo, $fecha_actual, $comentario, $id_usuario);
$stmt->execute();

ok_json(array("mensaje" => "Estado del traslado actualizado correctamente."));
