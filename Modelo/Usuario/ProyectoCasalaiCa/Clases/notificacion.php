<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;

use PDO;
use Usuario\ProyectoCasalaiCa\Config\BD;

class NotificacionModel {
    private $pdo;
    public function __construct($pdo = null) { $this->pdo = $pdo; }
    
    // Constantes de validación
    const TIPOS_NOTIFICACION = ['pago', 'despacho', 'sistema', 'general', 'alerta'];
    const PRIORIDADES = ['baja', 'media', 'alta', 'urgente'];
    const ESTADOS_PAGO = ['procesado', 'pendiente', 'rechazado'];
    const ESTADOS_DESPACHO = ['enviado', 'preparado', 'cancelado'];
    const MAX_LONGITUD_TITULO = 200;
    const MAX_LONGITUD_MENSAJE = 1000;
    const MIN_ID_USUARIO = 1;
    const MAX_ID_USUARIO = 999999999;
    const MIN_ID_REFERENCIA = 1;
    const MAX_ID_REFERENCIA = 999999999;
    const MIN_ID_MODULO = 1;
    const MAX_ID_MODULO = 999;
    const MAX_LONGITUD_ACCION = 50;
    
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
    
    /**
     * Validar datos para crear/modificar notificación
     * Esta validación se usa para el método crear() y sus variantes
     */
    public function validarNotificacion($datos) {
        $errores = [];
        
        // Validar que $datos sea un array
        if (!is_array($datos)) {
            $errores['datos'] = 'Los datos deben ser un array';
            return $errores;
        }
        
        // Validar ID de usuario
        if (!isset($datos['id_usuario'])) {
            $errores['id_usuario'] = 'El ID del usuario es obligatorio';
        } else {
            $id_usuario = (int)$datos['id_usuario'];
            if ($id_usuario < self::MIN_ID_USUARIO || $id_usuario > self::MAX_ID_USUARIO) {
                $errores['id_usuario'] = 'El ID del usuario debe ser un número entre ' . self::MIN_ID_USUARIO . ' y ' . self::MAX_ID_USUARIO;
            }
        }
        
        // Validar tipo de notificación
        if (!isset($datos['tipo'])) {
            $errores['tipo'] = 'El tipo de notificación es obligatorio';
        } else {
            $tipo = trim($datos['tipo']);
            if (!in_array($tipo, self::TIPOS_NOTIFICACION)) {
                $errores['tipo'] = 'El tipo de notificación no es válido. Tipos permitidos: ' . implode(', ', self::TIPOS_NOTIFICACION);
            }
        }
        
        // Validar título
        if (!isset($datos['titulo'])) {
            $errores['titulo'] = 'El título es obligatorio';
        } else {
            $titulo = trim($datos['titulo']);
            if (empty($titulo)) {
                $errores['titulo'] = 'El título no puede estar vacío';
            } elseif (mb_strlen($titulo) > self::MAX_LONGITUD_TITULO) {
                $errores['titulo'] = 'El título no debe exceder los ' . self::MAX_LONGITUD_TITULO . ' caracteres';
            }
        }
        
        // Validar mensaje
        if (!isset($datos['mensaje'])) {
            $errores['mensaje'] = 'El mensaje es obligatorio';
        } else {
            $mensaje = trim($datos['mensaje']);
            if (empty($mensaje)) {
                $errores['mensaje'] = 'El mensaje no puede estar vacío';
            } elseif (mb_strlen($mensaje) > self::MAX_LONGITUD_MENSAJE) {
                $errores['mensaje'] = 'El mensaje no debe exceder los ' . self::MAX_LONGITUD_MENSAJE . ' caracteres';
            }
        }
        
        // Validar prioridad
        if (!isset($datos['prioridad'])) {
            $errores['prioridad'] = 'La prioridad es obligatoria';
        } else {
            $prioridad = trim($datos['prioridad']);
            if (!in_array($prioridad, self::PRIORIDADES)) {
                $errores['prioridad'] = 'La prioridad no es válida. Prioridades permitidas: ' . implode(', ', self::PRIORIDADES);
            }
        }
        
        // Validar ID de referencia (opcional)
        if (isset($datos['id_referencia']) && $datos['id_referencia'] !== null) {
            $id_referencia = (int)$datos['id_referencia'];
            if ($id_referencia < self::MIN_ID_REFERENCIA || $id_referencia > self::MAX_ID_REFERENCIA) {
                $errores['id_referencia'] = 'El ID de referencia debe ser un número entre ' . self::MIN_ID_REFERENCIA . ' y ' . self::MAX_ID_REFERENCIA;
            }
        }
        
        // Validar ID de módulo (opcional)
        if (isset($datos['id_modulo']) && $datos['id_modulo'] !== null) {
            $id_modulo = (int)$datos['id_modulo'];
            if ($id_modulo < self::MIN_ID_MODULO || $id_modulo > self::MAX_ID_MODULO) {
                $errores['id_modulo'] = 'El ID del módulo debe ser un número entre ' . self::MIN_ID_MODULO . ' y ' . self::MAX_ID_MODULO;
            }
        }
        
        // Validar acción (opcional)
        if (isset($datos['accion']) && $datos['accion'] !== null) {
            $accion = trim($datos['accion']);
            if (mb_strlen($accion) > self::MAX_LONGITUD_ACCION) {
                $errores['accion'] = 'La acción no debe exceder los ' . self::MAX_LONGITUD_ACCION . ' caracteres';
            }
        }
        
        return $errores;
    }
    
