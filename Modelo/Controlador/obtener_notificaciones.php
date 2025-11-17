<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

// Incluir manualmente el archivo de configuración de la base de datos
require_once __DIR__ . '/../Config/database.php';
// Incluir manualmente el archivo de la clase BD
require_once __DIR__ . '/../Config/Config.php';

use Usuario\ProyectoCasalaiCa\Clases\Notificaciones;
use Usuario\ProyectoCasalaiCa\Config\Config\BD;

header('Content-Type: application/json');

$response = ['success' => false, 'count' => 0, 'notificaciones' => []];

try {
    if (isset($_SESSION['id_usuario'])) {
        $id_usuario = $_SESSION['id_usuario'];
        $bd_seguridad = new \Usuario\ProyectoCasalaiCa\Config\Config\BD('S');
        $pdo_seguridad = $bd_seguridad->getConexion();
        try {
            $query = "SELECT * FROM tbl_notificaciones 
                      WHERE id_usuario = :id_usuario AND leido = 0
                      ORDER BY fecha_hora DESC LIMIT 5";
            $stmt = $pdo_seguridad->prepare($query);
            $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $stmt->execute();
            $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['notificaciones'] = $notificaciones;
            $response['count'] = count($notificaciones);
            $response['success'] = true;
        } finally {
            if (isset($bd_seguridad)) { $bd_seguridad->cerrar(); }
        }
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>
