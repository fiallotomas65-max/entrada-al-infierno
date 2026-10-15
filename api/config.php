<?php
// configuracion del sistema: datos de conexion a la base

define('SERVIDOR', 'localhost');
define('USUARIO_DB', 'root');
define('CONTRASENA_DB', '');
define('BASE', 'cds_hospital');

// carpeta donde se guardan los pdf subidos
define('CARPETA_DOCUMENTOS', __DIR__ . '/../documentos');

// conecta a la base de datos y devuelve la conexion
function conectar() {
    $con = new mysqli(SERVIDOR, USUARIO_DB, CONTRASENA_DB, BASE);

    if ($con->connect_error) {
        http_response_code(500);
        echo json_encode(array("error" => "No se pudo conectar a la base de datos."));
        exit;
    }

    $con->set_charset('utf8mb4');
    return $con;
}
