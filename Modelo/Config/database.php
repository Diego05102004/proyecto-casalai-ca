<?php
// Base de Datos Principal
define('DB_PRINCIPAL', [
    'host' => 'mysql-casalai.alwaysdata.net',
    'dbname' => 'casalai_principal',
    'user' => 'casalai',
    'pass' => 'CasaLaiCa.SDBP#!',
    'charset' => 'utf8'
]);

// Base de Datos Secundaria 
define('DB_SEGURIDAD', [
    'host' => 'mysql-casalai.alwaysdata.net',
    'dbname' => 'casalai_seguridad',
    'user' => 'casalai',
    'pass' => 'CasaLaiCa.DSBP#!',
    'charset' => 'utf8'
]);

date_default_timezone_set('America/Caracas');
?>

