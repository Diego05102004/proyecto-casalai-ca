<?php
/**
 * Script para probar la conexión y el conteo del carrito WebSocket
 */

echo "Probando conexión WebSocket...\n";

// Crear un cliente WebSocket de prueba
$host = 'localhost';
$port = 8080;

// Intentar conectar
$socket = @fsockopen($host, $port, $errno, $errstr, 5);

if ($socket) {
    echo "✅ Conexión establecida con el servidor WebSocket\n";
    fclose($socket);
    
    // Simular mensaje de prueba para obtener carrito
    $testMessage = [
        'tipo' => 'obtener_carrito_count',
        'usuario_id' => 3 // ID de usuario de prueba
    ];
    
    echo "📦 Mensaje de prueba: " . json_encode($testMessage) . "\n";
    echo "💡 Para probar completamente, inicia sesión en el sistema y observa la consola del navegador\n";
    echo "🔍 Deberías ver mensajes como:\n";
    echo "   - 'Carrito encontrado: X, Productos: Y'\n";
    echo "   - 'No se encontró carrito para el usuario: X'\n";
    
} else {
    echo "❌ No se pudo conectar al servidor WebSocket\n";
    echo "Error: $errno - $errstr\n";
}
?>
