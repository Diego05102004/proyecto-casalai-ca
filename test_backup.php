<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir el autoloader de Composer si existe
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Incluir la clase Backup
require_once __DIR__ . '/Modelo/Usuario/ProyectoCasalaiCa/Clases/backup.php';

// Crear instancia de Backup
$backup = new Usuario\ProyectoCasalaiCa\Clases\Backup('P');

// Probar generación de respaldo
$testFile = 'test_backup_' . date('Ymd_His') . '.sql';
$result = $backup->generar($testFile);

echo "<h2>Resultado de la generación de respaldo:</h2>";
if ($result) {
    echo "<p style='color:green;'>El respaldo se generó correctamente: $testFile</p>";
} else {
    echo "<p style='color:red;'>Error al generar el respaldo.</p>";
}

// Verificar si el archivo se creó
$backupPath = __DIR__ . '/db/backup/' . $testFile;
if (file_exists($backupPath)) {
    echo "<p>Archivo de respaldo creado en: $backupPath</p>";
    echo "<p>Tamaño: " . filesize($backupPath) . " bytes</p>";
} else {
    echo "<p style='color:red;'>El archivo de respaldo no se creó.</p>";
}

// Verificar permisos del directorio
$backupDir = __DIR__ . '/db/backup';
echo "<h2>Verificación de permisos:</h2>";
echo "<p>Directorio de respaldos: $backupDir</p>";
echo "<p>Existe: " . (file_exists($backupDir) ? 'Sí' : 'No') . "</p>";
if (file_exists($backupDir)) {
    echo "<p>Es directorio: " . (is_dir($backupDir) ? 'Sí' : 'No') . "</p>";
    echo "<p>Permisos: " . substr(sprintf('%o', fileperms($backupDir)), -4) . "</p>";
    echo "<p>Puedo escribir: " . (is_writable($backupDir) ? 'Sí' : 'No') . "</p>";
}

// Verificar si mysqldump está accesible
$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
echo "<h2>Verificación de mysqldump:</h2>";
echo "<p>Ruta a mysqldump: $mysqldump</p>";
echo "<p>Existe: " . (file_exists($mysqldump) ? 'Sí' : 'No') . "</p>";
if (file_exists($mysqldump)) {
    echo "<p>Es ejecutable: " . (is_executable($mysqldump) ? 'Sí' : 'No') . "</p>";
    
    // Intentar obtener la versión de mysqldump
    $output = [];
    $return_var = 0;
    exec("$mysqldump --version", $output, $return_var);
    
    if ($return_var === 0 && !empty($output[0])) {
        echo "<p>Versión de mysqldump: " . htmlspecialchars($output[0]) . "</p>";
    } else {
        echo "<p style='color:red;'>No se pudo obtener la versión de mysqldump. Código de salida: $return_var</p>";
    }
}

// Verificar funciones deshabilitadas
echo "<h2>Configuración de PHP:</h2>";
echo "<p>Funciones deshabilitadas: " . (ini_get('disable_functions') ?: 'Ninguna') . "</p>";
echo "<p>Función exec disponible: " . (function_exists('exec') ? 'Sí' : 'No') . "</p>";

// Mostrar información del servidor
echo "<h2>Información del servidor:</h2>";
echo "<pre>" . php_uname() . "</pre>";
?>
