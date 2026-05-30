<?php
namespace Usuario\ProyectoCasalaiCa\Config;

/**
 * Clase de cifrado AES-256-CBC
 * Sigue estándares internacionales de seguridad (NIST, FIPS 197)
 * 
 * Características:
 * - Algoritmo: AES-256-CBC (Advanced Encryption Standard)
 * - Longitud de clave: 256 bits (32 bytes)
 * - Modo: CBC (Cipher Block Chaining)
 * - Padding: PKCS7
 * - IV (Initialization Vector): 128 bits (16 bytes), generado aleatoriamente para cada cifrado
 * - Codificación: Base64 para almacenamiento en base de datos
 */
class Encryption {
    private $key;
    private $method = 'AES-256-CBC';
    
    /**
     * Constructor
     * @param string $key Clave de cifrado (debe ser 32 bytes para AES-256)
     */
    public function __construct($key = null) {
        if ($key === null) {
            // Clave por defecto - EN PRODUCCIÓN DEBE ESTAR EN VARIABLES DE ENTORNO
            $key = $this->getDefaultKey();
        }
        
        // Asegurar que la clave tenga 32 bytes (256 bits)
        $this->key = $this->normalizeKey($key);
    }
    
    /**
     * Normaliza la clave a 32 bytes usando SHA-256
     * @param string $key
     * @return string
     */
    private function normalizeKey($key) {
        return hash('sha256', $key, true);
    }
    
    /**
     * Obtiene la clave por defecto desde configuración
     * EN PRODUCCIÓN: Usar variables de entorno o archivo de configuración seguro
     */
    private function getDefaultKey() {
        // Clave por defecto - CAMBIAR EN PRODUCCIÓN
        return 'ClaveSecretaParaCifradoAES256DebeSerMuyLarga';
    }
    
    /**
     * Cifra un dato usando AES-256-CBC
     * @param string $data Datos a cifrar
     * @return string Datos cifrados en Base64 (IV + datos cifrados)
     */
    public function encrypt($data) {
        // Manejar valores nulos o vacíos
        if ($data === null) {
            return null;
        }
        
        if ($data === '') {
            return '';
        }
        
        // Asegurar que sea string
        $data = (string)$data;
        
        // Generar IV aleatorio de 16 bytes
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
        
        // Cifrar los datos
        $encrypted = openssl_encrypt($data, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);
        
        if ($encrypted === false) {
            throw new \RuntimeException('Error al cifrar datos: ' . openssl_error_string());
        }
        
        // Combinar IV y datos cifrados, luego codificar en Base64
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Descifra un dato cifrado con AES-256-CBC
     * @param string $encryptedData Datos cifrados en Base64
     * @return string Datos descifrados
     */
    public function decrypt($encryptedData) {
        // Manejar valores nulos o vacíos
        if ($encryptedData === null) {
            return null;
        }
        
        if ($encryptedData === '') {
            return '';
        }
        
        // Asegurar que sea string
        $encryptedData = (string)$encryptedData;
        
        // Decodificar de Base64
        $data = base64_decode($encryptedData);
        
        if ($data === false) {
            // Si no es Base64 válido, podría ser un dato no cifrado (compatibilidad)
            // Retornar valor original sin log para no saturar
            return $encryptedData;
        }
        
        // Verificar longitud mínima (IV + datos cifrados)
        $ivLength = openssl_cipher_iv_length($this->method);
        if (strlen($data) < $ivLength) {
            // Datos demasiado cortos = probablemente dato no cifrado antiguo
            // Retornar valor original sin log para no saturar
            return $encryptedData;
        }
        
        // Extraer IV (primeros 16 bytes)
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        // Descifrar los datos
        $decrypted = openssl_decrypt($encrypted, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);
        
        if ($decrypted === false) {
            // Si falla el descifrado, retornar valor original (compatibilidad)
            return $encryptedData;
        }
        
        return $decrypted;
    }
    
    /**
     * Cifra un array de datos
     * @param array $data Array de datos a cifrar
     * @param array $fields Campos a cifrar
     * @return array Array con campos cifrados
     */
    public function encryptArray($data, $fields) {
        if (!is_array($data)) {
            return $data;
        }
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Descifra un array de datos
     * @param array $data Array de datos cifrados
     * @param array $fields Campos a descifrar
     * @return array Array con campos descifrados
     */
    public function decryptArray($data, $fields) {
        if (!is_array($data)) {
            return $data;
        }
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                try {
                    $valorOriginal = $data[$field];
                    $data[$field] = $this->decrypt($data[$field]);
                    
                    // Log solo si realmente se descifró algo (para depuración)
                    if ($valorOriginal !== $data[$field] && strlen($valorOriginal) > 20) {
                        error_log("Descifrado exitoso para campo '$field': longitud original=" . strlen($valorOriginal) . ", longitud descifrada=" . strlen($data[$field]));
                    }
                } catch (\Exception $e) {
                    // Si falla el descifrado, mantener el valor original
                    // Esto permite compatibilidad con datos no cifrados existentes
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Cifra un array de resultados de base de datos
     * @param array $results Array de resultados
     * @param array $fields Campos a cifrar
     * @return array Array con campos cifrados
     */
    public function encryptResults($results, $fields) {
        if (!is_array($results)) {
            return $results;
        }
        
        foreach ($results as &$row) {
            $row = $this->encryptArray($row, $fields);
        }
        
        return $results;
    }
    
    /**
     * Descifra un array de resultados de base de datos
     * @param array $results Array de resultados
     * @param array $fields Campos a descifrar
     * @return array Array con campos descifrados
     */
    public function decryptResults($results, $fields) {
        if (!is_array($results)) {
            return $results;
        }
        
        foreach ($results as &$row) {
            $row = $this->decryptArray($row, $fields);
        }
        
        return $results;
    }
}
