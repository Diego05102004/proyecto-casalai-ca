<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class cliente extends BD {
    private $tableclientes = 'tbl_clientes';
    private $conex;
    private $nombre;
    private $direccion;
    private $telefono;
    private $cedula;
    private $correo;
    private $activo = 1;
    private $id;

    public function __construct() {
        $this->conex = null;
    }

    // Getters y Setters
    public function setnombre($nombre) {
        $this->nombre = $nombre;
    }

    public function getnombre() {
        return $this->nombre;
    }

    public function setdireccion($direccion) {
        $this->direccion = $direccion;
    }

    public function getdireccion() {
        return $this->direccion;
    }

    public function settelefono($telefono) {
        $this->telefono = $telefono;
    }

    public function gettelefono() {
        return $this->telefono;
    }


    public function setcedula($cedula) {
        $this->cedula = $cedula;
    }

    public function getcedula() {
        return $this->cedula;
    }

    public function setcorreo($correo) {
        $this->correo = $correo;
    }

    public function getcorreo() {
        return $this->correo;
    }

    
    public function getId() {
        return $this->id;
    }
    public function setId($id) {
        $this->id = $id;
    }

    public function validarDatos($esModificacion = false) {
        $errores = [];

        // Validar nombre del cliente
        $nombre = trim((string)$this->nombre);
        if ($nombre === '') {
            $errores['nombre'] = 'El nombre del cliente es obligatorio';
        } elseif (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 200) {
            $errores['nombre'] = 'El nombre del cliente debe tener entre 2 y 200 caracteres';
        } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre)) {
            $errores['nombre'] = 'El nombre del cliente solo puede contener letras, números, espacios y caracteres especiales comunes';
        }

        // Validar cédula (formato: 1.234.567 o 12.345.678)
        $cedula = trim((string)$this->cedula);
        if ($cedula === '') {
            $errores['cedula'] = 'La cédula del cliente es obligatoria';
        } elseif (!preg_match('/^(?:\d{1,2}\.\d{3}\.\d{3})$/', $cedula)) {
            $errores['cedula'] = 'La cédula debe tener el formato 1.234.567 o 12.345.678';
        }

        // Validar teléfono (opcional pero si se proporciona debe ser válido)
        $telefono = trim((string)$this->telefono);
        if ($telefono !== '') {
            if (mb_strlen($telefono) < 7 || mb_strlen($telefono) > 20) {
                $errores['telefono'] = 'El teléfono debe tener entre 7 y 20 caracteres';
            } elseif (!preg_match('/^[0-9\-\+\(\)\s]+$/', $telefono)) {
                $errores['telefono'] = 'El teléfono solo puede contener números, guiones, paréntesis y el signo +';
            }
        }

        // Validar dirección (opcional)
        $direccion = trim((string)$this->direccion);
        if ($direccion !== '' && mb_strlen($direccion) > 500) {
            $errores['direccion'] = 'La dirección no debe exceder los 500 caracteres';
        }

        // Validar correo electrónico (opcional pero si se proporciona debe ser válido)
        $correo = trim((string)$this->correo);
        if ($correo !== '') {
            if (mb_strlen($correo) > 255) {
                $errores['correo'] = 'El correo electrónico no debe exceder los 255 caracteres';
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $errores['correo'] = 'El formato del correo electrónico no es válido';
            }
        }

        // Validar ID del cliente (solo para modificaciones)
        if ($esModificacion) {
            $id = (int)$this->id;
            if ($id <= 0) {
                $errores['id_cliente'] = 'El ID del cliente no es válido';
            }
        }

        return $errores;
    }

    public function ingresarclientes() {
        return $this->r_cliente();
    }
    private function r_cliente() {
        $conexion = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) {
                $this->conex = $this->getConexion();
            }
            if (!($this->conex instanceof PDO)) {
                $conexion = new BD('P');
                $this->conex = $conexion->getConexion();
                $created = true;
            }
        }
        try {
            $sql = "INSERT INTO tbl_clientes (`nombre`, `cedula`, `direccion`, `telefono`, `correo`, `activo`)
                    VALUES (:nombre, :cedula, :direccion, :telefono, :correo, 1)";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':direccion', $this->direccion);
            $stmt->bindParam(':telefono', $this->telefono);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->bindParam(':correo', $this->correo);
            return $stmt->execute();
        } finally {
            if ($conexion) { $conexion->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    // En modelo/cliente.php
    public function listarTodosClientes() {
        $conexion = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) {
                $this->conex = $this->getConexion();
            }
            if (!($this->conex instanceof PDO)) {
                $conexion = new BD('P');
                $this->conex = $conexion->getConexion();
                $created = true;
            }
        }
        $this->conex->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try {
            $stmt = $this->conex->prepare("SELECT id_clientes, nombre, cedula FROM tbl_clientes WHERE activo = 1 ORDER BY nombre");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        } finally {
            if ($conexion) { $conexion->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    public function existeNumeroCedula($cedula, $excluir_id = null) {
        return $this->existeNumCedula($cedula, $excluir_id); 
    }
    private function existeNumCedula($cedula, $excluir_id) {
        $conexion = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) {
                $this->conex = $this->getConexion();
            }
            if (!($this->conex instanceof PDO)) {
                $conexion = new BD('P');
                $this->conex = $conexion->getConexion();
                $created = true;
            }
        }
        try {
            $sql = "SELECT COUNT(*) FROM tbl_clientes WHERE cedula = ?";
            $params = [$cedula];
            if ($excluir_id !== null) {
                $sql .= " AND id_clientes != ?";
                $params[] = $excluir_id;
            }
            $stmt = $this->conex->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } finally {
            if ($conexion) { $conexion->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    public function obtenerUltimoCliente() {
        return $this->obtUltimaCliente(); 
    }
    private function obtUltimaCliente() {
        $conexion = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) {
                $this->conex = $this->getConexion();
            }
            if (!($this->conex instanceof PDO)) {
                $conexion = new BD('P');
                $this->conex = $conexion->getConexion();
                $created = true;
            }
        }
        try {
            $sql = "SELECT * FROM tbl_clientes ORDER BY id_clientes DESC LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            return $cliente ? $cliente : null;
        } catch (PDOException $e) {
            return null;
        } finally {
            if ($conexion) { $conexion->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    function obtenerReporteComprasClientes() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "SELECT c.nombre, COUNT(d.id_producto) AS cantidad
FROM tbl_clientes c
JOIN tbl_despachos ds ON c.id_clientes = ds.id_clientes
JOIN tbl_despacho_detalle d ON ds.id_despachos = d.id_despacho
GROUP BY c.id_clientes, c.nombre
ORDER BY cantidad DESC
LIMIT 10;";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }
    public function obtenerclientesPorId($id) {
        return $this->obtClientePorId($id);
    }
    private function obtClientePorId($id) {
        $conexion = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) {
                $this->conex = $this->getConexion();
            }
            if (!($this->conex instanceof PDO)) {
                $conexion = new BD('P');
                $this->conex = $conexion->getConexion();
                $created = true;
            }
        }
        try {
            $query = "SELECT * FROM tbl_clientes WHERE id_clientes = ?";
            $stmt = $this->conex->prepare($query);
            $stmt->execute([$id]);
            $clientes = $stmt->fetch(PDO::FETCH_ASSOC);
            return $clientes;
        } finally {
            if ($conexion) { $conexion->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    public function modificarclientes($id) {
        return $this->m_cliente($id);
    }
    private function m_cliente($id) {
        $conexion = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) {
                $this->conex = $this->getConexion();
            }
            if (!($this->conex instanceof PDO)) {
                $conexion = new BD('P');
                $this->conex = $conexion->getConexion();
                $created = true;
            }
        }
        try {
            $sql = "UPDATE tbl_clientes SET nombre = :nombre, cedula = :cedula, direccion = :direccion, telefono = :telefono, correo = :correo, activo = :activo WHERE id_clientes = :id_clientes";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_clientes', $id);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':direccion', $this->direccion);
            $stmt->bindParam(':telefono', $this->telefono);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->bindParam(':correo', $this->correo);
            $stmt->bindParam(':activo', $this->activo);
            return $stmt->execute();
        } finally {
            if ($conexion) { $conexion->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    function eliminar_l($id) {
        $conexion = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) {
                $this->conex = $this->getConexion();
            }
            if (!($this->conex instanceof PDO)) {
                $conexion = new BD('P');
                $this->conex = $conexion->getConexion();
                $created = true;
            }
        }
        try {
            $sql = "UPDATE tbl_clientes SET activo = 0 WHERE id_clientes = :id_clientes";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_clientes', $id);
            return $stmt->execute();
        } finally {
            if ($conexion) { $conexion->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    public function eliminarclientes($id) {
        return $this->e_cliente($id);
    }
    private function e_cliente($id) {
        $conexion = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) {
                $this->conex = $this->getConexion();
            }
            if (!($this->conex instanceof PDO)) {
                $conexion = new BD('P');
                $this->conex = $conexion->getConexion();
                $created = true;
            }
        }
        try {
            $sql = "DELETE FROM tbl_clientes WHERE id_clientes = :id_clientes";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_clientes', $id);
            $result = $stmt->execute();
            return $result;
        } finally {
            if ($conexion) { $conexion->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    public function getclientes() {
        return $this->g_clientes();
    }
    private function g_clientes() {
        $conexion = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) {
                $this->conex = $this->getConexion();
            }
            if (!($this->conex instanceof PDO)) {
                $conexion = new BD('P');
                $this->conex = $conexion->getConexion();
                $created = true;
            }
        }
        try {
            $queryclientes = 'SELECT * FROM ' . $this->tableclientes;
            $stmtclientes = $this->conex->prepare($queryclientes);
            $stmtclientes->execute();
            $clientes = $stmtclientes->fetchAll(PDO::FETCH_ASSOC);
            return $clientes;
        } finally {
            if ($conexion) { $conexion->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }
}






