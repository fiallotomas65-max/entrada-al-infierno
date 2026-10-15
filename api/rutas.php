<?php
// api de rutas del circuito nacional
require_once __DIR__ . '/auth.php';
requerir_rol(array("Administrador", "Coordinador"));

$con = conectar();
$metodo = $_SERVER["REQUEST_METHOD"];

if ($metodo == "GET") {
    $listado = array();
    $res = $con->query("SELECT * FROM rutas ORDER BY nombre ASC");
    while ($fila = $res->fetch_assoc()) {
        $listado[] = $fila;
    }
    ok_json(array("rutas" => $listado));
}

$datos = leer_json_body();
$accion = isset($datos["accion"]) ? $datos["accion"] : "guardar";

if ($metodo == "POST" && $accion == "guardar") {
    $id = isset($datos["id_ruta"]) ? $datos["id_ruta"] : "";
    $nombre = isset($datos["nombre"]) ? trim($datos["nombre"]) : "";
    $origen = isset($datos["origen"]) ? trim($datos["origen"]) : "";
    $destino = isset($datos["destino"]) ? trim($datos["destino"]) : "";
    $distancia = isset($datos["distancia_km"]) ? $datos["distancia_km"] : "";
    $tiempo = isset($datos["tiempo_min"]) ? $datos["tiempo_min"] : "";

    if (!campo_obligatorio($nombre) || !campo_obligatorio($origen) || !campo_obligatorio($destino) ||
        !is_numeric($distancia) || $distancia <= 0 || !is_numeric($tiempo) || $tiempo <= 0) {
        error_json("Complete nombre, origen, destino, y una distancia y tiempo mayores a cero.");
    }

    if ($id != "" && is_numeric($id)) {
        $stmt = $con->prepare("UPDATE rutas SET nombre=?, origen=?, destino=?, distancia_km=?, tiempo_min=? WHERE id_ruta=?");
        $stmt->bind_param("sssdii", $nombre, $origen, $destino, $distancia, $tiempo, $id);
        $stmt->execute();
        ok_json(array("mensaje" => "Ruta actualizada correctamente."));
    } else {
        $stmt = $con->prepare("INSERT INTO rutas (nombre, origen, destino, distancia_km, tiempo_min) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdi", $nombre, $origen, $destino, $distancia, $tiempo);
        $stmt->execute();
        ok_json(array("mensaje" => "Ruta guardada correctamente."));
    }
}

if ($metodo == "POST" && $accion == "eliminar") {
    $id = isset($datos["id_ruta"]) ? $datos["id_ruta"] : "";
    if (!is_numeric($id)) {
        error_json("Debe indicar una ruta valida.");
    }

    $stmt = $con->prepare("SELECT COUNT(*) AS total FROM traslados WHERE id_ruta = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()["total"];

    if ($total >= 1) {
        error_json("No se puede eliminar: la ruta fue usada en " . $total . " traslados.");
    }

    $stmt = $con->prepare("DELETE FROM rutas WHERE id_ruta = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    ok_json(array("mensaje" => "Ruta eliminada correctamente."));
}

error_json("Solicitud no reconocida.");
