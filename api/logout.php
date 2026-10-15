<?php
// cierra la sesion del usuario
require_once __DIR__ . '/auth.php';

session_unset();
session_destroy();

ok_json();