    /**
     * Validar datos para notificaciones de pago específicamente
     */
    public function validarNotificacionPago($datos) {
        $errores = [];
        
        // Validar datos básicos de notificación
        $errores_basicos = $this->validarNotificacion($datos);
        if (!empty($errores_basicos)) {
            return $errores_basicos;
        }
        
        // Validar que sea tipo 'pago'
        if ($datos['tipo'] !== 'pago') {
            $errores['tipo'] = 'Para notificación de pago, el tipo debe ser "pago"';
        }
        
        // Validar que la prioridad sea 'alta'
        if ($datos['prioridad'] !== 'alta') {
            $errores['prioridad'] = 'Para notificación de pago, la prioridad debe ser "alta"';
        }
        
        // Validar ID de pago (obligatorio para notificaciones de pago)
        if (!isset($datos['id_pago'])) {
            $errores['id_pago'] = 'El ID del pago es obligatorio para notificaciones de pago';
        } else {
            $id_pago = (int)$datos['id_pago'];
            if ($id_pago < self::MIN_ID_REFERENCIA || $id_pago > self::MAX_ID_REFERENCIA) {
                $errores['id_pago'] = 'El ID del pago debe ser un número entre ' . self::MIN_ID_REFERENCIA . ' y ' . self::MAX_ID_REFERENCIA;
            }
        }
        
        // Validar estado del pago (obligatorio)
        if (!isset($datos['estado'])) {
            $errores['estado'] = 'El estado del pago es obligatorio';
        } else {
            $estado = trim($datos['estado']);
            if (!in_array($estado, self::ESTADOS_PAGO)) {
                $errores['estado'] = 'El estado del pago no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_PAGO);
            }
        }
        
        return $errores;
    }
    
    /**
     * Validar datos para notificaciones de despacho específicamente
     */
    public function validarNotificacionDespacho($datos) {
        $errores = [];
        
        // Validar datos básicos de notificación
        $errores_basicos = $this->validarNotificacion($datos);
        if (!empty($errores_basicos)) {
            return $errores_basicos;
        }
        
        // Validar que sea tipo 'despacho'
        if ($datos['tipo'] !== 'despacho') {
            $errores['tipo'] = 'Para notificación de despacho, el tipo debe ser "despacho"';
        }
        
        // Validar que la prioridad sea 'media'
        if ($datos['prioridad'] !== 'media') {
            $errores['prioridad'] = 'Para notificación de despacho, la prioridad debe ser "media"';
        }
        
        // Validar ID de despacho (obligatorio para notificaciones de despacho)
        if (!isset($datos['id_despacho'])) {
            $errores['id_despacho'] = 'El ID del despacho es obligatorio para notificaciones de despacho';
        } else {
            $id_despacho = (int)$datos['id_despacho'];
            if ($id_despacho < self::MIN_ID_REFERENCIA || $id_despacho > self::MAX_ID_REFERENCIA) {
                $errores['id_despacho'] = 'El ID del despacho debe ser un número entre ' . self::MIN_ID_REFERENCIA . ' y ' . self::MAX_ID_REFERENCIA;
            }
        }
        
        // Validar estado del despacho (obligatorio)
        if (!isset($datos['estado'])) {
            $errores['estado'] = 'El estado del despacho es obligatorio';
        } else {
            $estado = trim($datos['estado']);
            if (!in_array($estado, self::ESTADOS_DESPACHO)) {
                $errores['estado'] = 'El estado del despacho no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_DESPACHO);
            }
        }
        
        return $errores;
    }
    
