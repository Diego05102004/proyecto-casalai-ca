<?php
/**
 * Sistema de autenticación con JWT para la API
 */

class Auth {
    
    // Clave secreta para firmar tokens (debe estar en variables de entorno en producción)
    private static $secretKey = 'proyecto-casalai-ca-secret-key-2024';
    
    // Tiempo de expiración del token (24 horas)
    private static $tokenExpiration = 86400;
    
    /**
     * Generar un token JWT
     */
    public static function generateToken($userId, $userData) {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        $issuedAt = time();
        $expire = $issuedAt + self::$tokenExpiration;
        
        $payload = [
            'iss' => 'casalai-api',
            'iat' => $issuedAt,
            'exp' => $expire,
            'user_id' => $userId,
            'data' => $userData
        ];
        
        // Codificar header y payload
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        // Crear firma
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, self::$secretKey, true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        // Retornar token
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    /**
     * Verificar y decodificar un token JWT
     */
    public static function verifyToken($token) {
        if (empty($token)) {
            return null;
        }
        
        $tokenParts = explode('.', $token);
        
        if (count($tokenParts) !== 3) {
            return null;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $tokenParts;
        
        // Verificar firma
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, self::$secretKey, true);
        $expectedSignature = self::base64UrlEncode($signature);
        
        if (!hash_equals($expectedSignature, $signatureEncoded)) {
            return null;
        }
        
        // Decodificar payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        
        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
    
    /**
     * Obtener el token del header Authorization
     */
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    public static function checkAuth() {
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            return false;
        }
        
        $payload = self::verifyToken($token);
        
        if (!$payload) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Obtener datos del usuario autenticado
     */
    public static function getUserData() {
        $payload = self::checkAuth();
        
        if (!$payload) {
            return null;
        }
        
        return $payload['data'] ?? null;
    }
    
    /**
     * Obtener ID del usuario autenticado
     */
    public static function getUserId() {
        $payload = self::checkAuth();
        
        if (!$payload) {
            return null;
        }
        
        return $payload['user_id'] ?? null;
    }
    
    /**
     * Codificar en Base64 URL-safe
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Decodificar desde Base64 URL-safe
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Refrescar token (generar uno nuevo)
     */
    public static function refreshToken($token) {
        $payload = self::verifyToken($token);
        
        if (!$payload) {
            return null;
        }
        
        // Generar nuevo token con los mismos datos
        return self::generateToken($payload['user_id'], $payload['data']);
    }
}
