<?php 
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
class Usuarios extends BD {
    
    private $conex;
    private $con;

    private $id_usuario;
    private $username;
    private $clave;
    private $id_rol;
    private $activo = 1;
    private $tableusuarios = 'tbl_usuarios';
    private $nombre;
    private $apellido;
    private $correo;
    private $telefono;
    private $estatus = 1;
    private $usuarios;
    private $cedula;

    // Constantes de validación
    const MAX_ID_USUARIO = 999999999;
    const MIN_ID_USUARIO = 1;
    const MAX_USERNAME = 50;
    const MIN_USERNAME = 3;
    const MAX_CLAVE = 255;
    const MIN_CLAVE = 8;
    const MAX_NOMBRE = 100;
    const MIN_NOMBRE = 2;
    const MAX_APELLIDO = 100;
    const MIN_APELLIDO = 2;
    const MAX_CORREO = 150;
    const MAX_TELEFONO = 15;
    const MIN_TELEFONO = 7;
    const MAX_CEDULA = 10;
    const MIN_CEDULA = 6;
    const MAX_ID_ROL = 999999999;
    const MIN_ID_ROL = 1;
    const ESTADOS_VALIDOS = ['habilitado', 'deshabilitado', 'inhabilitado'];

    public function __construct() {
        $this->conex = null;
        $this->con = null;
    }

    // Getters y Setters
    public function getUsername() { return $this->username; }
    public function setUsername($username) { $this->username = $username; }

    public function getActivo() { return $this->activo; }
    public function setActivo($activo) { $this->activo = $activo; }

    public function getUsuario() { return $this->usuarios; }
    public function setUsuario($usuario) { $this->usuarios = $usuario; }

    public function getEstatus() { return $this->estatus; }
    public function setEstatus($estatus) { $this->estatus = $estatus; }

    public function getClave() { return $this->clave; }
    public function setClave($clave) { $this->clave = $clave; }

    public function getRango() { return $this->id_rol; }
    public function setRango($id_rol) { $this->id_rol = $id_rol; }

    public function getId() { return $this->id_usuario; }
    public function setId($id_usuario) { $this->id_usuario = $id_usuario; }

    public function getNombre() { return $this->nombre; }
    public function setNombre($nombre) { $this->nombre = $nombre; }

    public function getApellido() { return $this->apellido; }
    public function setApellido($apellido) { $this->apellido = $apellido; }

    public function getCorreo() { return $this->correo; }
    public function setCorreo($correo) { $this->correo = $correo; }

    public function getTelefono() { return $this->telefono; }
    public function setTelefono($telefono) { $this->telefono = $telefono; }

    public function getCedula() { return $this->cedula; }
    public function setCedula($cedula) { $this->cedula = $cedula; }

    
    // Métodos de validación centralizados
    private function validarUsuario($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['usuario'] = 'Los datos del usuario deben ser un arreglo';
            return $errores;
        }
        
        // Validar ID del usuario
        if (isset($datos['id_usuario'])) {
            $id_usuario = (int)$datos['id_usuario'];
            if ($id_usuario < self::MIN_ID_USUARIO || $id_usuario > self::MAX_ID_USUARIO) {
                $errores['id_usuario'] = 'El ID del usuario debe ser un número entre ' . self::MIN_ID_USUARIO . ' y ' . self::MAX_ID_USUARIO;
            }
        }
        
        // Validar username
        if (isset($datos['username'])) {
            $username = trim((string)$datos['username']);
            if ($username === '') {
                $errores['username'] = 'El nombre de usuario es obligatorio';
            } elseif (mb_strlen($username) < self::MIN_USERNAME || mb_strlen($username) > self::MAX_USERNAME) {
                $errores['username'] = 'El nombre de usuario debe tener entre ' . self::MIN_USERNAME . ' y ' . self::MAX_USERNAME . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $errores['username'] = 'El nombre de usuario solo puede contener letras, números y guiones bajos';
            }
        }
        
