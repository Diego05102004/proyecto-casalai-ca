<?php
require_once 'Config/Config.php';
class NotificacionModel {
    private $bd;
    
    public function __construct() {
        $this->bd = new BD('S');
    }
    
    public function crear($id_usuario, $tipo, $titulo, $mensaje, $prioridad, $id_modulo, $accion, $id_referencia = null) {
        $pdo = $this->bd->getConexion();
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
                        AND n.titulo = :titulo2
                        AND n.mensaje = :mensaje2
                        AND n.id_referencia = :id_referencia2
                  )
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':titulo2', $titulo);
            $stmt->bindParam(':mensaje', $mensaje);
            $stmt->bindParam(':mensaje2', $mensaje);
            $stmt->bindParam(':id_referencia', $id_referencia);
            $stmt->bindParam(':id_referencia2', $id_referencia);
            $stmt->bindParam(':prioridad', $prioridad);
            $stmt->bindParam(':id_modulo', $id_modulo);
            $stmt->bindParam(':accion', $accion);

            $result = $stmt->execute();
            $this->bd->cerrar();
            return $result;
        } catch (Exception $e) {
            if (isset($this->bd)) {
                $this->bd->cerrar();
            }
            error_log("Error en crear notificación: " . $e->getMessage());
            return false;
        }
    }
    
    // Métodos específicos para tipos comunes
    public function obtenerNotificacionesUsuario($id_usuario) {
        $pdo = $this->bd->getConexion();
        try {
            $sql = "SELECT id_notificacion, titulo, mensaje, fecha_hora as fecha_creacion, leido 
                    FROM tbl_notificaciones 
                    WHERE id_usuario = :id_usuario 
                    ORDER BY fecha_hora DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->bd->cerrar();
            return $result;
        } catch (Exception $e) {
            if (isset($this->bd)) {
                $this->bd->cerrar();
            }
            error_log("Error al obtener notificaciones: " . $e->getMessage());
            return [];
        }
    }
    
    public function marcarComoLeida($id_notificacion, $id_usuario) {
        $pdo = $this->bd->getConexion();
        try {
            $sql = "UPDATE tbl_notificaciones 
                    SET leido = 1 
                    WHERE id_notificacion = :id_notificacion 
                    AND id_usuario = :id_usuario";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_notificacion', $id_notificacion);
            $stmt->bindParam(':id_usuario', $id_usuario);
            
            $result = $stmt->execute();
            $this->bd->cerrar();
            return $result;
        } catch (Exception $e) {
            if (isset($this->bd)) {
                $this->bd->cerrar();
            }
            error_log("Error al marcar como leída: " . $e->getMessage());
            return false;
        }
    }
    
    public function marcarTodasComoLeidas($id_usuario) {
        $pdo = $this->bd->getConexion();
        try {
            $sql = "UPDATE tbl_notificaciones 
                    SET leido = 1 
                    WHERE id_usuario = :id_usuario";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_usuario', $id_usuario);
            
            $result = $stmt->execute();
            $this->bd->cerrar();
            return $result;
        } catch (Exception $e) {
            if (isset($this->bd)) {
                $this->bd->cerrar();
            }
            error_log("Error al marcar todas como leídas: " . $e->getMessage());
            return false;
        }
    }
    
    public function notificarPago($id_usuario, $id_pago, $estado) {
        $titulo = "Estado de pago actualizado";
        $mensaje = "Su pago ha sido " . ($estado == 'procesado' ? "aprobado" : ($estado == 'pendiente' ? "recibido" : "rechazado"));
        return $this->crear($id_usuario, 'pago', $titulo, $mensaje, 'alta', 3, 'ver_pagos', $id_pago);
    }
    
    public function notificarDespacho($id_usuario, $id_despacho, $estado) {
        $titulo = "Estado de despacho";
        $mensaje = "Su pedido ha sido " . ($estado == 'enviado' ? "despachado" : "preparado para envío");
        return $this->crear($id_usuario, 'despacho', $titulo, $mensaje, 'media', 4, 'ver_despachos', $id_despacho);
    }

    public function marcarComoLeido($id_notificacion) {
        $pdo = $this->bd->getConexion();
        try {
            $sql = "UPDATE tbl_notificaciones SET leido = 1 WHERE id_notificacion = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$id_notificacion]);
            $this->bd->cerrar();
            return $result;
        } catch (Exception $e) {
            if (isset($this->bd)) {
                $this->bd->cerrar();
            }
            error_log("Error al marcar como leído: " . $e->getMessage());
            return false;
        }
    }
    
    public function __destruct() {
        if (isset($this->bd)) {
            $this->bd->cerrar();
        }
    }
}
