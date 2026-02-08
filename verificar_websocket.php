<?php
/**
 * Script para verificar y asegurar que el servidor WebSocket esté corriendo
 * Se ejecuta automáticamente después del login exitoso
 */

// Verificar si el servidor WebSocket está corriendo
function verificarWebSocketServer() {
    $socket = @fsockopen('localhost', 8080, $errno, $errstr, 2);
    
    if ($socket) {
        fclose($socket);
        return true; // El servidor está corriendo
    }
    
    return false; // El servidor no está corriendo
}

// Iniciar el servidor WebSocket si no está corriendo
function iniciarWebSocketServer() {
    // Ruta al script de inicio
    $scriptPath = __DIR__ . DIRECTORY_SEPARATOR . 'start_websocket.php';
    
    if (file_exists($scriptPath)) {
        // Iniciar el servidor en segundo plano (Windows)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Para Windows, usar PowerShell para iniciar en segundo plano
            $command = "powershell.exe -ExecutionPolicy Bypass -WindowStyle Hidden -Command \"php '$scriptPath'\"";
            
            // Usar pclose(popen()) para iniciar en segundo plano
            pclose(popen("start /B " . $command, "r"));
            
            // Alternativa: usar WScript.Shell si está disponible
            /*
            if (class_exists('COM')) {
                $WshShell = new COM('WScript.Shell');
                $WshShell->Run($command, 0, false);
            }
            */
        } else {
            // Para Linux/Mac
            exec("php \"$scriptPath\" > /dev/null 2>&1 &");
        }
        
        return true;
    }
    
    return false;
}

// Ejecutar verificación y inicio si es necesario
if (!verificarWebSocketServer()) {
    iniciarWebSocketServer();
    
    // Esperar un momento para que el servidor inicie
    sleep(2);
    
    // Verificar nuevamente
    if (verificarWebSocketServer()) {
        echo "<script>console.log('Servidor WebSocket iniciado automáticamente');</script>";
    } else {
        echo "<script>console.warn('No se pudo iniciar el servidor WebSocket automáticamente');</script>";
    }
} else {
    echo "<script>console.log('Servidor WebSocket ya está corriendo');</script>";
}
?>
