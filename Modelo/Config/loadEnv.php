<?php
namespace Usuario\ProyectoCasalaiCa\Config;

/**
 * Cargador de variables de entorno desde archivo .env
 * 
 * Este archivo carga las variables de entorno desde un archivo .env
 * en la raíz del proyecto para facilitar la configuración en desarrollo.
 * 
 * En producción, se recomienda usar variables de entorno del sistema operativo
 * en lugar de este archivo para mayor seguridad.
 */

class EnvLoader {
    /**
     * Carga variables de entorno desde archivo .env
     * @param string $path Ruta al archivo .env (opcional, por defecto busca en raíz del proyecto)
     * @return void
     */
    public static function load($path = null) {
        if ($path === null) {
            // Buscar .env en la raíz del proyecto (4 niveles arriba desde este archivo)
            $path = dirname(__DIR__, 4) . '/.env';
        }
        
        if (!file_exists($path)) {
            return; // No hacer nada si el archivo no existe
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentarios y líneas vacías
            if (strpos(trim($line), '#') === 0 || trim($line) === '') {
                continue;
            }
            
            // Buscar el signo igual
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Remover comillas si están presentes
                if (strlen($value) >= 2) {
                    if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                        (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                        $value = substr($value, 1, -1);
                    }
                }
                
                // Convertir \n a saltos de línea reales (para claves RSA)
                $value = str_replace('\\n', "\n", $value);
                
                // Establecer variable de entorno
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Cargar automáticamente al incluir este archivo
EnvLoader::load();
