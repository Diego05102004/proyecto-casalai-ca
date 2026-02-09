<?php
/**
 * Script para verificar y asegurar que el servidor WebSocket esté corriendo
 * Se ejecuta automáticamente después del login exitoso
 */

// Verificar si el servidor WebSocket está corriendo
function verificarWebSocketServer() {
    // Intentar conectar al servidor WebSocket
    $socket = @fsockopen('localhost', 8080, $errno, $errstr, 2);
    
    if ($socket) {
        fclose($socket);
        return true; // El servidor está corriendo
    }
    
    return false; // El servidor no está corriendo
}

// Iniciar el servidor WebSocket si no está corriendo
function iniciarWebSocketServer() {
    $lockFile = sys_get_temp_dir() . '/websocket_casalai.lock';
    
    // Verificar si ya hay un proceso corriendo
    if (file_exists($lockFile)) {
        $pid = file_get_contents($lockFile);
        // Verificar si el proceso todavía existe
        if (function_exists('posix_kill')) {
            // Linux/Mac
            if (posix_kill($pid, 0)) {
                return true; // El proceso todavía está corriendo
            }
        } else {
            // Windows - verificar con tasklist
            $output = [];
            exec("tasklist /FI \"PID eq $pid\" 2>NUL", $output);
            foreach ($output as $line) {
                if (strpos($line, (string)$pid) !== false) {
                    return true; // El proceso todavía está corriendo
                }
            }
        }
        // Si llegamos aquí, el proceso ya no existe, eliminar el lock
        unlink($lockFile);
    }
    
    // Ruta absoluta al websocket
    $websocketPath = __DIR__ . DIRECTORY_SEPARATOR . 'websocket_server.php';
    
    if (!file_exists($websocketPath)) {
        error_log("No se encuentra el archivo: $websocketPath");
        return false;
    }
    
    // Detectar sistema operativo
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    
    if ($isWindows) {
        // Windows - usar método más robusto
        $command = "php \"$websocketPath\"";
        
        // Método 1: Usar start /B con redirección
        $batchFile = sys_get_temp_dir() . '/start_websocket.bat';
        $batchContent = "@echo off\nphp \"$websocketPath\" > nul 2>&1\n";
        file_put_contents($batchFile, $batchContent);
        
        // Ejecutar en segundo plano
        pclose(popen("start /B \"\" \"$batchFile\"", "r"));
        
        // Esperar un momento y limpiar
        sleep(1);
        if (file_exists($batchFile)) {
            unlink($batchFile);
        }
        
    } else {
        // Linux / Unix
        $command = "nohup php \"$websocketPath\" > /dev/null 2>&1 &";
        exec($command);
    }
    
    // Crear archivo lock para evitar múltiples instancias
    file_put_contents($lockFile, getmypid());
    
    return true;
}

// Función mejorada para verificar y esperar a que el servidor inicie
function verificarEIniciarWebSocket() {
    $maxIntentos = 5;
    $intentos = 0;
    
    // Primero verificar si ya está corriendo
    if (verificarWebSocketServer()) {
        echo "<script>console.log('Servidor WebSocket ya está corriendo');</script>";
        return true;
    }
    
    // Intentar iniciar el servidor
    echo "<script>console.log('Iniciando servidor WebSocket...');</script>";
    
    if (!iniciarWebSocketServer()) {
        echo "<script>console.warn('No se pudo iniciar el servidor WebSocket');</script>";
        return false;
    }
    
    // Esperar y verificar que el servidor haya iniciado correctamente
    while ($intentos < $maxIntentos) {
        sleep(1);
        if (verificarWebSocketServer()) {
            echo "<script>console.log('Servidor WebSocket iniciado correctamente');</script>";
            return true;
        }
        $intentos++;
    }
    
    echo "<script>console.warn('El servidor WebSocket no pudo iniciarse después de $maxIntentos intentos');</script>";
    return false;
}

// Ejecutar verificación e inicio si es necesario
verificarEIniciarWebSocket();
?>
