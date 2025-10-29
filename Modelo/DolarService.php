<?php
require_once 'config/config.php';

class DolarService {
    private $url = 'https://www.bcv.org.ve';
    
    public function obtenerPrecioDolar() {
        try {
            // Usar cURL para obtener el contenido de la página
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception('Error al conectar con BCV: ' . curl_error($ch));
            }
            
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('Error HTTP: ' . $httpCode);
            }
            
            // Buscar el div con id="dolar" y el strong dentro
            if (preg_match('/<div id="dolar".*?<strong>\s*([\d,]+)\s*<\/strong>/s', $html, $matches)) {
                $precioDolar = str_replace(',', '.', str_replace('.', '', $matches[1]));
                return floatval($precioDolar);
            } else {
                throw new Exception('No se pudo encontrar el precio del dólar en la página del BCV');
            }
            
        } catch (Exception $e) {
            // En caso de error, usar un valor por defecto o de caché
            error_log('Error obteniendo precio del dólar: ' . $e->getMessage());
            return $this->obtenerPrecioCache();
        }
    }
    
    private function obtenerPrecioCache() {
        // Intentar obtener de la base de datos
        try {
            $conexion = new BD('P');
            $db = $conexion->getConexion();
            
            $stmt = $db->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && (time() - strtotime($result['fecha'])) < 86400) { // Usar cache si tiene menos de 24 horas
                return floatval($result['precio']);
            }
        } catch (Exception $e) {
            error_log('Error al obtener cache del dólar: ' . $e->getMessage());
        }
        
        // Valor por defecto si no hay cache válido
        return 35.50;
    }
    
    public function guardarPrecioCache($precio) {
        try {
            $conexion = new BD('P');
            $db = $conexion->getConexion();
            
            $stmt = $db->prepare("INSERT INTO dolar_cache (precio, fecha) VALUES (?, NOW())");
            $stmt->execute([$precio]);
            return true;
        } catch (Exception $e) {
            error_log('Error al guardar cache del dólar: ' . $e->getMessage());
            return false;
        }
    }
}
?>