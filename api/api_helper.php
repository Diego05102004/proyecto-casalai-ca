<?php
/**
 * Helper para la API - Funciones reutilizables para endpoints
 * Proporciona la función RecibirPeticion() para manejar solicitudes de la app móvil
 */

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
        // Obtener el nombre de la función solicitada
        $funcion = $_GET['funcion'] ?? $_POST['funcion'] ?? null;
        
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

