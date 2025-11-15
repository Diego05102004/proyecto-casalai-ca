<?php
namespace Usuario\ProyectoCasalaiCa\Clases;

use PDO;
use PDOException;
use RuntimeException;

class Backup {
    private $tipo;
    private $config;

    public function __construct($tipo = 'P') {
        $this->tipo = $tipo;
        $this->config = $this->getDatabaseConfig();
    }

    /**
     * Obtiene la configuración de la base de datos según el tipo
     */
    private function getDatabaseConfig() {
        if ($this->tipo === 'S') {
            return [
                'host' => 'localhost',
                'dbname' => 'seguridadlai',
                'user' => 'root',
                'pass' => '',
                'charset' => 'utf8mb4'
            ];
        } else {
            return [
                'host' => 'localhost',
                'dbname' => 'casalai',
                'user' => 'root',
                'pass' => '',
                'charset' => 'utf8mb4'
            ];
        }
    }

    /**
     * Genera un respaldo de la base de datos
     */
    /**
     * Genera un respaldo de la base de datos
     * 
     * @param string $nombreArchivo Nombre del archivo de respaldo
     * @return array [
     *     'success' => bool,
     *     'message' => string,
     *     'archivo' => string,
     *     'tamano' => int,
     *     'error' => string|null,
     *     'debug' => string|null
     * ]
     */
    public function generar($nombreArchivo) {
        $config = $this->config;
        $resultado = [
            'success' => false,
            'message' => '',
            'archivo' => $nombreArchivo,
            'tamano' => 0,
            'error' => null,
            'debug' => null
        ];
        
        // Configurar la ruta del archivo de respaldo
        $rutaCarpeta = __DIR__ . '/../../../../Modelo/db/respaldo/';
        if (!str_ends_with($nombreArchivo, '.sql')) {
            $nombreArchivo .= '.sql';
            $resultado['archivo'] = $nombreArchivo;
        }
        $rutaArchivo = $rutaCarpeta . $nombreArchivo;
    
    // Crear directorio si no existe
    if (!is_dir($rutaCarpeta)) {
        if (!mkdir($rutaCarpeta, 0775, true)) {
            $errorMsg = "No se pudo crear el directorio de respaldo: $rutaCarpeta";
            error_log($errorMsg);
            $resultado['error'] = $errorMsg;
            $resultado['message'] = 'Error al crear el directorio de respaldos';
            return $resultado;
        }
    }
    
    // Verificar permisos de escritura
    if (!is_writable($rutaCarpeta)) {
        error_log("El directorio no tiene permisos de escritura: $rutaCarpeta");
        return false;
    }
    
    // Configurar el comando mysqldump
    $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    
    // Verificar si existe mysqldump
    if (!file_exists($mysqldump)) {
        $errorMsg = "No se encontró el ejecutable de mysqldump en: $mysqldump";
        error_log($errorMsg);
        $resultado['error'] = $errorMsg;
        $resultado['message'] = 'No se encontró la herramienta de respaldo (mysqldump)';
        return $resultado;
    }
    
    // Configurar opciones de mysqldump
    $opciones = "--user={$config['user']} --password={$config['pass']} --host={$config['host']} --databases {$config['dbname']} --skip-add-drop-table --skip-comments --skip-set-charset --result-file=\"$rutaArchivo\" 2>&1";
    
    // Asegurar que las comillas estén correctamente escapadas para Windows
    $comando = '"'.$mysqldump.'" '.$opciones;
        
        // Preparar información de depuración
        $debugInfo = [
            'fecha' => date('Y-m-d H:i:s'),
            'tipo' => $this->tipo === 'S' ? 'Seguridad' : 'Principal',
            'base_datos' => $config['dbname'],
            'archivo_salida' => $rutaArchivo,
            'comando' => $comando,
            'salida' => []
        ];
        
        // Ejecutar el comando
        $output = [];
        $exitCode = 0;
        exec($comando, $output, $exitCode);
        
        // Agregar salida a la información de depuración
        $debugInfo['salida'] = $output;
        $debugInfo['codigo_salida'] = $exitCode;
        
        // Verificar si el archivo se creó correctamente
        $archivoExiste = file_exists($rutaArchivo);
        $tamanoArchivo = $archivoExiste ? filesize($rutaArchivo) : 0;
        
        // Actualizar información del resultado
        $resultado['tamano'] = $tamanoArchivo;
        $resultado['debug'] = json_encode($debugInfo, JSON_PRETTY_PRINT);
        
        if ($archivoExiste && $tamanoArchivo > 0) {
            // Éxito
            $resultado['success'] = true;
            $resultado['message'] = 'Respaldo generado correctamente';
            $debugInfo['estado'] = 'éxito';
            $debugInfo['tamano_archivo'] = $tamanoArchivo;
        } else {
            // Error
            $errorMsg = $archivoExiste ? 'El archivo de respaldo está vacío' : 'No se pudo crear el archivo de respaldo';
            $resultado['error'] = $errorMsg . ' (Código: ' . $exitCode . ')';
            $resultado['message'] = 'Error al generar el respaldo';
            $debugInfo['error'] = $errorMsg;
            $debugInfo['tamano_archivo'] = 0;
        }
        
        // Guardar registro de depuración
        $logFile = $rutaCarpeta . 'backup_debug.log';
        file_put_contents(
            $logFile, 
            json_encode($debugInfo, JSON_PRETTY_PRINT) . "\n\n", 
            FILE_APPEND
        );
        
        return $resultado;
    }

