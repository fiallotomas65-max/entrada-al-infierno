<?php
// numeros resumen que se muestran en el panel principal
require_once __DIR__ . '/auth.php';
requerir_login();

$con = conectar();

function contar($con, $sql) {
    $res = $con->query($sql);
    $fila = $res->fetch_assoc();
    return $fila['total'];
}

$resumen = array(
    "documentos" => contar($con, "SELECT COUNT(*) AS total FROM documentos"),
    "encuestas_activas" => contar($con, "SELECT COUNT(*) AS total FROM encuestas WHERE estado = 'Activa'"),
    "respuestas" => contar($con, "SELECT COUNT(*) AS total FROM respuestas"),
    "traslados_pendientes" => contar($con, "SELECT COUNT(*) AS total FROM traslados WHERE estado = 'Pendiente'"),
    "traslados_en_curso" => contar($con, "SELECT COUNT(*) AS total FROM traslados WHERE estado = 'En curso'"),
);

ok_json(array("resumen" => $resumen, "nombre" => $_SESSION['nombre'], "rol" => $_SESSION['rol']));
