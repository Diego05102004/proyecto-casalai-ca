<?php
namespace Usuario\ProyectoCasalaiCa\Config;

use PDO;
use PDOException;

class BD {
    protected $pdo = null;
    private $config = [];

    // $tipo: 'P' para principal, 'S' para seguridad
    public function __construct($tipo = 'P') {
        // Cargar configuración según el tipo de conexión
        $this->loadConfig($tipo);
        
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
            
            $this->pdo = new PDO($dsn, $this->config['user'], $this->config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Error de conexión: " . $e->getMessage());
        }
    }
    
    private function loadConfig($tipo) {
        // Configuración de la base de datos principal
        $mainConfig = [
            'host' => 'localhost',
            'dbname' => 'casalai',
            'user' => 'root',
            'pass' => '',
            'charset' => 'utf8mb4'
        ];
        
        // Configuración de la base de datos de seguridad
        $securityConfig = [
            'host' => 'localhost',
            'dbname' => 'seguridadlai',
            'user' => 'root',
            'pass' => '',
            'charset' => 'utf8mb4'
        ];
        
        $this->config = ($tipo === 'S') ? $securityConfig : $mainConfig;
    }

    public function getConexion() {
        return $this->pdo;
    }

    public function cerrar() {
        $this->pdo = null;
    }
}