    /**
     * Validar datos para consultar notificaciones
     */
    public function validarConsultarNotificaciones($datos) {
        $errores = [];
        
        // Validar que $datos sea un array
        if (!is_array($datos)) {
            $errores['datos'] = 'Los datos deben ser un array';
            return $errores;
        }
        
        // Validar ID de usuario (obligatorio para consulta)
        if (!isset($datos['id_usuario'])) {
            $errores['id_usuario'] = 'El ID del usuario es obligatorio para consultar notificaciones';
        } else {
            $id_usuario = (int)$datos['id_usuario'];
            if ($id_usuario < self::MIN_ID_USUARIO || $id_usuario > self::MAX_ID_USUARIO) {
                $errores['id_usuario'] = 'El ID del usuario debe ser un número entre ' . self::MIN_ID_USUARIO . ' y ' . self::MAX_ID_USUARIO;
            }
        }
        
        // Validar límite (opcional)
        if (isset($datos['limite'])) {
            $limite = (int)$datos['limite'];
            if ($limite < 1 || $limite > 100) {
                $errores['limite'] = 'El límite debe ser un número entre 1 y 100';
            }
        }
        
        // Validar estado leído (opcional)
        if (isset($datos['leido'])) {
            $leido = $datos['leido'];
            if (!is_bool($leido) && $leido !== '0' && $leido !== '1') {
                $errores['leido'] = 'El estado leído debe ser verdadero, falso, 0 o 1';
            }
        }
        
        return $errores;
    }
    
    /**
     * Validar datos para marcar notificación como leída
     */
    public function validarMarcarLeida($datos) {
        $errores = [];
        
        // Validar que $datos sea un array
        if (!is_array($datos)) {
            $errores['datos'] = 'Los datos deben ser un array';
            return $errores;
        }
        
        // Validar ID de notificación (obligatorio)
        if (!isset($datos['id_notificacion'])) {
            $errores['id_notificacion'] = 'El ID de la notificación es obligatorio';
        } else {
            $id_notificacion = (int)$datos['id_notificacion'];
            if ($id_notificacion < self::MIN_ID_REFERENCIA || $id_notificacion > self::MAX_ID_REFERENCIA) {
                $errores['id_notificacion'] = 'El ID de la notificación debe ser un número entre ' . self::MIN_ID_REFERENCIA . ' y ' . self::MAX_ID_REFERENCIA;
            }
        }
        
        // Validar ID de usuario (obligatorio para seguridad)
        if (!isset($datos['id_usuario'])) {
            $errores['id_usuario'] = 'El ID del usuario es obligatorio';
        } else {
            $id_usuario = (int)$datos['id_usuario'];
            if ($id_usuario < self::MIN_ID_USUARIO || $id_usuario > self::MAX_ID_USUARIO) {
                $errores['id_usuario'] = 'El ID del usuario debe ser un número entre ' . self::MIN_ID_USUARIO . ' y ' . self::MAX_ID_USUARIO;
            }
        }
        
        return $errores;
    }
    
    /**
     * Validar datos para marcar todas las notificaciones como leídas
     */
    public function validarMarcarTodasLeidas($datos) {
        $errores = [];
        
        // Validar que $datos sea un array
        if (!is_array($datos)) {
            $errores['datos'] = 'Los datos deben ser un array';
            return $errores;
        }
        
        // Validar ID de usuario (obligatorio)
        if (!isset($datos['id_usuario'])) {
            $errores['id_usuario'] = 'El ID del usuario es obligatorio';
        } else {
            $id_usuario = (int)$datos['id_usuario'];
            if ($id_usuario < self::MIN_ID_USUARIO || $id_usuario > self::MAX_ID_USUARIO) {
                $errores['id_usuario'] = 'El ID del usuario debe ser un número entre ' . self::MIN_ID_USUARIO . ' y ' . self::MAX_ID_USUARIO;
            }
        }
        
        return $errores;
    }
}