    /**
     * Restaura un respaldo de la base de datos
     *
     * @param string $nombreArchivo Nombre del archivo de respaldo
     * @return bool Verdadero si el respaldo se restauró correctamente, falso en caso contrario
     */
    /**
     * Restaura un respaldo de la base de datos
     */
    public function restaurar($nombreArchivo) {
        $config = $this->config;
        
        // Configurar la ruta del archivo de respaldo
        $rutaCarpeta = __DIR__ . '/../../../../Modelo/db/respaldo/';
        $rutaArchivo = $rutaCarpeta . $nombreArchivo;
        
        // Verificar que el archivo existe
        if (!file_exists($rutaArchivo)) {
            error_log("El archivo de respaldo no existe: $rutaArchivo");
            return false;
        }
        
        // Configurar el comando mysql
        $mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';
        
        // Construir el comando
        $comando = sprintf(
            '"%s" --user=%s --password=%s --host=%s %s < "%s" 2>&1',
            $mysql,
            escapeshellarg($config['user']),
            escapeshellarg($config['pass']),
            escapeshellarg($config['host']),
            $config['dbname'],
            $rutaArchivo
        );
        
        // Ejecutar el comando
        $output = [];
        $resultado = 0;
        exec($comando, $output, $resultado);
        
        // Registrar el resultado
        $logFile = $rutaCarpeta . 'backup_restore.log';
        $logMessage = '[' . date('c') . "] RESTAURAR: tipo={$this->tipo}, db={$config['dbname']}, archivo={$rutaArchivo}\n";
        $logMessage .= '[' . date('c') . "] Comando: $comando\n";
        $logMessage .= '[' . date('c') . "] Resultado: $resultado\n";
        $logMessage .= '[' . date('c') . "] Salida: " . implode("\n", $output) . "\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        if ($resultado !== 0) {
            error_log("Error al restaurar el respaldo. Código: $resultado, Archivo: $rutaArchivo");
            return false;
        }
        
        return true;
    }
    
    public function listar() {
          $ruta = __DIR__ . '/../../../../Modelo/db/respaldo/';
        $archivos = [];
        
        // Crear el directorio si no existe
        if (!is_dir($ruta)) {
            if (!mkdir($ruta, 0775, true)) {
                error_log("No se pudo crear el directorio de respaldos: $ruta");
                return $archivos;
            }
        }
        
        $files = @scandir($ruta);
        if ($files === false) {
            error_log("No se pudo leer el directorio de respaldos: $ruta");
            return $archivos;
        }
        
        foreach ($files as $file) {
            if (preg_match('/\.sql$/i', $file)) {
                $filePath = $ruta . $file;
                    $fileInfo = [
                        'nombre' => $file,
                        'tamano' => $this->formatearTamano(filesize($filePath)),
                        'fecha_modificacion' => date('d/m/Y H:i:s', filemtime($filePath)),
                        'tipo' => $this->obtenerTipoBackup($file)
                    ];
                    $archivos[] = $fileInfo;
                }
            }
            // Ordenar por fecha de modificación (más reciente primero)
            usort($archivos, function($a, $b) {
                return strtotime($b['fecha_modificacion']) - strtotime($a['fecha_modificacion']);
            });
        
        return $archivos;
    }
    

    /**
     * Formatea el tamaño del archivo en un formato legible
     */
    private function formatearTamano($bytes) {
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($unidades) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $unidades[$i];
    }

    /**
     * Obtiene el tipo de backup basado en el nombre del archivo
     */
    private function obtenerTipoBackup($nombreArchivo) {
        $nombreArchivo = strtolower($nombreArchivo);
        if (strpos($nombreArchivo, 'seguridad') !== false) {
            return 'Seguridad';
        } elseif (strpos($nombreArchivo, 'principal') !== false) {
            return 'Principal';
        }
        return 'Desconocido';
    }}

?>