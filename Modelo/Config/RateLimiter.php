<?php
namespace Usuario\ProyectoCasalaiCa\Config;

use PDO;
use PDOException;

/**
 * Clase para implementar Rate Limiting y control de seguridad
 * 
 * Esta clase proporciona métodos para limitar el número de intentos de login
 * y bloquear IPs/usuarios sospechosos para prevenir ataques de fuerza bruta.
 * 
 * @package Usuario\ProyectoCasalaiCa\Config
 * @version 1.0.0
 */
class RateLimiter {
    
    /**
     * Configuración de rate limiting
     */
    const MAX_ATTEMPTS = 5;           // Máximo de intentos fallidos permitidos
    const BLOCK_DURATION = 900;       // Duración del bloqueo en segundos (15 minutos)
    const ATTEMPT_WINDOW = 300;       // Ventana de tiempo para contar intentos (5 minutos)
    /**
     * Obtiene la dirección IP del cliente
     * 
     * @return string Dirección IP
     */
    private static function getClientIP() {
        $ip = '';
        
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return trim($ip);
    }
    
    /**
     * Obtiene el User Agent del cliente
     * 
     * @return string User Agent
     */
    private static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
    
    /**
     * Obtiene conexión a la base de datos de seguridad
     * 
     * @return PDO|null Conexión PDO o null si falla
     */
    private static function getSecurityConnection() {
        try {
            // Configuración de la base de datos de seguridad
            $host = '127.0.0.1';
            $dbname = 'casalai_seguridad';
            $username = 'root';
            $password = '';
            
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            error_log("Conexión exitosa a base de datos de seguridad: $dbname");
            return $pdo;
        } catch (PDOException $e) {
            error_log("Error al conectar a base de datos de seguridad: " . $e->getMessage());
            error_log("Host: $host, DB: $dbname, User: $username");
            return null;
        }
    }
    
