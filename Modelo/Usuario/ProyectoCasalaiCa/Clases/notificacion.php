<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;

use PDO;
use Usuario\ProyectoCasalaiCa\Config\BD;

class NotificacionModel {
    private $pdo;
    public function __construct($pdo = null) { $this->pdo = $pdo; }
    
    public function crear($id_usuario, $tipo, $titulo, $mensaje, $prioridad, $id_modulo, $accion, $id_referencia = null) {
        $bd_seguridad = null; $pdo_seguridad = $this->pdo; $created = false;
        if (!($pdo_seguridad instanceof PDO) && !is_object($pdo_seguridad)) {
            $bd_seguridad = new BD('S');
            $pdo_seguridad = $bd_seguridad->getConexion();
            $created = true;
        }
        try {
            // Inserción directa para un usuario específico si no se define módulo/acción
            if ($id_modulo === null || $accion === null) {
                $sql = "
                    INSERT INTO tbl_notificaciones (id_usuario, tipo, titulo, mensaje, id_referencia, prioridad)
                    VALUES (:id_usuario, :tipo, :titulo, :mensaje, :id_referencia, :prioridad)
                ";
                $stmt = $pdo_seguridad->prepare($sql);
                $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
                $stmt->bindParam(':tipo', $tipo);
                $stmt->bindParam(':titulo', $titulo);
                $stmt->bindParam(':mensaje', $mensaje);
                $stmt->bindParam(':id_referencia', $id_referencia);
                $stmt->bindParam(':prioridad', $prioridad);
                return $stmt->execute();
            }

            // Inserción basada en permisos
            $sql = "
                INSERT INTO tbl_notificaciones (id_usuario, tipo, titulo, mensaje, id_referencia, prioridad)
                SELECT u.id_usuario, :tipo, :titulo, :mensaje, :id_referencia, :prioridad
                FROM tbl_usuarios u
                INNER JOIN tbl_rol r ON u.id_rol = r.id_rol
                INNER JOIN tbl_permisos p ON r.id_rol = p.id_rol
                WHERE p.id_modulo = :id_modulo 
                AND p.accion = :accion
                AND p.estatus = 'Permitido'
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
            if ($bd_seguridad) { $bd_seguridad->cerrar(); }
        }
    }

    public function notificarPago($id_usuario, $id_pago, $estado) {
        $titulo = "Estado de pago actualizado";
        $mensaje = "Su pago ha sido " . ($estado == 'procesado' ? "aprobado" : ($estado == 'pendiente' ? "recibido" : "rechazado"));
        // prioridad = 'alta', sin módulo/acción, id_referencia = $id_pago
        return $this->crear($id_usuario, 'pago', $titulo, $mensaje, 'alta', null, null, $id_pago);
    }
    
    public function notificarDespacho($id_usuario, $id_despacho, $estado) {
        $titulo = "Estado de despacho";
        $mensaje = "Su pedido ha sido " . ($estado == 'enviado' ? "despachado" : "preparado para envío");
        // prioridad = 'media', sin módulo/acción, id_referencia = $id_despacho
        return $this->crear($id_usuario, 'despacho', $titulo, $mensaje, 'media', null, null, $id_despacho);
    }


    // Método esperado por el controlador: obtener notificaciones de un usuario
    public function obtenerNotificacionesUsuario($id_usuario) {
        $bd_seguridad = null; $pdo_seguridad = $this->pdo; $created = false;
        if (!($pdo_seguridad instanceof PDO) && !is_object($pdo_seguridad)) {
            $bd_seguridad = new BD('S');
            $pdo_seguridad = $bd_seguridad->getConexion();
            $created = true;
        }
        try {
            // Trae las últimas notificaciones del usuario (puedes ajustar LIMIT si hace falta)
            $sql = "SELECT * FROM tbl_notificaciones WHERE id_usuario = :id_usuario ORDER BY id_notificacion DESC";
            $st = $pdo_seguridad->prepare($sql);
            $st->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if ($bd_seguridad) { $bd_seguridad->cerrar(); }
        }
    }

    // Firma utilizada por el controlador: marcar como leída por id y usuario
    public function marcarComoLeida($id_notificacion, $id_usuario) {
        $bd_seguridad = null; $pdo_seguridad = $this->pdo; $created = false;
        if (!($pdo_seguridad instanceof PDO) && !is_object($pdo_seguridad)) {
            $bd_seguridad = new BD('S');
            $pdo_seguridad = $bd_seguridad->getConexion();
            $created = true;
        }
        try {
            $sql = "UPDATE tbl_notificaciones SET leido = 1 WHERE id_notificacion = :id AND id_usuario = :u";
            $st = $pdo_seguridad->prepare($sql);
            return $st->execute([':id' => $id_notificacion, ':u' => $id_usuario]);
        } finally {
            if ($bd_seguridad) { $bd_seguridad->cerrar(); }
        }
    }

    // Marcar todas las notificaciones como leídas para un usuario
    public function marcarTodasComoLeidas($id_usuario) {
        $bd_seguridad = null; $pdo_seguridad = $this->pdo; $created = false;
        if (!($pdo_seguridad instanceof PDO) && !is_object($pdo_seguridad)) {
            $bd_seguridad = new BD('S');
            $pdo_seguridad = $bd_seguridad->getConexion();
            $created = true;
        }
        try {
            $sql = "UPDATE tbl_notificaciones SET leido = 1 WHERE id_usuario = :u AND leido = 0";
            $st = $pdo_seguridad->prepare($sql);
            return $st->execute([':u' => $id_usuario]);
        } finally {
            if ($bd_seguridad) { $bd_seguridad->cerrar(); }
        }
    }
}
