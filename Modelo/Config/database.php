<?php
// Base de Datos Principal "casalai_principal"
define('DB_PRINCIPAL', [
    'host' => 'localhost',
    'dbname' => 'casalai_principal',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8'
]);

// Base de Datos Secundaria "casalai_seguridad"
define('DB_SEGURIDAD', [
    'host' => 'localhost',
    'dbname' => 'casalai_seguridad',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8'
]);

date_default_timezone_set('America/Caracas');
?>