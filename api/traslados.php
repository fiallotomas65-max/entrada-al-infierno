<?php
// api de traslados en ambulancia
require_once __DIR__ . '/auth.php';
requerir_rol(array("Administrador", "Coordinador"));

$con = conectar();
$metodo = $_SERVER["REQUEST_METHOD"];

// ------------------------------------------------
// GET: listado de traslados (con filtro opcional por estado), o el detalle
// de uno solo con su historial si viene ?id=
// ------------------------------------------------
if ($metodo == "GET") {

    if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
        $id = $_GET["id"];

        $sql = "SELECT t.*, a.patente, a.modelo,
                       p.nombre AS conductor_nombre, p.apellido AS conductor_apellido,
                       c.nombre AS copiloto_nombre, c.apellido AS copiloto_apellido,
                       r.nombre AS ruta_nombre
                FROM traslados t
                JOIN ambulancias a ON a.id_ambulancia = t.id_ambulancia
                JOIN personal p ON p.id_personal = t.id_conductor
                LEFT JOIN personal c ON c.id_personal = t.id_copiloto
                LEFT JOIN rutas r ON r.id_ruta = t.id_ruta
                WHERE t.id_traslado = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $traslado = $stmt->get_result()->fetch_assoc();

        if (!$traslado) {
            error_json("No se encontro el traslado.", 404);
        }

        $historial = array();
        $stmt = $con->prepare("SELECT s.*, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido
                               FROM seguimiento s JOIN usuarios u ON u.id_usuario = s.id_usuario
                               WHERE s.id_traslado = ? ORDER BY s.fecha_hora ASC");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($fila = $res->fetch_assoc()) {
            $historial[] = $fila;
        }

        // a que estados puede pasar desde el estado actual
        $transiciones = array(
            "Pendiente" => array("En curso", "Cancelado"),
            "En curso"  => array("Finalizado", "Cancelado"),
            "Finalizado" => array(),
            "Cancelado" => array(),
        );

        ok_json(array(
            "traslado" => $traslado,
            "historial" => $historial,
            "siguientes_estados" => $transiciones[$traslado["estado"]]
        ));
    }

    $estado_filtro = isset($_GET["estado"]) ? $_GET["estado"] : "";
    $estados_validos = array("Pendiente", "En curso", "Finalizado", "Cancelado");

    $sql = "SELECT t.*, a.patente, p.nombre AS conductor_nombre, p.apellido AS conductor_apellido
            FROM traslados t
            JOIN ambulancias a ON a.id_ambulancia = t.id_ambulancia
            JOIN personal p ON p.id_personal = t.id_conductor";

    if (in_array($estado_filtro, $estados_validos)) {
        $sql .= " WHERE t.estado = ? ORDER BY t.fecha_traslado DESC";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("s", $estado_filtro);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $sql .= " ORDER BY t.fecha_traslado DESC";
        $res = $con->query($sql);
    }

    $listado = array();
    while ($fila = $res->fetch_assoc()) {
        $listado[] = $fila;
    }
    ok_json(array("traslados" => $listado));
}

// ------------------------------------------------
// POST: alta de un traslado nuevo (nace en estado Pendiente)
// ------------------------------------------------
if ($metodo != "POST") {
    error_json("Metodo no permitido.", 405);
}

$datos = leer_json_body();

$id_ambulancia = isset($datos["id_ambulancia"]) ? $datos["id_ambulancia"] : "";
$id_conductor = isset($datos["id_conductor"]) ? $datos["id_conductor"] : "";
$id_copiloto = isset($datos["id_copiloto"]) && $datos["id_copiloto"] != "" ? $datos["id_copiloto"] : null;
$id_ruta = isset($datos["id_ruta"]) && $datos["id_ruta"] != "" ? $datos["id_ruta"] : null;
$tipo_elemento = isset($datos["tipo_elemento"]) ? $datos["tipo_elemento"] : "";
$elemento = isset($datos["elemento"]) ? trim($datos["elemento"]) : "";
$origen = isset($datos["origen"]) ? trim($datos["origen"]) : "";
$destino = isset($datos["destino"]) ? trim($datos["destino"]) : "";
$fecha_traslado = isset($datos["fecha_traslado"]) ? $datos["fecha_traslado"] : "";
$observaciones = isset($datos["observaciones"]) ? trim($datos["observaciones"]) : "";

$tipos_validos = array("Paciente", "Material biologico", "Equipamiento", "Insumo");

if (!is_numeric($id_ambulancia) || !is_numeric($id_conductor) || !in_array($tipo_elemento, $tipos_validos) ||
    !campo_obligatorio($elemento) || !campo_obligatorio($origen) || !campo_obligatorio($destino) ||
    !campo_obligatorio($fecha_traslado) || !strtotime($fecha_traslado)) {
    error_json("Revise los datos del traslado: hay campos obligatorios sin completar.");
}

if ($id_copiloto !== null && !is_numeric($id_copiloto)) {
    error_json("El copiloto elegido no es valido.");
}

if ($id_ruta !== null && !is_numeric($id_ruta)) {
    error_json("La ruta elegida no es valida.");
}

if ($id_copiloto !== null && $id_copiloto == $id_conductor) {
    error_json("El conductor y el copiloto no pueden ser la misma persona.");
}

// se verifica que ambulancia, conductor, copiloto (si vino) y ruta (si vino)
// existan de verdad en la base, para no llegar a un error de clave foranea
$referencias = array(
    array("ambulancias", "id_ambulancia", $id_ambulancia),
    array("personal", "id_personal", $id_conductor),
    array("personal", "id_personal", $id_copiloto),
    array("rutas", "id_ruta", $id_ruta),
);

foreach ($referencias as $referencia) {
    list($tabla, $columna, $valor) = $referencia;
    if ($valor === null) {
        continue; // copiloto y ruta son opcionales
    }
    $stmt = $con->prepare("SELECT COUNT(*) AS total FROM $tabla WHERE $columna = ?");
    $stmt->bind_param("i", $valor);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()["total"] == 0) {
        error_json("La ambulancia, el conductor, el copiloto o la ruta elegidos no existen.");
    }
}

$fecha_actual = date("Y-m-d H:i:s");
$id_usuario = $_SESSION["id_usuario"];

$sql = "INSERT INTO traslados (id_ambulancia, id_conductor, id_copiloto, id_ruta, tipo_elemento,
                                elemento, origen, destino, fecha_traslado, estado, observaciones, id_usuario)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente', ?, ?)";
$stmt = $con->prepare($sql);
$stmt->bind_param("iiiissssssi", $id_ambulancia, $id_conductor, $id_copiloto, $id_ruta, $tipo_elemento,
    $elemento, $origen, $destino, $fecha_traslado, $observaciones, $id_usuario);
$stmt->execute();
$id_traslado = $stmt->insert_id;

// el traslado y su primer registro de seguimiento nacen juntos
$comentario_inicial = "Solicitud de traslado registrada";
$stmt = $con->prepare("INSERT INTO seguimiento (id_traslado, estado, fecha_hora, comentario, id_usuario) VALUES (?, 'Pendiente', ?, ?, ?)");
$stmt->bind_param("issi", $id_traslado, $fecha_actual, $comentario_inicial, $id_usuario);
$stmt->execute();

ok_json(array("mensaje" => "Traslado guardado correctamente.", "id_traslado" => $id_traslado));
