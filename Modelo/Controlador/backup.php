<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Incluir el autoloader de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Mostrar errores de inicio de sesión si existen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(403);
    die('Acceso no autorizado. Por favor inicie sesión.');
}

define('MODULO_BACKUP', 20);

// Usar las clases con sus namespaces completos
use Usuario\ProyectoCasalaiCa\Clases\Backup;
use Usuario\ProyectoCasalaiCa\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Config\Config\BD;



// Función simple de depuración a archivo
function backup_debug_log($mensaje) {
    $logDir = __DIR__ . '/../../db/respaldo/';
    if (!is_dir($logDir)) { 
        @mkdir($logDir, 0775, true); 
    }
    $logFile = $logDir . 'backup_debug.log';
    @file_put_contents($logFile, '[' . date('c') . "] CONTROLADOR: " . $mensaje . "\n", FILE_APPEND);
}

// Capturar errores fatales y registrar en log (y devolver JSON si aplica)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $msg = 'FATAL: ' . $error['message'] . ' en ' . $error['file'] . ':' . $error['line'];
        backup_debug_log($msg);
        if (isset($_GET['accion']) && $_GET['accion'] === 'generar') {
            if (!headers_sent()) { header('Content-Type: application/json'); }
            echo json_encode(['success' => false, 'error' => 'Error fatal en el servidor.', 'detalle' => $msg]);
        }
    }
});

// Listar archivos de respaldo
if (isset($_GET['accion']) && $_GET['accion'] === 'listar') {
    $backup = new Backup();
    $archivos = [];
    $ruta = __DIR__ . '/../db/respaldo/';
    
    if (is_dir($ruta)) {
        $files = scandir($ruta);
        foreach ($files as $file) {
            if (preg_match('/\.sql$/', $file)) {
                $filePath = $ruta . $file;
                $archivos[] = [
                    'nombre' => $file,
                    'tamano' => filesize($filePath),
                    'fecha_modificacion' => date('Y-m-d H:i:s', filemtime($filePath)),
                    'tipo' => strpos($file, 'seguridad') !== false ? 'Seguridad' : 'Principal'
                ];
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($archivos);
    exit;
}

// Eliminar archivo de respaldo
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && !empty($_GET['archivo'])) {
    $archivo = basename($_GET['archivo']); // Prevenir directory traversal
    $ruta = __DIR__ . '/../db/respaldo/' . $archivo;
    
    if (file_exists($ruta) && is_file($ruta)) {
        if (unlink($ruta)) {
            echo json_encode(['success' => true, 'message' => 'Archivo eliminado correctamente']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'No se pudo eliminar el archivo']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Archivo no encontrado']);
    }
    exit;
}

if (isset($_GET['accion'])) {

    if ($_GET['accion'] === 'generar') {
        header('Content-Type: application/json');
        try {
            // asegurar dir de logs
            backup_debug_log('--- INICIO GENERAR ---');
            $tipo = isset($_GET['tipo']) && $_GET['tipo'] === 'S' ? 'S' : 'P';
            $backup = new Backup($tipo);
            $nombreArchivo = 'backup_' . ($tipo === 'S' ? 'seguridad' : 'principal') . '_' . date('Ymd_His') . '.sql';
            $ok = $backup->generar($nombreArchivo);
            $ruta = realpath(__DIR__ . '/../db/respaldo/' . $nombreArchivo);
            backup_debug_log("Acción GENERAR tipo={$tipo}, archivo={$nombreArchivo}, ok=" . ($ok ? '1' : '0') . ", ruta=" . ($ruta ?: 'N/A'));

            if ($ok && $ruta && file_exists($ruta)) {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    if (isset($_SESSION['id_usuario'])) {
                        $bitacoraModel = new Bitacora();
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_BACKUP,
                            'GENERAR',
                            'Se generó un respaldo ' . ($tipo === 'S' ? 'de seguridad' : 'principal') . ': ' . $nombreArchivo,
                            'media'
                        );
                    }
                }
                echo json_encode(['success' => true, 'archivo' => $nombreArchivo]);
            } else {
                $logFile = __DIR__ . '/../db/respaldo/backup_debug.log';
                $logMsg = file_exists($logFile) ? @file_get_contents($logFile) : '';
                backup_debug_log('Error al GENERAR respaldo (ok=0 o archivo no existe).');
                echo json_encode([
                    'success' => false,
                    'error' => 'Error al generar el respaldo. Ver debug.',
                    'debug' => $logMsg
                ]);
            }
        } catch (Throwable $e) {
            backup_debug_log('Excepción en GENERAR: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Excepción en el servidor.',
                'detalle' => $e->getMessage()
            ]);
        }
        exit;
    }

    // ... resto del código igual ...

    if ($_GET['accion'] === 'descargar') {
        $archivo = $_GET['archivo'] ?? '';
        $ruta = realpath(__DIR__ . '/../db/respaldo/' . $archivo);
        backup_debug_log('Acción DESCARGAR archivo=' . $archivo . ', existe=' . ((bool)$ruta && file_exists($ruta) ? '1' : '0'));
        if ($archivo && file_exists($ruta)) {
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . basename($archivo) . '"');
            header('Content-Length: ' . filesize($ruta));
            readfile($ruta);
            exit;
        } else {
            echo "Archivo no encontrado";
            exit;
        }
    }

    switch ($_GET['accion']) {
        case 'consultar':
        $ruta = realpath(__DIR__ . '/../db/respaldo/' . $archivo);
        header('Content-Type: application/json');
        backup_debug_log('Acción RESTAURAR archivo=' . $archivo . ', existe=' . ($ruta && file_exists($ruta) ? '1' : '0'));
        if ($archivo && file_exists($ruta)) {
            $backup = new Backup();
            $ok = $backup->restaurar($archivo);
            backup_debug_log('Resultado RESTAURAR ok=' . ($ok ? '1' : '0'));
            if ($ok) {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    if (isset($_SESSION['id_usuario'])) {
                        $bitacoraModel = new Bitacora();
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_BACKUP,
                            'RESTAURAR',
                            'Se restauró el respaldo: ' . $archivo,
                            'alta'
                        );
                    }
                }
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al restaurar']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Archivo no encontrado']);
        }
        exit;
    }
}

// Renderizado de la vista
$pagina = "backup";
if (is_file("vista/" . $pagina . ".php")) {
    $backup = new Backup();
    $backups = $backup->listar();
    require_once("vista/" . $pagina . ".php");
    if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                MODULO_BACKUP,
                'ACCESAR',
                'El usuario accedió al módulo de Backup',
                'media'
            );
        }
    }
} else {
    echo "Página en construcción";
}
?>