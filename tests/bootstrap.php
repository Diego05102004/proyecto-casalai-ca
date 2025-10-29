<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['id_usuario'] = $_SESSION['id_usuario'] ?? 1;
$_SESSION['id_rol'] = $_SESSION['id_rol'] ?? 1; // Ajusta si necesitas otro rol

date_default_timezone_set('America/Caracas');

$projectRoot = __DIR__ . DIRECTORY_SEPARATOR . '..';
set_include_path($projectRoot . PATH_SEPARATOR . get_include_path());

require_once $projectRoot . '/Config/Config.php';

function test_pdo(): PDO {
    $bd = new BD('P');
    return $bd->getConexion();
}

function truncate_tablas_basicas(PDO $pdo): void {
    try { $pdo->exec('SET FOREIGN_KEY_CHECKS=0'); } catch (Throwable $e) {}
    try { $pdo->exec('TRUNCATE TABLE tbl_modelos'); } catch (Throwable $e) {}
    try { $pdo->exec('TRUNCATE TABLE tbl_marcas'); } catch (Throwable $e) {}
    try { $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $e) {}
}