        // Validar clave
        if (isset($datos['clave'])) {
            $clave = (string)$datos['clave'];
            if ($clave !== '') {
                if (mb_strlen($clave) < self::MIN_CLAVE) {
                    $errores['clave'] = 'La contraseña debe tener al menos ' . self::MIN_CLAVE . ' caracteres';
                } elseif (!preg_match('/[A-Z]/', $clave)) {
                    $errores['clave'] = 'La contraseña debe incluir al menos una letra mayúscula';
                } elseif (!preg_match('/[a-z]/', $clave)) {
                    $errores['clave'] = 'La contraseña debe incluir al menos una letra minúscula';
                } elseif (!preg_match('/[0-9]/', $clave)) {
                    $errores['clave'] = 'La contraseña debe incluir al menos un número';
                }
            }
        }
        
        // Validar nombre
        if (isset($datos['nombre'])) {
            $nombre = trim((string)$datos['nombre']);
            if ($nombre === '') {
                $errores['nombre'] = 'El nombre es obligatorio';
            } elseif (mb_strlen($nombre) < self::MIN_NOMBRE || mb_strlen($nombre) > self::MAX_NOMBRE) {
                $errores['nombre'] = 'El nombre debe tener entre ' . self::MIN_NOMBRE . ' y ' . self::MAX_NOMBRE . ' caracteres';
            }
        }
        
        // Validar apellido
        if (isset($datos['apellido'])) {
            $apellido = trim((string)$datos['apellido']);
            if ($apellido === '') {
                $errores['apellido'] = 'El apellido es obligatorio';
            } elseif (mb_strlen($apellido) < self::MIN_APELLIDO || mb_strlen($apellido) > self::MAX_APELLIDO) {
                $errores['apellido'] = 'El apellido debe tener entre ' . self::MIN_APELLIDO . ' y ' . self::MAX_APELLIDO . ' caracteres';
            }
        }
        
        // Validar correo
        if (isset($datos['correo'])) {
            $correo = trim((string)$datos['correo']);
            if ($correo === '') {
                $errores['correo'] = 'El correo es obligatorio';
            } elseif (mb_strlen($correo) > self::MAX_CORREO || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $errores['correo'] = 'El correo no es válido';
            }
        }
        
        // Validar teléfono
        if (isset($datos['telefono'])) {
            $telefono = preg_replace('/\D+/', '', (string)$datos['telefono']);
            if ($telefono === '') {
                $errores['telefono'] = 'El teléfono es obligatorio';
            } elseif (strlen($telefono) < self::MIN_TELEFONO || strlen($telefono) > self::MAX_TELEFONO) {
                $errores['telefono'] = 'El teléfono debe tener entre ' . self::MIN_TELEFONO . ' y ' . self::MAX_TELEFONO . ' dígitos';
            }
        }
        
        // Validar cédula
        if (isset($datos['cedula'])) {
            $cedula = preg_replace('/\D+/', '', (string)$datos['cedula']);
            if ($cedula === '') {
                $errores['cedula'] = 'La cédula es obligatoria';
            } elseif (strlen($cedula) < self::MIN_CEDULA || strlen($cedula) > self::MAX_CEDULA) {
                $errores['cedula'] = 'La cédula debe tener entre ' . self::MIN_CEDULA . ' y ' . self::MAX_CEDULA . ' dígitos';
            }
        }
        
        // Validar ID del rol
        if (isset($datos['id_rol'])) {
            $id_rol = (int)$datos['id_rol'];
            if ($id_rol < self::MIN_ID_ROL || $id_rol > self::MAX_ID_ROL) {
                $errores['id_rol'] = 'Debe seleccionar un rol válido';
            }
        }
        
        // Validar estatus
        if (isset($datos['estatus'])) {
            $estatus = trim((string)$datos['estatus']);
            if (!in_array($estatus, self::ESTADOS_VALIDOS)) {
                $errores['estatus'] = 'El estatus no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS);
            }
        }
        
