<?php
namespace Usuario\ProyectoCasalaiCa\Config;

/**
 * Clase de cifrado híbrido RSA+AES-256-CBC
 * Sigue estándares internacionales de seguridad (NIST, FIPS 197, PKCS#1)
 * 
 * Características:
 * - Algoritmo híbrido: RSA para proteger clave AES + AES-256-CBC para datos
 * - RSA: 2048 bits para cifrado asimétrico de clave AES
 * - AES-256-CBC: Para cifrado simétrico de datos (rápido y eficiente)
 * - Longitud de clave AES: 256 bits (32 bytes), generada aleatoriamente por sesión
 * - Modo AES: CBC (Cipher Block Chaining)
 * - Padding: PKCS7
 * - IV (Initialization Vector): 128 bits (16 bytes), generado aleatoriamente para cada cifrado
 * - Codificación: Base64 para almacenamiento en base de datos
 * - Variables de entorno: Claves RSA cargadas desde entorno para máxima seguridad
 */
class Encryption {
    private $rsaPublicKey;
    private $rsaPrivateKey;
    private $aesMethod = 'AES-256-CBC';
    
    /**
     * Constructor
     * Carga claves RSA desde variables de entorno
     */
    public function __construct() {
        $this->loadRSAKeys();
    }
    
    /**
     * Carga claves RSA desde variables de entorno
     * @throws \RuntimeException si las claves no están configuradas
     */
    private function loadRSAKeys() {
        // Cargar claves desde variables de entorno
        $publicKey = getenv('RSA_PUBLIC_KEY');
        $privateKey = getenv('RSA_PRIVATE_KEY');
        
        if ($publicKey === false || $privateKey === false) {
            throw new \RuntimeException(
                'Las claves RSA no están configuradas en variables de entorno. ' .
                'Configure RSA_PUBLIC_KEY y RSA_PRIVATE_KEY en el archivo .env'
            );
        }
        
        // Validar formato de claves (PEM)
        if (!strpos($publicKey, '-----BEGIN PUBLIC KEY-----') || 
            !strpos($privateKey, '-----BEGIN PRIVATE KEY-----')) {
            throw new \RuntimeException(
                'Las claves RSA no tienen formato PEM válido. ' .
                'Asegúrese de incluir los encabezados -----BEGIN/END PUBLIC/PRIVATE KEY-----'
            );
        }
        
        $this->rsaPublicKey = $publicKey;
        $this->rsaPrivateKey = $privateKey;
    }
    
    /**
     * Cifra un dato usando cifrado híbrido RSA+AES-256-CBC
     * Proceso:
     * 1. Generar clave AES aleatoria (32 bytes)
     * 2. Cifrar datos con AES-256-CBC
     * 3. Cifrar clave AES con RSA-2048
     * 4. Combinar: clave AES cifrada + IV + datos cifrados
     * 
     * @param string $data Datos a cifrar
     * @return string Datos cifrados en Base64
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
        
        // 1. Generar clave AES aleatoria de 32 bytes (256 bits)
        $aesKey = openssl_random_pseudo_bytes(32);
        
        // 2. Generar IV aleatorio de 16 bytes (128 bits)
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->aesMethod));
        
        // 3. Cifrar datos con AES-256-CBC
        $encryptedData = openssl_encrypt($data, $this->aesMethod, $aesKey, OPENSSL_RAW_DATA, $iv);
        
        if ($encryptedData === false) {
            throw new \RuntimeException('Error al cifrar datos con AES: ' . openssl_error_string());
        }
        
        // 4. Cifrar clave AES con RSA (clave pública)
        $encryptedAesKey = openssl_encrypt($aesKey, 'RSA-OAEP', $this->rsaPublicKey, OPENSSL_RAW_DATA);
        
        if ($encryptedAesKey === false) {
            throw new \RuntimeException('Error al cifrar clave AES con RSA: ' . openssl_error_string());
        }
        
        // 5. Combinar: longitud clave AES cifrada (4 bytes) + clave AES cifrada + IV + datos cifrados
        $result = pack('N', strlen($encryptedAesKey)) . $encryptedAesKey . $iv . $encryptedData;
        
        // 6. Codificar en Base64
        return base64_encode($result);
    }
    
    /**
     * Descifra un dato cifrado con cifrado híbrido RSA+AES-256-CBC
     * Proceso:
     * 1. Decodificar de Base64
     * 2. Extraer longitud de clave AES cifrada
     * 3. Extraer clave AES cifrada
     * 4. Descifrar clave AES con RSA (clave privada)
     * 5. Extraer IV y datos cifrados
     * 6. Descifrar datos con AES-256-CBC
     * 
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
        
        // 1. Decodificar de Base64
        $data = base64_decode($encryptedData);
        
        if ($data === false) {
            // Si no es Base64 válido, podría ser un dato no cifrado (compatibilidad)
            return $encryptedData;
        }
        
        // Verificar longitud mínima (4 bytes longitud + clave AES + IV + datos)
        if (strlen($data) < 4 + 16) {
            // Datos demasiado cortos = probablemente dato no cifrado antiguo
            return $encryptedData;
        }
        
        try {
            // 2. Extraer longitud de clave AES cifrada (primeros 4 bytes)
            $keyLength = unpack('N', substr($data, 0, 4))[1];
            
            // Verificar que hay suficientes datos
            if (strlen($data) < 4 + $keyLength + 16) {
                return $encryptedData;
            }
            
            // 3. Extraer clave AES cifrada
            $encryptedAesKey = substr($data, 4, $keyLength);
            
            // 4. Descifrar clave AES con RSA (clave privada)
            $aesKey = openssl_decrypt($encryptedAesKey, 'RSA-OAEP', $this->rsaPrivateKey, OPENSSL_RAW_DATA);
            
            if ($aesKey === false) {
                throw new \RuntimeException('Error al descifrar clave AES con RSA: ' . openssl_error_string());
            }
            
            // 5. Extraer IV (16 bytes) y datos cifrados
            $iv = substr($data, 4 + $keyLength, 16);
            $encryptedDataPart = substr($data, 4 + $keyLength + 16);
            
            // 6. Descifrar datos con AES-256-CBC
            $decrypted = openssl_decrypt($encryptedDataPart, $this->aesMethod, $aesKey, OPENSSL_RAW_DATA, $iv);
            
            if ($decrypted === false) {
                throw new \RuntimeException('Error al descifrar datos con AES: ' . openssl_error_string());
            }
            
            return $decrypted;
            
        } catch (\Exception $e) {
            // Si falla el descifrado, retornar valor original (compatibilidad)
            error_log('Error al descifrar datos: ' . $e->getMessage());
            return $encryptedData;
        }
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
