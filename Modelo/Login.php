<?php

require_once('Config/Config.php');

class Login extends BD {
    private $username;
    private $password;
    private $pdo;
    private $pdoProd;

    public function __construct($pdo = null, $pdoProd = null) {
        if ($pdo !== null) {
            $this->pdo = $pdo;
            $this->pdoProd = $pdoProd;
        } else {
            parent::__construct('S'); // 'S' para la base de datos de seguridad
            $this->pdo = $this->getConexion();
            
            // Si necesitas la conexión de producción, inicialízala aquí
            if ($pdoProd === null) {
                $prodConnection = new BD('P');
                $this->pdoProd = $prodConnection->getConexion();
            } else {
                $this->pdoProd = $pdoProd;
            }
        }
    }

    function set_username($valor)
    {
        $this->username = $valor;
    }

    function set_password($valor)
    {
        $this->password = $valor;
    }


    function get_username()
    {
        return $this->username;
    }

    function get_password()
    {
        return $this->password;
    }

    public function existe() {
        return $this->e_verificar_usuario($this->username, $this->password);
    }

    private function e_verificar_usuario($username, $password) {
        $r = array();
        
        try {
            $p = $this->pdo->prepare("SELECT 
    u.id_usuario, 
    u.id_rol,
    r.nombre_rol, 
    u.username, 
    u.password,
    u.cedula
FROM 
    tbl_usuarios u 
INNER JOIN 
    tbl_rol r 
ON 
    r.id_rol = u.id_rol
WHERE username = :username");
            $p->bindParam(':username', $username);
            $p->execute();

            $fila = $p->fetch(PDO::FETCH_ASSOC);

            if ($fila) {
                if (password_verify($password, $fila['password'])) {
                    $r['resultado'] = 'existe';
                    $r['mensaje'] = $fila['username'];
                    $r['nombre_rol'] = $fila['nombre_rol'];
                    $r['id_usuario'] = $fila['id_usuario']; 
                    $r['id_rol'] = $fila['id_rol']; 
                    $r['cedula'] = $fila['cedula'];
                } else {
                    $r['resultado'] = 'noexiste';
                    $r['mensaje'] = "Error en usuario o contraseña!!!";
                }
            } else {
                $r['resultado'] = 'noexiste';
                $r['mensaje'] = "Error en usuario o contraseña!!!";
            }
        } catch (Exception $e) {
            $r['resultado'] = 'error';
            $r['mensaje'] = $e->getMessage();
        }
    
    return $r;
}

    public function solicitarRecuperacion($email) {
        return $this->sr_procesar_solicitud($email);
    }

    private function sr_procesar_solicitud($email) {
        try {
            // Verificar si el email existe
            $p = $this->pdo->prepare("SELECT id_usuario FROM tbl_usuarios WHERE correo = ?");
            $p->execute([$email]);
            $usuario = $p->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                return ['status' => 'error', 'mensaje' => 'El correo no está registrado'];
            }
            
            // Generar token único
            $token = bin2hex(random_bytes(32));
            $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour')); 
            
            // Iniciar transacción
            $this->pdo->beginTransaction();
            
            try {
                // Eliminar solicitudes previas
                $this->pdo->prepare("DELETE FROM tbl_recuperar WHERE id_usuario = ?")
                    ->execute([$usuario['id_usuario']]);
                
                // Insertar nueva solicitud
                $p = $this->pdo->prepare("INSERT INTO tbl_recuperar (id_usuario, token, expiracion, fecha, hora) 
                                      VALUES (?, ?, ?, CURDATE(), CURTIME())");
                $p->execute([$usuario['id_usuario'], $token, $expiracion]);
                
                $this->pdo->commit();
                
                return [
                    'status' => 'success', 
                    'token' => $token,
                    'id_usuario' => $usuario['id_usuario']
                ];
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'mensaje' => $e->getMessage()];
        }
}

    public function validarToken($id_usuario, $token) {
        return $this->vt_verificar($id_usuario, $token);
    }

    private function vt_verificar($id_usuario, $token) {
        try {
            $p = $this->pdo->prepare("SELECT * FROM tbl_recuperar 
                                   WHERE id_usuario = ? AND token = ? AND utilizado = 0 
                                   AND expiracion > NOW()");
            $p->execute([$id_usuario, $token]);
            return $p->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
}

    public function actualizarPassword($id_usuario, $nueva_password) {
        return $this->ap_actualizar($id_usuario, $nueva_password);
    }

    private function ap_actualizar($id_usuario, $nueva_password) {
        $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        
        try {
            $this->pdo->beginTransaction();
            
            // Actualizar contraseña
            $p = $this->pdo->prepare("UPDATE tbl_usuarios SET password = ? WHERE id_usuario = ?");
            $p->execute([$hash, $id_usuario]);
            
            // Marcar token como utilizado
            $p = $this->pdo->prepare("UPDATE tbl_recuperar SET utilizado = 1 WHERE id_usuario = ?");
            $p->execute([$id_usuario]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
}



public function registrarUsuarioYCliente($datos) {
        return $this->ruc_registrar($datos);
    }

    private function ruc_registrar($datos) {
        $respuesta = ['status' => 'error', 'mensaje' => ''];

        try {
            // Iniciar transacción en ambas bases de datos
            $this->pdo->beginTransaction();
            $this->pdoProd->beginTransaction();

            // 1. Verificar si el username ya existe
            $p = $this->pdo->prepare("SELECT id_usuario FROM tbl_usuarios WHERE username = ?");
            $p->execute([$datos['username']]);
            if ($p->fetch()) {
                $respuesta['mensaje'] = 'El nombre de usuario ya está en uso';
                return $respuesta;
            }

            // 2. Insertar en tbl_usuarios
            $p = $this->pdo->prepare("INSERT INTO tbl_usuarios 
                (username, password, id_rol, cedula, correo, telefono, direccion, estado) 
                VALUES (?, ?, 2, ?, ?, ?, ?, 1)");
            
            $password_hash = password_hash($datos['password'], PASSWORD_DEFAULT);
            $p->execute([
                $datos['username'],
                $password_hash,
                $datos['cedula'],
                $datos['correo'],
                $datos['telefono'],
                $datos['direccion']
            ]);

            $id_usuario = $this->pdo->lastInsertId();

            // 3. Insertar en tbl_clientes (base de producción)
            $p = $this->pdoProd->prepare("INSERT INTO tbl_clientes 
                (cedula, nombre, apellido, telefono, direccion, correo, estado) 
                VALUES (?, ?, ?, ?, ?, ?, 1)");
            
            $p->execute([
                $datos['cedula'],
                $datos['nombre'],
                $datos['apellido'],
                $datos['telefono'],
                $datos['direccion'],
                $datos['correo']
            ]);

            // Si todo sale bien, confirmar transacciones
            $this->pdo->commit();
            $this->pdoProd->commit();

            $respuesta['status'] = 'success';
            $respuesta['mensaje'] = 'Usuario registrado exitosamente';
            $respuesta['id_usuario'] = $id_usuario;

        } catch (Exception $e) {
            // Si hay error, deshacer transacciones
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($this->pdoProd->inTransaction()) {
                $this->pdoProd->rollBack();
            }
            $respuesta['mensaje'] = 'Error al registrar: ' . $e->getMessage();
        }

        return $respuesta;
    }
}
