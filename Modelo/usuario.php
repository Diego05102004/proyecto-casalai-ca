<?php 
require_once __DIR__ . '/../Config/config.php';

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
        $this->c_clienteExiste($cedula);
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
        $conexionS = new BD('S');
        $pdoS = $conexionS->getConexion();
        $conexionP = new BD('P');
        $pdoP = $conexionP->getConexion();
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
            $conexionS->cerrar();
            $conexionP->cerrar();
        }
    }

    public function modificarUsuario($id_usuario) {
        return $this->m_modificarUsuario($id_usuario);
    }
    private function m_modificarUsuario($id_usuario) {
        $conexionS = new BD('S');
        $pdoS = $conexionS->getConexion();
        $conexionP = new BD('P');
        $pdoP = $conexionP->getConexion();
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
            $conexionS->cerrar();
            $conexionP->cerrar();
        }
    }

    public function existeUsuario($username, $excluir_id = null) {
        return $this->e_existeUsuario($username, $excluir_id);
    }
    private function e_existeUsuario($username, $excluir_id = null) {
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
    public function existeCedula($cedula, $excluir_id = null) {
        return $this->e_existeCedula($cedula, $excluir_id);
    }
    private function e_existeCedula($cedula, $excluir_id = null) {
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
    public function existeCorreo($correo, $excluir_id = null) {
        return $this->e_existeCorreo($correo, $excluir_id);
    }
    private function e_existeCorreo($correo, $excluir_id = null) {
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

    public function obtenerUltimoUsuario() {
        return $this->o_ultimoUsuario();
    }
    private function o_ultimoUsuario() {
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
        $conexion = new BD('S');
        $pdo = $conexion->getConexion();
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
            $conexion->cerrar();
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
                              INNER JOIN tbl_rol AS rol ON usuarios.id_rol = rol.id_rol
                              WHERE usuarios.estatus = :estatus
                              ORDER BY usuarios.id_usuario DESC";
            $stmtusuarios = $pdo->prepare($queryusuarios);
            $stmtusuarios->bindParam(':estatus', $estatus);
            $stmtusuarios->execute();
            return $stmtusuarios->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            $conexion->cerrar();
        }
    }
}
