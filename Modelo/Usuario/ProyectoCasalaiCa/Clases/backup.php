<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;

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
/**
 * Genera un respaldo de la base de datos
 */
public function generar($nombreArchivo) {
    $config = $this->config;
    $resultado = [
        'success' => false,
        'message' => '',
        'archivo' => $nombreArchivo,
        'tamano' => 0,
        'error' => null
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
        $errorMsg = "El directorio no tiene permisos de escritura: $rutaCarpeta";
        error_log($errorMsg);
        $resultado['error'] = $errorMsg;
        $resultado['message'] = 'Error de permisos en el directorio de respaldos';
        return $resultado;
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
    $opciones = [
        '--user=' . escapeshellarg($config['user']),
        '--password=' . escapeshellarg($config['pass']),
        '--host=' . escapeshellarg($config['host']),
        '--default-character-set=utf8mb4',
        '--add-drop-database',
        '--add-drop-table',
        '--add-locks',
        '--create-options',
        '--disable-keys',
        '--extended-insert',
        '--lock-tables',
        '--quick',
        '--routines',
        '--triggers',
        '--events',
        '--set-charset',
        '--single-transaction',
        '--result-file=' . escapeshellarg($rutaArchivo),
        escapeshellarg($config['dbname']),
        '2>&1'
    ];
    
    // Construir el comando
    $comando = '"' . $mysqldump . '" ' . implode(' ', $opciones);
    
    // Ejecutar el comando
    $output = [];
    $exitCode = 0;
    exec($comando, $output, $exitCode);
    
    // Verificar si el archivo se creó correctamente
    $archivoExiste = file_exists($rutaArchivo);
    $tamanoArchivo = $archivoExiste ? filesize($rutaArchivo) : 0;
    
    if ($archivoExiste && $tamanoArchivo > 0) {
        // Agregar encabezado SQL al inicio del archivo
        $header = $this->getSqlHeader($config['dbname']);
        $currentContent = file_get_contents($rutaArchivo);
        file_put_contents($rutaArchivo, $header . $currentContent);
        
        // Actualizar el tamaño después de agregar el encabezado
        $tamanoArchivo = filesize($rutaArchivo);
        
        $resultado['success'] = true;
        $resultado['message'] = 'Respaldo generado correctamente';
        $resultado['tamano'] = $tamanoArchivo;
    } else {
        $errorMsg = $archivoExiste ? 'El archivo de respaldo está vacío' : 'No se pudo crear el archivo de respaldo';
        $resultado['error'] = $errorMsg . ' (Código: ' . $exitCode . ')';
        $resultado['message'] = 'Error al generar el respaldo';
        error_log(implode("\n", $output));
    }
    
    return $resultado;
}



/**
 * Devuelve el encabezado SQL para el respaldo
 */
private function getSqlHeader($dbName) {
    $header = "-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)\n";
    $header .= "--\n";
    $header .= "-- Host: localhost    Database: {$dbName}\n";
    $header .= "-- ------------------------------------------------------\n";
    $header .= "-- Server version\t10.4.32-MariaDB\n\n";
    $header .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
    $header .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
    $header .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
    $header .= "/*!40101 SET NAMES utf8mb4 */;\n";
    $header .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n";
    $header .= "/*!40103 SET TIME_ZONE='+00:00' */;\n";
    $header .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n";
    $header .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
    $header .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n";
    $header .= "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";
    $header .= "--\n";
    $header .= "-- Current Database: `{$dbName}`\n";
    $header .= "--\n\n";
    $header .= "USE `{$dbName}`;\n\n";
    
    return $header;
}

/**
 * Devuelve el pie de página SQL para el respaldo
 */
private function getSqlFooter() {
    $footer = "\n--\n";
    $footer .= "-- Dump completed on " . date('Y-m-d H:i:s') . "\n";
    $footer .= "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;\n";
    $footer .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n";
    $footer .= "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n";
    $footer .= "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n";
    $footer .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
    $footer .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
    $footer .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
    $footer .= "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n";
    
    return $footer;
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