    /**
     * Verifica si una IP está bloqueada
     * 
     * @param string $ip Dirección IP
     * @return array Información del bloqueo o array vacío si no está bloqueada
     */
    private static function isIPBlocked($ip) {
        $pdo = self::getSecurityConnection();
        if (!$pdo) {
            return [];
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM seguridad_ip 
                WHERE direccion_ip = :ip 
                AND esta_bloqueado = 1 
                AND (fecha_desbloqueo IS NULL OR fecha_desbloqueo > NOW())
                LIMIT 1
            ");
            $stmt->execute([':ip' => $ip]);
            $result = $stmt->fetch();
            
            return $result ?: [];
        } catch (PDOException $e) {
            error_log("Error al verificar bloqueo de IP: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verifica si un usuario está bloqueado
     * 
     * @param string $username Nombre de usuario
     * @return array Información del bloqueo o array vacío si no está bloqueado
     */
    private static function isUserBlocked($username) {
        $pdo = self::getSecurityConnection();
        if (!$pdo) {
            return [];
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM seguridad_ip 
                WHERE username = :username 
                AND tipo_bloqueo = 'usuario'
                AND esta_bloqueado = 1 
                AND (fecha_desbloqueo IS NULL OR fecha_desbloqueo > NOW())
                LIMIT 1
            ");
            $stmt->execute([':username' => $username]);
            $result = $stmt->fetch();
            
            return $result ?: [];
        } catch (PDOException $e) {
            error_log("Error al verificar bloqueo de usuario: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Registra un intento de login
     * 
     * @param string $ip Dirección IP
     * @param string $username Nombre de usuario (opcional)
     * @param bool $success Si el login fue exitoso
     * @return void
     */
    private static function recordAttempt($ip, $username = null, $success = false) {
        error_log("RateLimiter::recordAttempt llamado - IP: $ip, Username: $username, Success: " . ($success ? 'true' : 'false'));
        
        $pdo = self::getSecurityConnection();
        if (!$pdo) {
            error_log("RateLimiter: No se pudo conectar a la base de datos de seguridad");
            return;
        }
        
        try {
            // Buscar registro existente
            $stmt = $pdo->prepare("
                SELECT * FROM seguridad_ip 
                WHERE direccion_ip = :ip 
                ORDER BY fecha_ultima_peticion DESC 
                LIMIT 1
            ");
            $stmt->execute([':ip' => $ip]);
            $existing = $stmt->fetch();
            
            error_log("RateLimiter: Registro existente: " . ($existing ? 'Sí' : 'No'));
            
            $userAgent = self::getUserAgent();
            $now = date('Y-m-d H:i:s');
            
            if ($existing) {
                // Actualizar registro existente
                $peticionesTotales = ($existing['peticiones_totales'] ?? 0) + 1;
                $peticionesSospechosas = $success ? ($existing['peticiones_sospechosas'] ?? 0) : ($existing['peticiones_sospechosas'] ?? 0) + 1;
                
                // Calcular nivel de riesgo
                $nivelRiesgo = self::calculateRiskLevel($peticionesSospechosas, $peticionesTotales);
                
                $stmt = $pdo->prepare("
                    UPDATE seguridad_ip 
                    SET peticiones_totales = :peticiones_totales,
                        peticiones_sospechosas = :peticiones_sospechosas,
                        fecha_ultima_peticion = :fecha_ultima_peticion,
                        username = :username,
                        agente_usuario = :agente_usuario,
                        nivel_riesgo = :nivel_riesgo
                    WHERE id_seguridad_ip = :id
                ");
                $stmt->execute([
                    ':peticiones_totales' => $peticionesTotales,
                    ':peticiones_sospechosas' => $peticionesSospechosas,
                    ':fecha_ultima_peticion' => $now,
                    ':username' => $username,
                    ':agente_usuario' => $userAgent,
                    ':nivel_riesgo' => $nivelRiesgo,
                    ':id' => $existing['id_seguridad_ip']
                ]);
                
                error_log("RateLimiter: Registro actualizado - ID: {$existing['id_seguridad_ip']}, Peticiones: $peticionesTotales, Sospechosas: $peticionesSospechosas");
            } else {
                // Crear nuevo registro
                $peticionesSospechosas = $success ? 0 : 1;
                $nivelRiesgo = self::calculateRiskLevel($peticionesSospechosas, 1);
                
                $stmt = $pdo->prepare("
                    INSERT INTO seguridad_ip 
                    (direccion_ip, username, tipo_bloqueo, peticiones_totales, peticiones_sospechosas, 
                     fecha_ultima_peticion, esta_bloqueado, nivel_riesgo, agente_usuario)
                    VALUES 
                    (:direccion_ip, :username, 'ip', :peticiones_totales, :peticiones_sospechosas,
                     :fecha_ultima_peticion, 0, :nivel_riesgo, :agente_usuario)
                ");
                $stmt->execute([
                    ':direccion_ip' => $ip,
                    ':username' => $username,
                    ':peticiones_totales' => 1,
                    ':peticiones_sospechosas' => $peticionesSospechosas,
                    ':fecha_ultima_peticion' => $now,
                    ':nivel_riesgo' => $nivelRiesgo,
                    ':agente_usuario' => $userAgent
                ]);
                
                error_log("RateLimiter: Nuevo registro creado - IP: $ip, Username: $username");
            }
        } catch (PDOException $e) {
            error_log("Error al registrar intento: " . $e->getMessage());
        }
    }
    
    /**
     * Bloquea una IP o usuario
     * 
     * @param string $ip Dirección IP
     * @param string $username Nombre de usuario (opcional)
     * @param string $reason Motivo del bloqueo
     * @param string $blockType Tipo de bloqueo ('ip' o 'usuario')
     * @return void
     */
    private static function block($ip, $username = null, $reason = 'Muchos intentos de ingreso fallidos', $blockType = 'ip') {
        $pdo = self::getSecurityConnection();
        if (!$pdo) {
            return;
        }
        
        try {
            $now = date('Y-m-d H:i:s');
            $unlockTime = date('Y-m-d H:i:s', time() + self::BLOCK_DURATION);
            
            if ($blockType === 'usuario' && $username) {
                // Bloquear usuario
                $stmt = $pdo->prepare("
                    UPDATE seguridad_ip 
                    SET esta_bloqueado = 1,
                        fecha_bloqueo = :fecha_bloqueo,
                        fecha_desbloqueo = :fecha_desbloqueo,
                        motivo_bloqueo = :motivo_bloqueo,
                        nivel_riesgo = 'alto'
                    WHERE username = :username
                    AND tipo_bloqueo = 'usuario'
                ");
                $stmt->execute([
                    ':fecha_bloqueo' => $now,
                    ':fecha_desbloqueo' => $unlockTime,
                    ':motivo_bloqueo' => $reason,
                    ':username' => $username
                ]);
            } else {
                // Bloquear IP
                $stmt = $pdo->prepare("
                    UPDATE seguridad_ip 
                    SET esta_bloqueado = 1,
                        fecha_bloqueo = :fecha_bloqueo,
                        fecha_desbloqueo = :fecha_desbloqueo,
                        motivo_bloqueo = :motivo_bloqueo,
                        nivel_riesgo = 'alto'
                    WHERE direccion_ip = :ip
                    AND tipo_bloqueo = 'ip'
                ");
                $stmt->execute([
                    ':fecha_bloqueo' => $now,
                    ':fecha_desbloqueo' => $unlockTime,
                    ':motivo_bloqueo' => $reason,
                    ':ip' => $ip
                ]);
            }
        } catch (PDOException $e) {
            error_log("Error al bloquear IP/usuario: " . $e->getMessage());
        }
    }
    
    /**
     * Calcula el nivel de riesgo basado en intentos fallidos
     * 
     * @param int $failedAttempts Intentos fallidos
     * @param int $totalAttempts Total de intentos
     * @return string Nivel de riesgo ('bajo', 'medio', 'alto', 'critico')
     */
    private static function calculateRiskLevel($failedAttempts, $totalAttempts) {
        if ($failedAttempts === 0) {
            return 'bajo';
        } elseif ($failedAttempts < 3) {
            return 'bajo';
        } elseif ($failedAttempts < 5) {
            return 'medio';
        } elseif ($failedAttempts < 10) {
            return 'alto';
        } else {
            return 'critico';
        }
    }
    
    /**
     * Cuenta los intentos fallidos recientes de una IP
     * 
     * @param string $ip Dirección IP
     * @param int $window Ventana de tiempo en segundos
     * @return int Número de intentos fallidos
     */
    private static function countRecentFailedAttempts($ip, $window = self::ATTEMPT_WINDOW) {
        $pdo = self::getSecurityConnection();
        if (!$pdo) {
            return 0;
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT peticiones_sospechosas FROM seguridad_ip 
                WHERE direccion_ip = :ip 
                AND fecha_ultima_peticion > DATE_SUB(NOW(), INTERVAL :window SECOND)
                ORDER BY fecha_ultima_peticion DESC 
                LIMIT 1
            ");
            $stmt->execute([
                ':ip' => $ip,
                ':window' => $window
            ]);
            $result = $stmt->fetch();
            
            return $result ? (int)$result['peticiones_sospechosas'] : 0;
        } catch (PDOException $e) {
            error_log("Error al contar intentos fallidos: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Verifica si se permite el intento de login
     * 
     * @param string $username Nombre de usuario (opcional)
     * @return array Resultado con 'allowed' (bool) y 'message' (string)
     */
    public static function checkLoginAttempt($username = null) {
        error_log("RateLimiter::checkLoginAttempt llamado - Username: $username");
        
        $ip = self::getClientIP();
        error_log("RateLimiter: IP del cliente: $ip");
        
        // Verificar si la IP está bloqueada
        $ipBlock = self::isIPBlocked($ip);
        if ($ipBlock) {
            $remainingTime = strtotime($ipBlock['fecha_desbloqueo']) - time();
            $minutes = ceil($remainingTime / 60);
            error_log("RateLimiter: IP bloqueada - Tiempo restante: $minutes minutos");
            return [
                'allowed' => false,
                'message' => "Su IP ha sido bloqueada temporalmente por seguridad. Intente nuevamente en {$minutes} minutos.",
                'block_info' => $ipBlock
            ];
        }
        
        // Verificar si el usuario está bloqueado (si se proporciona username)
        if ($username) {
            $userBlock = self::isUserBlocked($username);
            if ($userBlock) {
                $remainingTime = strtotime($userBlock['fecha_desbloqueo']) - time();
                $minutes = ceil($remainingTime / 60);
                error_log("RateLimiter: Usuario bloqueado - Tiempo restante: $minutes minutos");
                return [
                    'allowed' => false,
                    'message' => "El usuario ha sido bloqueado temporalmente por seguridad. Intente nuevamente en {$minutes} minutos.",
                    'block_info' => $userBlock
                ];
            }
        }
        
        // Verificar límite de intentos
        $failedAttempts = self::countRecentFailedAttempts($ip);
        error_log("RateLimiter: Intentos fallidos recientes: $failedAttempts");
        
        if ($failedAttempts >= self::MAX_ATTEMPTS) {
            // Bloquear IP
            self::block($ip, $username, 'Too many failed login attempts', 'ip');
            error_log("RateLimiter: Bloqueo por exceso de intentos");
            return [
                'allowed' => false,
                'message' => "Demasiados intentos fallidos. Su IP ha sido bloqueada temporalmente por " . (self::BLOCK_DURATION / 60) . " minutos."
            ];
        }
        
        error_log("RateLimiter: Intento permitido");
        return ['allowed' => true];
    }
    
    /**
     * Registra un intento de login exitoso
     * 
     * @param string $username Nombre de usuario
     * @return void
     */
    public static function recordSuccessfulLogin($username) {
        error_log("RateLimiter::recordSuccessfulLogin llamado - Username: $username");
        $ip = self::getClientIP();
        self::recordAttempt($ip, $username, true);
    }
    
    /**
     * Registra un intento de login fallido
     * 
     * @param string $username Nombre de usuario
     * @return void
     */
    public static function recordFailedLogin($username) {
        error_log("RateLimiter::recordFailedLogin llamado - Username: $username");
        $ip = self::getClientIP();
        self::recordAttempt($ip, $username, false);
        
        // Verificar si se debe bloquear
        $failedAttempts = self::countRecentFailedAttempts($ip);
        if ($failedAttempts >= self::MAX_ATTEMPTS) {
            self::block($ip, $username, 'Too many failed login attempts', 'ip');
        }
    }
}
