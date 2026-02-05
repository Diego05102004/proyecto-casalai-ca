<?php

$lockFile = sys_get_temp_dir() . '/websocket_casalai.lock';

// Si el WebSocket ya fue iniciado, no hacer nada
if (file_exists($lockFile)) {
    return;
}

// Crear archivo lock
file_put_contents($lockFile, getmypid());

// Ruta absoluta al websocket
$websocketPath = __DIR__ . '/websocket_server.php';

// Detectar sistema operativo
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

if ($isWindows) {
    // Windows (XAMPP / WAMP)
    pclose(popen("start /B php \"$websocketPath\"", "r"));
} else {
    // Linux / Unix
    exec("nohup php \"$websocketPath\" > /dev/null 2>&1 &");
}
