<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Usuario\ProyectoCasalaiCa\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Clases\Bitacora;

function getNotificacionesUsuario($id_usuario) {
    try {
        $notificacion = new NotificacionModel();
        return $notificacion->obtenerNotificacionesUsuario($id_usuario);
    } catch (Exception $e) {
        error_log('Error en getNotificacionesUsuario: ' . $e->getMessage());
        return [];
    }
}

// Función para marcar una notificación como leída
function marcarNotificacionLeida($id_notificacion, $id_usuario) {
    $notificacion = new NotificacionModel();
    return $notificacion->marcarComoLeida($id_notificacion, $id_usuario);
}

// Función para marcar todas las notificaciones como leídas
function marcarTodasLeidas($id_usuario) {
    $notificacion = new NotificacionModel();
    return $notificacion->marcarTodasComoLeidas($id_usuario);
}

// Manejo de acciones AJAX
if (isset($_GET['accion']) && !empty($_GET['accion'])) {
    if (!isset($_SESSION['id_usuario'])) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['error' => 'Usuario no autenticado']);
        exit;
    }
    
    $accion = $_GET['accion'];
    $respuesta = ['exito' => false, 'mensaje' => 'Acción no válida'];
    
    switch ($accion) {
        case 'listar':
            $notificaciones = getNotificacionesUsuario($_SESSION['id_usuario']);
            // Formatear fechas para mejor legibilidad (tolerante a campos faltantes)
            foreach ($notificaciones as &$notificacion) {
                try {
                    $src = $notificacion['fecha_hora'] ?? $notificacion['fecha_creacion'] ?? null;
                    if ($src) {
                        $fecha = new DateTime($src);
                        $notificacion['fecha_formateada'] = $fecha->format('Y-m-d H:i:s');
                    } else {
                        $notificacion['fecha_formateada'] = null;
                    }
                } catch (Exception $e) {
                    $notificacion['fecha_formateada'] = null;
                }
            }
            $respuesta = ['exito' => true, 'data' => $notificaciones];
            break;
            
        case 'marcar_leida':
            if (isset($_POST['id_notificacion'])) {
                $resultado = marcarNotificacionLeida($_POST['id_notificacion'], $_SESSION['id_usuario']);
                $respuesta = ['exito' => $resultado, 'mensaje' => $resultado ? 'Notificación marcada como leída' : 'Error al actualizar la notificación'];
            } else {
                $respuesta = ['exito' => false, 'mensaje' => 'ID de notificación no proporcionado'];
            }
            break;
            
        case 'marcar_todas_leidas':
            $resultado = marcarTodasLeidas($_SESSION['id_usuario']);
            $respuesta = ['exito' => $resultado, 'mensaje' => $resultado ? 'Todas las notificaciones marcadas como leídas' : 'Error al actualizar las notificaciones'];
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($respuesta);
    exit;
}

// Cargar la vista de notificaciones
$pagina = "notificacion";
if (is_file("vista/" . $pagina . ".php")) {
    if (!isset($_SESSION['id_usuario'])) {
        header("Location: ?pagina=login");
        exit;
    }
    
    try {
        // Registrar en bitácora
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            '2', // ID del módulo de notificaciones
            'CONSULTAR',
            'El usuario accedió al módulo de Notificaciones',
            'baja'
        );
        
        // Obtener notificaciones para la vista
        $notificaciones = getNotificacionesUsuario($_SESSION['id_usuario']);
        
        // Verificar si hay un error al obtener las notificaciones
        if ($notificaciones === false) {
            throw new Exception('Error al cargar las notificaciones');
        }
        
        // Cargar la vista
        require_once("vista/" . $pagina . ".php");
        
    } catch (Exception $e) {
        error_log('Error en el controlador de notificaciones: ' . $e->getMessage());
        // Mostrar un mensaje de error amigable
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }
        echo "<div class='alert alert-danger'>Ocurrió un error al cargar las notificaciones. Por favor, intente más tarde.</div>";
    }
} else {
    if (!headers_sent()) {
        header('HTTP/1.1 404 Not Found');
    }
    echo "<div class='alert alert-warning'>La página solicitada no está disponible en este momento.</div>";
}
