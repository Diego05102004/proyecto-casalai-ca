<?php
session_start();

require_once(__DIR__ . '/../config/config.php');

header('Content-Type: application/json');

$response = ['success' => false, 'count' => 0, 'notificaciones' => []];

try {
    if (isset($_SESSION['id_usuario'])) {
        $id_usuario = $_SESSION['id_usuario'];
        $bd_seguridad = new BD('S');
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
