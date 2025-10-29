<?php
require_once __DIR__ . '/../config/config.php';
class NotificacionModel {
    public function __construct() {}
    
public function crear($id_usuario, $tipo, $titulo, $mensaje, $prioridad, $id_modulo, $accion, $id_referencia = null) {
    $bd_seguridad = new BD('S');
    $pdo_seguridad = $bd_seguridad->getConexion();
    try {
        $sql = "
            INSERT INTO tbl_notificaciones (id_usuario, tipo, titulo, mensaje, id_referencia, prioridad)
            SELECT u.id_usuario, :tipo, :titulo, :mensaje, :id_referencia, :prioridad
            FROM tbl_usuarios u
            INNER JOIN tbl_rol r ON u.id_rol = r.id_rol
            INNER JOIN tbl_permisos p ON r.id_rol = p.id_rol
            WHERE p.id_modulo = :id_modulo 
              AND p.accion = :accion
              AND p.estatus = 'Permitido'
              -- Evita duplicados: solo inserta si no existe la misma notificación
              AND NOT EXISTS (
                  SELECT 1 
                  FROM tbl_notificaciones n 
                  WHERE n.id_usuario = u.id_usuario
                    AND n.titulo = :titulo
                    AND n.mensaje = :mensaje
                    AND n.id_referencia = :id_referencia
              )
        ";

        $stmt = $pdo_seguridad->prepare($sql);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':mensaje', $mensaje);
        $stmt->bindParam(':id_referencia', $id_referencia);
        $stmt->bindParam(':prioridad', $prioridad);
        $stmt->bindParam(':id_modulo', $id_modulo);
        $stmt->bindParam(':accion', $accion);

        return $stmt->execute();
    } finally {
        if (isset($bd_seguridad)) { $bd_seguridad->cerrar(); }
    }
}

    
    // Métodos específicos para tipos comunes
    public function notificarPago($id_usuario, $id_pago, $estado) {
        $titulo = "Estado de pago actualizado";
        $mensaje = "Su pago ha sido " . ($estado == 'procesado' ? "aprobado" : ($estado == 'pendiente' ? "recibido" : "rechazado"));
        return $this->crear($id_usuario, 'pago', $titulo, $mensaje, $id_pago, 'alta');
    }
    
    public function notificarDespacho($id_usuario, $id_despacho, $estado) {
        $titulo = "Estado de despacho";
        $mensaje = "Su pedido ha sido " . ($estado == 'enviado' ? "despachado" : "preparado para envío");
        return $this->crear($id_usuario, 'despacho', $titulo, $mensaje, $id_despacho, 'media');
    }

    // Agregar este método a NotificacionModel (notificacion.php)
    public function marcarComoLeido($id_notificacion) {
        $bd_seguridad = new BD('S');
        $pdo_seguridad = $bd_seguridad->getConexion();
        try {
            $stmt = $pdo_seguridad->prepare("UPDATE tbl_notificaciones SET leido = 1 WHERE id_notificacion = ?");
            return $stmt->execute([$id_notificacion]);
        } finally {
            if (isset($bd_seguridad)) { $bd_seguridad->cerrar(); }
        }
    }
}