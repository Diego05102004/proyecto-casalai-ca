<?php
/**
 * Prueba de cifrado AES-256 para el módulo de clientes
 * 
 * Este archivo prueba la funcionalidad de cifrado/descifrado
 * implementada en la clase Encryption.
 */

require_once __DIR__ . '/Modelo/Config/Encryption.php';

use Usuario\ProyectoCasalaiCa\Config\Encryption;

echo "<h1>Prueba de Cifrado AES-256</h1>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

try {
    $encryption = new Encryption();
    
    echo "<h2>Datos de prueba</h2>";
    $datosPrueba = [
        'nombre' => 'Juan Pérez',
        'direccion' => 'Calle 123, Ciudad',
        'telefono' => '0414-123-4567',
        'correo' => 'juan.perez@example.com'
    ];
    
    echo "<pre>";
    print_r($datosPrueba);
    echo "</pre>";
    
    echo "<h2>Cifrando datos...</h2>";
    $datosCifrados = [];
    foreach ($datosPrueba as $campo => $valor) {
        $datosCifrados[$campo] = $encryption->encrypt($valor);
        echo "<div class='info'>$campo: " . substr($datosCifrados[$campo], 0, 50) . "...</div>";
    }
    
    echo "<h2>Descifrando datos...</h2>";
    $datosDescifrados = [];
    $exitoso = true;
    foreach ($datosCifrados as $campo => $valor) {
        $datosDescifrados[$campo] = $encryption->decrypt($valor);
        if ($datosDescifrados[$campo] === $datosPrueba[$campo]) {
            echo "<div class='success'>✓ $campo descifrado correctamente: " . $datosDescifrados[$campo] . "</div>";
        } else {
            echo "<div class='error'>✗ $campo: Error al descifrar</div>";
            $exitoso = false;
        }
    }
    
    echo "<h2>Prueba de array completo</h2>";
    $camposACifrar = ['nombre', 'direccion', 'telefono', 'correo'];
    $arrayCifrado = $encryption->encryptArray($datosPrueba, $camposACifrar);
    echo "<div class='info'>Array cifrado (primer campo): " . substr($arrayCifrado['nombre'], 0, 50) . "...</div>";
    
    $arrayDescifrado = $encryption->decryptArray($arrayCifrado, $camposACifrar);
    $arrayExitoso = true;
    foreach ($camposACifrar as $campo) {
        if ($arrayDescifrado[$campo] === $datosPrueba[$campo]) {
            echo "<div class='success'>✓ $campo en array descifrado correctamente</div>";
        } else {
            echo "<div class='error'>✗ $campo en array: Error al descifrar</div>";
            $arrayExitoso = false;
        }
    }
    
    echo "<h2>Prueba de resultados múltiples</h2>";
    $resultadosPrueba = [
        $datosPrueba,
        [
            'nombre' => 'María González',
            'direccion' => 'Avenida 456',
            'telefono' => '0424-987-6543',
            'correo' => 'maria.gonzalez@example.com'
        ]
    ];
    
    $resultadosCifrados = $encryption->encryptResults($resultadosPrueba, $camposACifrar);
    echo "<div class='info'>Resultados cifrados: " . count($resultadosCifrados) . " registros</div>";
    
    $resultadosDescifrados = $encryption->decryptResults($resultadosCifrados, $camposACifrar);
    $resultadosExitoso = true;
    foreach ($resultadosDescifrados as $index => $resultado) {
        foreach ($camposACifrar as $campo) {
            if ($resultado[$campo] === $resultadosPrueba[$index][$campo]) {
                echo "<div class='success'>✓ Registro $index, campo $campo descifrado correctamente</div>";
            } else {
                echo "<div class='error'>✗ Registro $index, campo $campo: Error al descifrar</div>";
                $resultadosExitoso = false;
            }
        }
    }
    
    echo "<h2>Resultado final</h2>";
    if ($exitoso && $arrayExitoso && $resultadosExitoso) {
        echo "<div class='success'><h3>✓ TODAS LAS PRUEBAS EXITOSAS</h3></div>";
        echo "<p>El cifrado AES-256 está funcionando correctamente.</p>";
    } else {
        echo "<div class='error'><h3>✗ ALGUNAS PRUEBAS FALLARON</h3></div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'><h3>Error: " . $e->getMessage() . "</h3></div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
