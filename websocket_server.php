<?php
require 'vendor/autoload.php';
require_once __DIR__ . '/Modelo/Config/database.php';
require_once __DIR__ . '/Modelo/Config/Config.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Usuario\ProyectoCasalaiCa\Config\BD;

class NotificacionWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $usuarios = []; // Almacena las conexiones por ID de usuario

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nueva conexión! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        if (!is_array($data)) {
            return;
        }

        if (isset($data['tipo'])) {
            if ($data['tipo'] === 'autenticar' && isset($data['usuario_id'])) {
                $usuarioId = (int)$data['usuario_id'];
                $this->usuarios[$usuarioId] = $from;
                echo "Usuario autenticado: {$usuarioId}\n";

                // Enviar estado inicial de notificaciones no leídas a este usuario
                $this->enviarNotificacionesIniciales($from, $usuarioId);
                return;
            }

            if ($data['tipo'] === 'marcar_leida') {
                $this->procesarMarcarLeida($from, $data);
                return;
            }

            if ($data['tipo'] === 'ping_nuevas') {
                $this->procesarPingNuevas($from);
                return;
            }
        }

        if (isset($data['para_usuario_id']) && isset($this->usuarios[$data['para_usuario_id']])) {
            $this->usuarios[$data['para_usuario_id']]->send(json_encode($data));
        }
    }

    protected function enviarNotificacionesIniciales(ConnectionInterface $conn, int $usuarioId): void
    {
        try {
            $bd_seguridad = new BD('S');
            $pdo_seguridad = $bd_seguridad->getConexion();

            $stmt = $pdo_seguridad->prepare("SELECT * FROM tbl_notificaciones WHERE id_usuario = :id_usuario AND leido = 0 ORDER BY fecha_hora DESC LIMIT 5");
            $stmt->bindParam(':id_usuario', $usuarioId, \PDO::PARAM_INT);
            $stmt->execute();
            $notificaciones = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Formatear fecha para compatibilidad con el frontend
            foreach ($notificaciones as &$notif) {
                $src = $notif['fecha_hora'] ?? $notif['fecha_creacion'] ?? null;
                if ($src) {
                    $timestamp = strtotime($src);
                    $notif['fecha_formateada'] = $timestamp !== false
                        ? date('d/m/Y H:i:s', $timestamp)
                        : $src;
                } else {
                    $notif['fecha_formateada'] = '';
                }
            }
            unset($notif);

            $conn->send(json_encode([
                'tipo' => 'sync_inicial',
                'notificaciones' => $notificaciones,
                'count' => count($notificaciones),
            ]));
        } catch (\Throwable $e) {
            echo "Error en enviarNotificacionesIniciales: " . $e->getMessage() . "\n";
        } finally {
            if (isset($bd_seguridad)) {
                $bd_seguridad->cerrar();
            }
        }
    }

    protected function procesarPingNuevas(ConnectionInterface $from): void
    {
        // Determinar el usuario asociado a esta conexión
        $usuarioId = array_search($from, $this->usuarios, true);
        if ($usuarioId === false) {
            return;
        }

        try {
            $bd_seguridad = new BD('S');
            $pdo_seguridad = $bd_seguridad->getConexion();

            $stmt = $pdo_seguridad->prepare("SELECT * FROM tbl_notificaciones WHERE id_usuario = :id_usuario AND leido = 0 ORDER BY fecha_hora DESC LIMIT 5");
            $stmt->bindParam(':id_usuario', $usuarioId, \PDO::PARAM_INT);
            $stmt->execute();
            $notificaciones = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($notificaciones as &$notif) {
                $src = $notif['fecha_hora'] ?? $notif['fecha_creacion'] ?? null;
                if ($src) {
                    $timestamp = strtotime($src);
                    $notif['fecha_formateada'] = $timestamp !== false
                        ? date('d/m/Y H:i:s', $timestamp)
                        : $src;
                } else {
                    $notif['fecha_formateada'] = '';
                }
            }
            unset($notif);

            $from->send(json_encode([
                'tipo' => 'nueva_notificacion',
                'notificaciones' => $notificaciones,
                'count' => count($notificaciones),
            ]));
        } catch (\Throwable $e) {
            echo "Error en procesarPingNuevas: " . $e->getMessage() . "\n";
        } finally {
            if (isset($bd_seguridad)) {
                $bd_seguridad->cerrar();
            }
        }
    }

    protected function procesarMarcarLeida(ConnectionInterface $from, array $data) {
        if (!isset($data['id_notificacion'])) {
            return;
        }

        $usuarioId = array_search($from, $this->usuarios, true);
        if ($usuarioId === false) {
            return;
        }

        $idNotificacion = (int)$data['id_notificacion'];

        try {
            echo "procesarMarcarLeida: usuarioId={$usuarioId}, idNotificacion={$idNotificacion}\n";
            $bd_seguridad = new BD('S');
            $pdo_seguridad = $bd_seguridad->getConexion();

            $stmt = $pdo_seguridad->prepare("UPDATE tbl_notificaciones SET leido = 1 WHERE id_notificacion = :id AND id_usuario = :usuario");
            $stmt->bindParam(':id', $idNotificacion, \PDO::PARAM_INT);
            $stmt->bindParam(':usuario', $usuarioId, \PDO::PARAM_INT);
            $stmt->execute();

            $ok = $stmt->rowCount() > 0;
            echo "procesarMarcarLeida: filas actualizadas=" . $stmt->rowCount() . "\n";

            $respuesta = [
                'tipo' => 'marcar_leida',
                'ok' => $ok,
                'id_notificacion' => $idNotificacion,
            ];

            if (!$ok) {
                $respuesta['error'] = 'No se actualizó ninguna fila';
            }

            $from->send(json_encode($respuesta));
        } catch (\Throwable $e) {
            echo "Error en procesarMarcarLeida: " . $e->getMessage() . "\n";
            $from->send(json_encode([
                'tipo' => 'marcar_leida',
                'ok' => false,
                'id_notificacion' => $idNotificacion,
                'error' => $e->getMessage(),
            ]));
        } finally {
            if (isset($bd_seguridad)) {
                $bd_seguridad->cerrar();
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Eliminar de usuarios conectados
        if ($usuarioId = array_search($conn, $this->usuarios, true)) {
            unset($this->usuarios[$usuarioId]);
        }
        $this->clients->detach($conn);
        echo "Conexión cerrada! ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Iniciar el servidor
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new NotificacionWebSocket()
        )
    ),
    8080
);

echo "Servidor WebSocket iniciado en el puerto 8080\n";
$server->run();