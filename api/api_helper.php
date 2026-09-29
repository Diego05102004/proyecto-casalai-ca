<?php
/**
 * Helper para la API - Funciones reutilizables para endpoints
 * Proporciona la función RecibirPeticion() para manejar solicitudes de la app móvil
 */

require_once __DIR__ . '/Config/Encryption.php';

use Usuario\ProyectoCasalaiCa\Config\Encryption;

/**
 * Función principal para recibir y procesar peticiones de la app móvil
 * Detecta la función solicitada e invoca el método correspondiente de la clase
 * 
 * @param object $instance Instancia de la clase del modelo
 * @param array $operations Array asociativo con las operaciones permitidas
 *                          Formato: ['nombre_funcion' => ['method' => 'GET|POST|PUT|DELETE', 'handler' => 'nombre_metodo_clase']]
 * 
 * @return void
 */
function RecibirPeticion($instance, $operations) {
    try {
        $requestData = getRequestData();

        // Obtener el nombre de la función solicitada, soportando JSON, POST y GET
        $funcion = $requestData['funcion'] ?? $_GET['funcion'] ?? $_POST['funcion'] ?? null;
        
        if (!$funcion) {
            // Si no se especifica función, usar la operación por defecto (GET)
            $funcion = 'default';
        }
        
        $funcion = strtolower(trim($funcion));
        
        // Verificar si la operación existe
        if (!isset($operations[$funcion])) {
            errorResponse('Función no reconocida: ' . $funcion, 400);
        }
        
        $operation = $operations[$funcion];
        $method = $operation['method'] ?? 'GET';
        $handler = $operation['handler'] ?? $funcion;
        
        // Validar método HTTP
        validateMethod([$method]);
        
        // Obtener datos de la petición según el método
        $data = getRequestData();

        // Para GET, también incluir parámetros de URL
        if ($method === 'GET') {
            $data = array_merge($data, $_GET);
        }

        // Desencriptar datos sensibles si están cifrados (solo para POST/PUT)
        if ($method === 'POST' || $method === 'PUT') {
            $encryption = new Encryption();
            $sensitiveFields = ['username', 'password', 'nombres', 'apellidos', 'cedula', 'correo', 'telefono', 'direccion', 'nombre', 'apellido', 'clave'];
            foreach ($sensitiveFields as $field) {
                if (isset($data[$field]) && $data[$field] !== '') {
                    try {
                        // Restaurar espacios en blanco a '+' para Base64
                        if (is_string($data[$field])) {
                            $data[$field] = str_replace(' ', '+', $data[$field]);
                        }
                        $data[$field] = $encryption->decrypt($data[$field]);
                    } catch (\Throwable $decryptError) {
                        error_log("[API_HELPER] Error descifrando campo '$field': " . $decryptError->getMessage());
                        // Continuar con el valor original si falla el desencriptado
                    }
                }
            }
        }
        
        // Importante: NO desencriptar el campo 'funcion' ya que se usa para enrutamiento

        // Asegurar que la función también quede en $data para métodos con JSON
        if (!isset($data['funcion']) && isset($funcion)) {
            $data['funcion'] = $funcion;
        }
        
        // Verificar si el método existe en la instancia
        if (!method_exists($instance, $handler)) {
            errorResponse('Método no implementado: ' . $handler, 501);
        }
        
        // Invocar el método de la clase
        $resultado = call_user_func([$instance, $handler], $data);
        
        // Retornar respuesta exitosa
        successResponse($resultado, 'Operación exitosa');
        
    } catch (Exception $e) {
        errorResponse('Error al procesar la petición: ' . $e->getMessage(), 500);
    }
}

