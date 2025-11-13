<?php
namespace Usuario\ProyectoCasalaiCa\Clases;
use Usuario\ProyectoCasalaiCa\Config\Config\BD;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
class Backup {
    private $tipo;

    public function __construct($tipo = 'P') {
        $this->tipo = $tipo;
    }




public function generar($nombreArchivo) {

    $conexion = new \BD($this->tipo);
    $pdo = $conexion->getConexion();
    try {
        $dbname = $pdo->query('select database()')->fetchColumn();
        $rutaCarpeta = __DIR__ . '/../db/respaldo/';
        $ruta = $rutaCarpeta . $nombreArchivo;
        $config = ($this->tipo === 'S') ? DB_SEGURIDAD : DB_PRINCIPAL;

        // Verificar y crear carpeta si no existe
        if (!is_dir($rutaCarpeta)) {
            if (!mkdir($rutaCarpeta, 0775, true)) {
                return false;
            }
        }
        // Verificar permisos de escritura
        if (!is_writable($rutaCarpeta)) {
            return false;
        }

        $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe'; // En Windows
        // Depuración previa
        $logFile = __DIR__ . '/../db/backup/backup_debug.log';
        @file_put_contents($logFile, '[' . date('c') . "] MODELO: generar tipo={$this->tipo}, db={$dbname}, archivo={$ruta}\n", FILE_APPEND);
        @file_put_contents($logFile, '[' . date('c') . "] MODELO: mysqldump existe=" . (file_exists($mysqldump) ? '1' : '0') . ", is_executable=" . (is_file($mysqldump) ? '1' : '0') . "\n", FILE_APPEND);
        @file_put_contents($logFile, '[' . date('c') . "] MODELO: disable_functions=" . ini_get('disable_functions') . "\n", FILE_APPEND);

        $opciones = "--databases {$dbname} --add-drop-database --add-drop-table --routines --events --triggers";
        $comando = "\"$mysqldump\" --user=\"{$config['user']}\" --password=\"{$config['pass']}\" --host=\"{$config['host']}\" $opciones > \"$ruta\" 2>&1";
        @file_put_contents($logFile, '[' . date('c') . "] MODELO: comando=" . $comando . "\n", FILE_APPEND);
        exec($comando, $output, $resultado);

        // Depuración: guardar salida del comando
        if ($resultado !== 0) {
            @file_put_contents($logFile, '[' . date('c') . "] MODELO: Error mysqldump (tipo={$this->tipo}) resultado={$resultado}\n" . implode("\n", (array)$output) . "\n", FILE_APPEND);
            return false;
        }

        $ok = ($resultado === 0 && file_exists($ruta) && filesize($ruta) > 0);
        @file_put_contents($logFile, '[' . date('c') . "] MODELO: dump final ok=" . ($ok ? '1' : '0') . ", existe=" . (file_exists($ruta) ? '1' : '0') . ", size=" . (file_exists($ruta) ? filesize($ruta) : 0) . "\n", FILE_APPEND);
        return $ok;
    } finally {
        if (isset($conexion)) { $conexion->cerrar(); }
    }
}


public function restaurar($nombreArchivo) {
    $conexion = new \BD($this->tipo);
    $pdo = $conexion->getConexion();
    try {
        $ruta = __DIR__ . '/../db/respaldo/' . $nombreArchivo;
        $config = ($this->tipo === 'S') ? DB_SEGURIDAD : DB_PRINCIPAL;

        // Usa la ruta completa de mysql.exe en Windows
        $mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';

        // No pongas el nombre de la base de datos si el archivo contiene CREATE DATABASE
        $comando = "\"$mysql\" --user=\"{$config['user']}\" --password=\"{$config['pass']}\" --host=\"{$config['host']}\" < \"$ruta\" 2>&1";
        $output = [];
        $resultado = 0;
        exec($comando, $output, $resultado);

        if ($resultado !== 0) {
            return false;
        }
        return true;
    } finally {
        if (isset($conexion)) { $conexion->cerrar(); }
    }
}

    public function listar() {
        $ruta = __DIR__ . '/../db/respaldo/';
        $archivos = [];
        if (is_dir($ruta)) {
            $files = scandir($ruta);
            foreach ($files as $file) {
                if (preg_match('/\.sql$/', $file)) {
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
        }
        return $archivos;
    }

    private function formatearTamano($bytes) {
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($unidades) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $unidades[$i];
    }

    private function obtenerTipoBackup($nombreArchivo) {
        if (strpos($nombreArchivo, 'seguridad') !== false) {
            return 'Seguridad';
        }
        return 'Principal';
    }
}
?>