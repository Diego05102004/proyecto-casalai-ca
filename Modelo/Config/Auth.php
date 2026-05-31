<?php
namespace Usuario\ProyectoCasalaiCa\Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Exception;

/**
 * Clase de autenticación JWT para gestión de tokens de acceso
 * 
 * Esta clase proporciona métodos para generar, validar y gestionar tokens JWT
 * con soporte híbrido para aplicaciones web (cookies) y móviles (headers).
 * 
 * @package Usuario\ProyectoCasalaiCa\Config
 * @version 1.0.0
 */
class Auth {
    
    /**
     * Clave secreta para firmar los tokens JWT
     * En producción, esta debe almacenarse en variables de entorno
     * 
     * @var string
     */
    private static $secretKey = 'd6e32e8d9c57eeef6fb09bebe9cb579bc75836c0d8327914bf689369d4e5f76b';
    
    /**
     * Algoritmo de firma del token
     * 
     * @var string
     */
    private static $algorithm = 'HS256';
    
    /**
     * Tiempo de expiración del token en segundos (1 hora)
     * 
     * @var int
     */
    private static $tokenExpiration = 3600;
    
    /**
     * Nombre de la cookie para almacenar el token JWT
     * 
     * @var string
     */
    private static $cookieName = 'jwt_token';
    
    /**
     * Genera un token JWT para un usuario autenticado
     * 
     * @param int $userId ID del usuario
     * @param string $userRole Rol del usuario (Administrador, SuperUsuario, cliente, etc.)
     * @return string Token JWT generado
     * @throws Exception Si hay error al generar el token
     */
    public static function generateToken($userId, $userRole) {
        try {
            $issuedAt = time();
            $expire = $issuedAt + self::$tokenExpiration;
            
            $payload = [
                'iat' => $issuedAt,           // Tiempo de emisión
                'exp' => $expire,             // Tiempo de expiración
                'sub' => $userId,             // ID del usuario (subject)
                'role' => $userRole,          // Rol del usuario
                'type' => 'access'            // Tipo de token
            ];
            
            $token = JWT::encode($payload, self::$secretKey, self::$algorithm);
            
            return $token;
        } catch (Exception $e) {
            error_log("Error al generar JWT: " . $e->getMessage());
            throw new Exception("Error al generar token de acceso");
        }
    }
    
    /**
     * Establece el token JWT en una cookie HttpOnly
     * 
     * @param string $token Token JWT a almacenar
     * @return void
     */
    public static function setTokenCookie($token) {
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $isLocalhost = $_SERVER['SERVER_NAME'] === 'localhost' || 
                       $_SERVER['SERVER_NAME'] === '127.0.0.1';
        
        // En localhost, no usar Secure para permitir HTTP
        $secureFlag = $isSecure && !$isLocalhost;
        
        $cookieOptions = [
            'expires' => time() + self::$tokenExpiration,
            'path' => '/',
            'domain' => '', // Dominio actual
            'secure' => $secureFlag,
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        
        setcookie(self::$cookieName, $token, $cookieOptions);
    }
    
    /**
     * Elimina la cookie del token JWT
     * 
     * @return void
     */
    public static function clearTokenCookie() {
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $isLocalhost = $_SERVER['SERVER_NAME'] === 'localhost' || 
                       $_SERVER['SERVER_NAME'] === '127.0.0.1';
        
        $secureFlag = $isSecure && !$isLocalhost;
        
        $cookieOptions = [
            'expires' => time() - 3600, // Expirar en el pasado
            'path' => '/',
            'domain' => '',
            'secure' => $secureFlag,
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        
        setcookie(self::$cookieName, '', $cookieOptions);
    }
    
    /**
     * Valida un token JWT y retorna el payload decodificado
     * Soporte híbrido: busca primero en cookie, luego en header Authorization
     * 
     * @param string|null $token Token JWT a validar (opcional, si es null busca en cookie/header)
     * @return array|false Payload decodificado si es válido, false si no es válido
     */
    public static function validateToken($token = null) {
        try {
            // Si no se proporciona token, buscar en cookie o header
            if ($token === null) {
                // Primero buscar en cookie HttpOnly
                if (isset($_COOKIE[self::$cookieName])) {
                    $token = $_COOKIE[self::$cookieName];
                }
                // Si no está en cookie, buscar en header Authorization: Bearer
                elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
                    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                        $token = $matches[1];
                    }
                }
                // Si no está en ninguna parte, retornar false
                if ($token === null) {
                    return false;
                }
            }
            
            // Decodificar y validar el token
            $decoded = JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
            
            // Convertir a array
            $payload = (array) $decoded;
            
            return $payload;
            
        } catch (ExpiredException $e) {
            error_log("Token expirado: " . $e->getMessage());
            return false;
        } catch (SignatureInvalidException $e) {
            error_log("Firma de token inválida: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error al validar token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si el token es válido y retorna el payload
     * Método de conveniencia para uso en controladores
     * 
     * @return array|false Payload decodificado si es válido, false si no
     */
    public static function checkAuth() {
        return self::validateToken();
    }
    
    /**
     * Verifica si el usuario tiene un rol específico
     * 
     * @param array $payload Payload del token JWT
     * @param array $allowedRoles Array de roles permitidos
     * @return bool True si el rol está permitido, false si no
     */
    public static function hasRole($payload, $allowedRoles) {
        if (!$payload || !isset($payload['role'])) {
            return false;
        }
        
        return in_array($payload['role'], $allowedRoles);
    }
    
    /**
     * Obtiene el ID del usuario desde el payload del token
     * 
     * @param array $payload Payload del token JWT
     * @return int|null ID del usuario o null si no está disponible
     */
    public static function getUserId($payload) {
        if (!$payload || !isset($payload['sub'])) {
            return null;
        }
        
        return (int) $payload['sub'];
    }
    
    /**
     * Obtiene el rol del usuario desde el payload del token
     * 
     * @param array $payload Payload del token JWT
     * @return string|null Rol del usuario o null si no está disponible
     */
    public static function getUserRole($payload) {
        if (!$payload || !isset($payload['role'])) {
            return null;
        }
        
        return $payload['role'];
    }
    
    /**
     * Envía una respuesta JSON de error de autenticación
     * 
     * @param string $message Mensaje de error
     * @param int $code Código HTTP (default: 401)
     * @return void
     */
    public static function sendAuthError($message = 'No autorizado', $code = 401) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'code' => $code
        ]);
        exit;
    }
    
    /**
     * Middleware para proteger endpoints que requieren autenticación
     * 
     * @param array $allowedRoles Array de roles permitidos (vacío = cualquier rol autenticado)
     * @return array|false Payload del token si es válido, envía error y termina si no
     */
    public static function requireAuth($allowedRoles = []) {
        $payload = self::validateToken();
        
        if (!$payload) {
            self::sendAuthError('Token inválido o expirado. Por favor, inicie sesión nuevamente.');
        }
        
        // Verificar roles si se especificaron
        if (!empty($allowedRoles)) {
            if (!self::hasRole($payload, $allowedRoles)) {
                self::sendAuthError('No tiene permisos suficientes para acceder a este recurso.', 403);
            }
        }
        
        return $payload;
    }
    
    /**
     * Obtiene el tiempo de expiración del token en segundos
     * 
     * @return int Tiempo de expiración en segundos
     */
    public static function getTokenExpiration() {
        return self::$tokenExpiration;
    }
    
    /**
     * Obtiene el nombre de la cookie del token
     * 
     * @return string Nombre de la cookie
     */
    public static function getCookieName() {
        return self::$cookieName;
    }
}
