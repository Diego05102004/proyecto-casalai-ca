<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Incluir el autoloader de Composer (ruta absoluta desde la raíz del proyecto)
$rootDir = realpath(__DIR__ . '/../..');
require_once $rootDir . '/vendor/autoload.php';

// Incluir manualmente la clase Backup para asegurar que se cargue
require_once $rootDir . '/Modelo/Usuario/ProyectoCasalaiCa/Clases/Backup.php';

// Incluir la configuración de la base de datos
require_once $rootDir . '/Modelo/Config/database.php';

// Usar las clases necesarias
use Usuario\ProyectoCasalaiCa\Clases\Backup as BackupClass;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(403);
    die('Acceso no autorizado. Por favor inicie sesión.');
}

define('MODULO_BACKUP', 20);


// Función simple de depuración a archivo
function backup_debug_log($mensaje) {
    $logDir = __DIR__ . '/../Modelo/db/respaldo/';
    if (!is_dir($logDir)) { 
        @mkdir($logDir, 0775, true); 
    }
    $logFile = $logDir . 'backup_debug.log';
    @file_put_contents($logFile, '[' . date('c') . "] CONTROLADOR: " . $mensaje . "\n", FILE_APPEND);
}



// Listar archivos de respaldo
if (isset($_GET['accion']) && $_GET['accion'] === 'listar') {
    try {
        $backup = new Usuario\ProyectoCasalaiCa\Clases\Backup();
        $ruta = dirname(__DIR__, 2) . '/db/respaldo/';
        $archivos = [];
        
        if (!is_dir($ruta)) {
            if (!mkdir($ruta, 0775, true)) {
                throw new Exception('No se pudo crear el directorio de respaldos');
            }
        }

        $files = scandir($ruta);
        if ($files === false) {
            throw new Exception('No se pudo leer el directorio de respaldos');
        }

        foreach ($files as $file) {
            if (preg_match('/\.sql$/i', $file)) {
                $filePath = $ruta . $file;
                $archivos[] = [
                    'nombre' => $file,
                    'tamano' => filesize($filePath),
                    'fecha_modificacion' => date('Y-m-d H:i:s', filemtime($filePath)),
                    'tipo' => strpos(strtolower($file), 'seguridad') !== false ? 'Seguridad' : 'Principal'
                ];
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($archivos);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Descargar archivo de respaldo
if (isset($_GET['accion']) && $_GET['accion'] === 'descargar' && !empty($_GET['archivo'])) {
    $archivo = basename($_GET['archivo']); // Prevenir directory traversal
    $ruta = dirname(__DIR__, 2) . '/db/respaldo/' . $archivo;
    
    if (file_exists($ruta) && is_file($ruta)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($ruta) . '"');
        header('Content-Length: ' . filesize($ruta));
        header('Pragma: public');
        header('Cache-Control: must-revalidate');
        header('Expires: 0');
        
        readfile($ruta);
        exit;
    } else {
        http_response_code(404);
        echo 'Archivo no encontrado';
        exit;
    }
}

// Eliminar archivo de respaldo
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && !empty($_GET['archivo'])) {
    $archivo = basename($_GET['archivo']); // Prevenir directory traversal
    $ruta = dirname(__DIR__, 2) . '/db/respaldo/' . $archivo;
    
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

// Restaurar base de datos desde un respaldo
if (isset($_GET['accion']) && $_GET['accion'] === 'restaurar' && !empty($_GET['archivo'])) {
    try {
        $archivo = basename($_GET['archivo']); // Prevenir directory traversal
        $ruta = dirname(__DIR__, 2) . '/db/respaldo/' . $archivo;
        
        if (!file_exists($ruta)) {
            throw new Exception('El archivo de respaldo no existe');
        }
        
        // Determinar el tipo de backup (seguridad o principal)
        $tipo = (strpos(strtolower($archivo), 'seguridad') !== false) ? 'S' : 'P';
        $backup = new Usuario\ProyectoCasalaiCa\Clases\Backup($tipo);
        
        if ($backup->restaurar($archivo)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Base de datos restaurada correctamente',
                'tipo' => ($tipo === 'S') ? 'Seguridad' : 'Principal'
            ]);
        } else {
            throw new Exception('Error al restaurar la base de datos');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Generar respaldo
if (isset($_GET['accion']) && $_GET['accion'] === 'generar') {
        header('Content-Type: application/json');
        
        try {
            // Validar tipo de respaldo
            $tipo = isset($_GET['tipo']) && $_GET['tipo'] === 'S' ? 'S' : 'P';
            $tipoTexto = $tipo === 'S' ? 'seguridad' : 'principal';
            
            // Crear instancia y generar respaldo
            $backup = new Usuario\ProyectoCasalaiCa\Clases\Backup($tipo);
            $nombreArchivo = 'backup_' . $tipoTexto . '_' . date('Ymd_His') . '.sql';
            
            // Generar el respaldo
            $resultado = $backup->generar($nombreArchivo);
            
            if ($resultado['success']) {
                // Respuesta exitosa
                echo json_encode([
                    'success' => true, 
                    'message' => 'Respaldo generado correctamente',
                    'archivo' => $nombreArchivo,
                    'tipo' => $tipoTexto,
                    'fecha' => date('Y-m-d H:i:s')
                ]);
            } else {
                // Error en la generación
                throw new Exception($resultado['error'] ?? 'Error desconocido al generar el respaldo');
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'error' => $e->getMessage(),
                'debug' => isset($resultado['debug']) ? $resultado['debug'] : null
            ]);
        }
        exit;
    }


// Renderizado de la vista
$pagina = "backup";
if (is_file("vista/" . $pagina . ".php")) {
    $backup = new Usuario\ProyectoCasalaiCa\Clases\Backup();
    $backups = $backup->listar();
    require_once("vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
?>