        return $errores;
    }
    
    // Métodos públicos de validación
    public function validarConsultarUsuario($datos) {
        $errores = [];
        
        // Para consultar, podemos validar por ID o sin filtros
        if (isset($datos['id_usuario'])) {
            $id_usuario = (int)$datos['id_usuario'];
            if ($id_usuario < self::MIN_ID_USUARIO || $id_usuario > self::MAX_ID_USUARIO) {
                $errores['id_usuario'] = 'El ID del usuario debe ser un número entre ' . self::MIN_ID_USUARIO . ' y ' . self::MAX_ID_USUARIO;
            }
        }
        
        // Validar estatus si viene
        if (isset($datos['estatus'])) {
            $estatus = trim((string)$datos['estatus']);
            if (!in_array($estatus, self::ESTADOS_VALIDOS) && $estatus !== 'todos') {
                $errores['estatus'] = 'El estatus no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS) . ', todos';
            }
        }
        
        return $errores;
    }
    
    public function validarRegistrarUsuario($datos) {
        $errores = [];
        
        // Para registrar, requerimos campos obligatorios
        $campos_obligatorios = ['username', 'clave', 'nombre', 'apellido', 'correo', 'telefono', 'cedula', 'id_rol'];
        foreach ($campos_obligatorios as $campo) {
            if (!isset($datos[$campo])) {
                $errores[$campo] = 'El campo ' . $campo . ' es obligatorio';
            }
        }
        
        // Validar el usuario completo
        $errores_usuario = $this->validarUsuario($datos);
        if (!empty($errores_usuario)) {
            $errores = array_merge($errores, $errores_usuario);
        }
        
        return $errores;
    }
    
    public function validarModificarUsuario($datos) {
        $errores = [];
        
        // Para modificar, el ID es obligatorio
        if (!isset($datos['id_usuario'])) {
            $errores['id_usuario'] = 'El ID del usuario es obligatorio';
        }
        
        // Para modificar, requerimos campos obligatorios (excepto clave que es opcional)
        $campos_obligatorios = ['username', 'nombre', 'apellido', 'correo', 'telefono', 'cedula', 'id_rol'];
        foreach ($campos_obligatorios as $campo) {
            if (!isset($datos[$campo])) {
                $errores[$campo] = 'El campo ' . $campo . ' es obligatorio';
            }
        }
        
        // Validar el usuario completo
        $errores_usuario = $this->validarUsuario($datos);
        if (!empty($errores_usuario)) {
            $errores = array_merge($errores, $errores_usuario);
        }
        
        return $errores;
    }
    
    public function validarEliminarUsuario($datos) {
        $errores = [];
        
        // Para eliminar, el ID es obligatorio
        if (!isset($datos['id_usuario'])) {
            $errores['id_usuario'] = 'El ID del usuario es obligatorio';
        } else {
            $id_usuario = (int)$datos['id_usuario'];
            if ($id_usuario < self::MIN_ID_USUARIO || $id_usuario > self::MAX_ID_USUARIO) {
                $errores['id_usuario'] = 'El ID del usuario debe ser un número entre ' . self::MIN_ID_USUARIO . ' y ' . self::MAX_ID_USUARIO;
            }
        }
        
        return $errores;
    }
    
    public function validarCambiarEstatus($datos) {
        $errores = [];
        
        // Para cambiar estatus, el ID es obligatorio
        if (!isset($datos['id_usuario'])) {
            $errores['id_usuario'] = 'El ID del usuario es obligatorio';
        } else {
            $id_usuario = (int)$datos['id_usuario'];
            if ($id_usuario < self::MIN_ID_USUARIO || $id_usuario > self::MAX_ID_USUARIO) {
                $errores['id_usuario'] = 'El ID del usuario debe ser un número entre ' . self::MIN_ID_USUARIO . ' y ' . self::MAX_ID_USUARIO;
            }
        }
        
        // Validar estatus
        if (!isset($datos['estatus'])) {
            $errores['estatus'] = 'El estatus es obligatorio';
        } else {
            $estatus = trim((string)$datos['estatus']);
            if (!in_array($estatus, self::ESTADOS_VALIDOS)) {
                $errores['estatus'] = 'El estatus no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS);
            }
        }
        
        return $errores;
    }
    
    public function validarReporte($datos) {
        $errores = [];
        
        // Para reporte, validar parámetros opcionales
        if (isset($datos['estatus'])) {
            $estatus = trim((string)$datos['estatus']);
            if (!in_array($estatus, self::ESTADOS_VALIDOS) && $estatus !== 'todos') {
                $errores['estatus'] = 'El estatus no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS) . ', todos';
            }
        }
        
        return $errores;
    }
    
    public function validarDescarga($datos) {
        $errores = [];
        
        // Para descarga, validar tipo de descarga
        if (isset($datos['tipo_descarga'])) {
            $tipos_validos = ['pdf', 'excel', 'csv'];
            if (!in_array($datos['tipo_descarga'], $tipos_validos)) {
                $errores['tipo_descarga'] = 'El tipo de descarga no es válido. Tipos permitidos: ' . implode(', ', $tipos_validos);
            }
        }
        
        // Validar parámetros adicionales según el tipo
        if (isset($datos['parametros']) && is_array($datos['parametros'])) {
            foreach ($datos['parametros'] as $parametro => $valor) {
                if (is_string($valor) && mb_strlen($valor) > 100) {
                    $errores[$parametro] = 'El parámetro ' . $parametro . ' es demasiado largo';
                }
            }
        }
        
        return $errores;
    }
    
    // Métodos auxiliares
    private function verificarUsuarioExistente($id_usuario) {
        $conexion = null;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('S');
            $this->conex = $conexion->getConexion();
        }
        try {
            $stmt = $this->conex->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        } finally {
            if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        }
    }
    
    private function verificarRolExistente($id_rol) {
        $conexion = null;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('S');
            $this->conex = $conexion->getConexion();
        }
        try {
            $stmt = $this->conex->prepare("SELECT COUNT(*) FROM tbl_rol WHERE id_rol = ?");
            $stmt->execute([$id_rol]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        } finally {
            if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        }
    }

    public function verificarCampoEstatus() {
        $this->v_CampoEstatus();
    }
    private function v_CampoEstatus() {
        $conexion = new BD('S');
        $pdo = $conexion->getConexion();
        try {
            $sql = "SHOW COLUMNS FROM tbl_usuarios LIKE 'estatus'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $alterSql = "ALTER TABLE tbl_usuarios 
                             ADD estatus ENUM('habilitado','deshabilitado') NOT NULL DEFAULT 'habilitado'";
                $pdo->exec($alterSql);
            }
        } finally {
            $conexion->cerrar();
        }
    }

    public function clienteExiste($cedula) {
        return $this->c_clienteExiste($cedula);
    }
    private function c_clienteExiste($cedula) {
        $conexion = new BD('P');
        $pdo = $conexion->getConexion();
        try {
            $sql = "SELECT COUNT(*) FROM tbl_clientes WHERE cedula = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cedula]);
            return $stmt->fetchColumn() > 0;
        } finally {
            $conexion->cerrar();
        }
    }

    public function ingresarUsuario() {
        return $this->i_ingresarUsuario();
    }
    private function i_ingresarUsuario() {
        // Usar stubs inyectados si existen; en caso contrario crear conexiones reales
        $createdS = false; $createdP = false;
        if ($this->conex instanceof PDO) {
            $pdoS = $this->conex;
            $conexionS = null;
        } else {
            $conexionS = new BD('S');
            $pdoS = $conexionS->getConexion();
            $createdS = true;
        }
        if ($this->con instanceof PDO) {
            $pdoP = $this->con;
            $conexionP = null;
        } else {
            $conexionP = new BD('P');
            $pdoP = $conexionP->getConexion();
            $createdP = true;
        }
        try {
            $pdoS->beginTransaction();
            $claveEncriptada = password_hash($this->clave, PASSWORD_BCRYPT);

            $sql = "INSERT INTO tbl_usuarios (username, password, id_rol, correo, nombres, apellidos, telefono, cedula)
                    VALUES (:username, :clave, :id_rol, :correo, :nombres, :apellidos, :telefono, :cedula)";
            $stmt = $pdoS->prepare($sql);
            $stmt->bindParam(':username', $this->username);
            $stmt->bindParam(':clave', $claveEncriptada);
            $stmt->bindParam(':id_rol', $this->id_rol);
            $stmt->bindParam(':correo', $this->correo);
            $stmt->bindParam(':nombres', $this->nombre);
            $stmt->bindParam(':apellidos', $this->apellido);
            $stmt->bindParam(':telefono', $this->telefono);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->execute();

            if (!$this->clienteExiste($this->cedula)) {
                $sqlCliente = "INSERT INTO tbl_clientes (nombre, cedula, telefono, direccion, correo, activo)
                               VALUES (:nombre, :cedula, :telefono, '', :correo, 1)";
                $stmtCliente = $pdoP->prepare($sqlCliente);
                $nombreCompleto = $this->nombre . ' ' . $this->apellido;
                $stmtCliente->bindParam(':nombre', $nombreCompleto);
                $stmtCliente->bindParam(':cedula', $this->cedula);
                $stmtCliente->bindParam(':telefono', $this->telefono);
                $stmtCliente->bindParam(':correo', $this->correo);
                $stmtCliente->execute();
            }

            $pdoS->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdoS->inTransaction()) { $pdoS->rollBack(); }
            return false;
        } finally {
            if ($createdS && isset($conexionS)) { $conexionS->cerrar(); }
            if ($createdP && isset($conexionP)) { $conexionP->cerrar(); }
        }
    }

    public function modificarUsuario($id_usuario) {
        return $this->m_modificarUsuario($id_usuario);
    }
    private function m_modificarUsuario($id_usuario) {
        $createdS = false; $createdP = false;
        if ($this->conex instanceof PDO) {
            $pdoS = $this->conex; $conexionS = null;
        } else {
            $conexionS = new BD('S'); $pdoS = $conexionS->getConexion(); $createdS = true;
        }
        if ($this->con instanceof PDO) {
            $pdoP = $this->con; $conexionP = null;
        } else {
            $conexionP = new BD('P'); $pdoP = $conexionP->getConexion(); $createdP = true;
        }
        try {
            $pdoS->beginTransaction();
            $claveEncriptada = !empty($this->clave) ? password_hash($this->clave, PASSWORD_BCRYPT) : null;

            $sql = "UPDATE tbl_usuarios SET 
                        username = :username, 
                        id_rol = :id_rol,
                        nombres = :nombre,
                        apellidos = :apellido,
                        correo = :correo,
                        telefono = :telefono,
                        cedula = :cedula";
            if (!empty($this->clave)) {
                $sql .= ", password = :clave";
            }
            $sql .= " WHERE id_usuario = :id_usuario";

            $stmt = $pdoS->prepare($sql);
            $stmt->bindParam(':username', $this->username);
            $stmt->bindParam(':id_rol', $this->id_rol);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':apellido', $this->apellido);
            $stmt->bindParam(':correo', $this->correo);
            $stmt->bindParam(':telefono', $this->telefono);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->bindParam(':id_usuario', $id_usuario);
            if (!empty($this->clave)) {
                $stmt->bindParam(':clave', $claveEncriptada);
            }
            $stmt->execute();

            if ($this->clienteExiste($this->cedula)) {
                $sqlCliente = "UPDATE tbl_clientes SET 
                                nombre = :nombre,
                                telefono = :telefono,
                                correo = :correo
                                WHERE cedula = :cedula";
                $stmtCliente = $pdoP->prepare($sqlCliente);
                $nombreCompleto = $this->nombre . ' ' . $this->apellido;
                $stmtCliente->bindParam(':nombre', $nombreCompleto);
                $stmtCliente->bindParam(':telefono', $this->telefono);
                $stmtCliente->bindParam(':correo', $this->correo);
                $stmtCliente->bindParam(':cedula', $this->cedula);
                $stmtCliente->execute();
            }

            $pdoS->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdoS->inTransaction()) { $pdoS->rollBack(); }
            return false;
        } finally {
            if (isset($createdS) && $createdS && isset($conexionS)) { $conexionS->cerrar(); }
            if (isset($createdP) && $createdP && isset($conexionP)) { $conexionP->cerrar(); }
        }
    }

    public function existeUsuario($username, $excluir_id = null) {
        return $this->e_existeUsuario($username, $excluir_id);
    }
    private function e_existeUsuario($username, $excluir_id = null) {
        // Usar conexión inyectada en pruebas si existe, si no crear una nueva
        if ($this->conex instanceof PDO) {
            $pdo = $this->conex;
            $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE username = ?";
            $params = [$username];
            if ($excluir_id !== null) {
                $sql .= " AND id_usuario != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } else {
            $conexion = new BD('S');
            $pdo = $conexion->getConexion();
            try {
                $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE username = ?";
                $params = [$username];
                if ($excluir_id !== null) {
                    $sql .= " AND id_usuario != ?";
                    $params[] = $excluir_id;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchColumn() > 0;
            } finally {
                $conexion->cerrar();
            }
        }
    }
    public function existeCedula($cedula, $excluir_id = null) {
        return $this->e_existeCedula($cedula, $excluir_id);
    }
    private function e_existeCedula($cedula, $excluir_id = null) {
        if ($this->conex instanceof PDO) {
            $pdo = $this->conex;
            $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE cedula = ?";
            $params = [$cedula];
            if ($excluir_id !== null) {
                $sql .= " AND id_usuario != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } else {
            $conexion = new BD('S');
            $pdo = $conexion->getConexion();
            try {
                $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE cedula = ?";
                $params = [$cedula];
                if ($excluir_id !== null) {
                    $sql .= " AND id_usuario != ?";
                    $params[] = $excluir_id;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchColumn() > 0;
            } finally {
                $conexion->cerrar();
            }
        }
    }
    public function existeCorreo($correo, $excluir_id = null) {
        return $this->e_existeCorreo($correo, $excluir_id);
    }
    private function e_existeCorreo($correo, $excluir_id = null) {
        if ($this->conex instanceof PDO) {
            $pdo = $this->conex;
            $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE correo = ?";
            $params = [$correo];
            if ($excluir_id !== null) {
                $sql .= " AND id_usuario != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } else {
            $conexion = new BD('S');
            $pdo = $conexion->getConexion();
            try {
                $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE correo = ?";
                $params = [$correo];
                if ($excluir_id !== null) {
                    $sql .= " AND id_usuario != ?";
                    $params[] = $excluir_id;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchColumn() > 0;
            } finally {
                $conexion->cerrar();
            }
        }
    }

    public function obtenerUltimoUsuario() {
        return $this->o_ultimoUsuario();
    }
    private function o_ultimoUsuario() {
        if ($this->conex instanceof PDO) {
            $pdo = $this->conex;
            $sql = "SELECT usuarios.*, rol.nombre_rol 
                    FROM tbl_usuarios AS usuarios
                    INNER JOIN tbl_rol AS rol ON usuarios.id_rol = rol.id_rol
                    ORDER BY usuarios.id_usuario DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            return $usuario ? $usuario : null;
        } else {
            $conexion = new BD('S');
            $pdo = $conexion->getConexion();
            try {
                $sql = "SELECT usuarios.*, rol.nombre_rol 
                        FROM tbl_usuarios AS usuarios
                        INNER JOIN tbl_rol AS rol ON usuarios.id_rol = rol.id_rol
                        ORDER BY usuarios.id_usuario DESC LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                return $usuario ? $usuario : null;
            } catch (PDOException $e) {
                return null;
            } finally {
                $conexion->cerrar();
            }
        }
    }

    public function obtenerUsuarioPorId($id_usuario) {
        return $this->o_usuarioPorId($id_usuario);
    }
    private function o_usuarioPorId($id_usuario) {
        $conexion = new BD('S');
        $pdo = $conexion->getConexion();
        try {
            $query = "SELECT usuarios.*, rol.nombre_rol 
                      FROM tbl_usuarios AS usuarios
                      INNER JOIN tbl_rol AS rol ON usuarios.id_rol = rol.id_rol
                      WHERE usuarios.id_usuario = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$id_usuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } finally {
            $conexion->cerrar();
        }
    }

    public function eliminarUsuario($id_usuario) {
        return $this->d_eliminarUsuario($id_usuario);
    }
    private function d_eliminarUsuario($id_usuario) {
        $conexion = new BD('S');
        $pdo = $conexion->getConexion();
        try {
            $sql = "DELETE FROM tbl_usuarios WHERE id_usuario = :id_usuario";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_usuario', $id_usuario);
            return $stmt->execute();
        } finally {
            $conexion->cerrar();
        }
    }

    public function cambiarEstatus($nuevoEstatus) {
        return $this->c_cambiarEstatus($nuevoEstatus);
    }
    private function c_cambiarEstatus($nuevoEstatus) {
        $conexion = new BD('S');
        $pdo = $conexion->getConexion();
        try {
            $sql = "UPDATE tbl_usuarios SET estatus = :estatus WHERE id_usuario = :id_usuario";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':estatus', $nuevoEstatus);
            $stmt->bindParam(':id_usuario', $this->id_usuario);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        } finally {
            $conexion->cerrar();
        }
    }

    public function obtenerReporteRoles() {
        return $this->r_reporteRoles();
    }
    private function r_reporteRoles() {
        $conexion = new BD('S');
        $pdo = $conexion->getConexion();
        try {
            $sql = "SELECT rol.nombre_rol, COUNT(u.id_usuario) as cantidad
                    FROM tbl_rol rol
                    LEFT JOIN tbl_usuarios u ON rol.id_rol = u.id_rol
                    GROUP BY rol.id_rol, rol.nombre_rol
                    ORDER BY cantidad DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            $conexion->cerrar();
        }
    }

    public function actualizarPerfil($id_usuario, $datos) {
        return $this->a_actualizarPerfil($id_usuario, $datos);
    }
    private function a_actualizarPerfil($id_usuario, $datos) {
        // Usar conexión inyectada (doble de pruebas) si existe; de lo contrario crear una BD real
        $conexion = null;
        $created = false;
        if ($this->conex instanceof PDO) {
            $pdo = $this->conex;
        } else {
            $conexion = new BD('S');
            $pdo = $conexion->getConexion();
            $created = true;
        }
        try {
            $pdo->beginTransaction();
            $sql = "UPDATE tbl_usuarios SET ";
            $params = [':id_usuario' => $id_usuario];
            $updates = [];
            
            foreach ($datos as $campo => $valor) {
                if ($valor !== '' || $campo === 'password') {
                    $updates[] = "$campo = :$campo";
                    $params[":$campo"] = $campo === 'password' ? password_hash($valor, PASSWORD_BCRYPT) : $valor;
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $sql .= implode(", ", $updates) . " WHERE id_usuario = :id_usuario";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log("Error al actualizar perfil: " . $e->getMessage());
            return false;
        } finally {
            if ($created && $conexion !== null) {
                $conexion->cerrar();
            }
        }
    }

    public function getusuarios($estatus = 'habilitado') {
        return $this->g_getusuarios($estatus);
    }
    private function g_getusuarios($estatus = 'habilitado') {
        $conexion = new BD('S');
        $pdo = $conexion->getConexion();
        try {
            $queryusuarios = "SELECT usuarios.*, rol.nombre_rol 
                              FROM tbl_usuarios AS usuarios
                              INNER JOIN tbl_rol AS rol ON usuarios.id_rol = rol.id_rol";
            
            if ($estatus !== 'todos') {
                $queryusuarios .= " WHERE usuarios.estatus = :estatus";
            }
            
            $queryusuarios .= " ORDER BY usuarios.id_usuario DESC";
            $stmtusuarios = $pdo->prepare($queryusuarios);
            
            if ($estatus !== 'todos') {
                $stmtusuarios->bindParam(':estatus', $estatus);
            }
            
            $stmtusuarios->execute();
            return $stmtusuarios->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            $conexion->cerrar();
        }
    }
}

