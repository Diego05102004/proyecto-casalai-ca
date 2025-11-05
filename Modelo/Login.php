<?php

require_once('Config/Config.php');

class Login extends BD {
    private $username;
    private $password;
    private $co;
    private $cop;

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
        $conexion = new BD('S');
        $co = $conexion->getConexion();
    
    try {
            $p = $co->prepare("SELECT 
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
        } finally {
            $co = null;
            $conexion = null;
    }
    
    return $r;
}

public function solicitarRecuperacion($email) {
        return $this->sr_procesar_solicitud($email);
    }

    private function sr_procesar_solicitud($email) {
        $conexion = new BD('S');
        $co = $conexion->getConexion();
        
        try {
    // Verificar si el email existe
            $p = $co->prepare("SELECT id_usuario FROM tbl_usuarios WHERE correo = ?");
    $p->execute([$email]);
    $usuario = $p->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        return ['status' => 'error', 'mensaje' => 'El correo no está registrado'];
    }
    
    // Generar token único
    $token = bin2hex(random_bytes(32));
    $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour')); 
    
    // Eliminar solicitudes previas
            $co->prepare("DELETE FROM tbl_recuperar WHERE id_usuario = ?")->execute([$usuario['id_usuario']]);
    
    // Insertar nueva solicitud
            $p = $co->prepare("INSERT INTO tbl_recuperar (id_usuario, token, expiracion, fecha, hora) 
                            VALUES (?, ?, ?, CURDATE(), CURTIME())");
    $p->execute([$usuario['id_usuario'], $token, $expiracion]);
    
    return [
        'status' => 'success', 
        'token' => $token,
        'id_usuario' => $usuario['id_usuario']
    ];
        } catch (Exception $e) {
            return ['status' => 'error', 'mensaje' => $e->getMessage()];
        } finally {
            $co = null;
            $conexion = null;
        }
}

public function validarToken($id_usuario, $token) {
        return $this->vt_verificar($id_usuario, $token);
    }

    private function vt_verificar($id_usuario, $token) {
        $conexion = new BD('S');
        $co = $conexion->getConexion();
        
        try {
            $p = $co->prepare("SELECT * FROM tbl_recuperar 
                            WHERE id_usuario = ? AND token = ? AND utilizado = 0 
                            AND expiracion > NOW()");
    $p->execute([$id_usuario, $token]);
    return $p->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        } finally {
            $co = null;
            $conexion = null;
        }
}

public function actualizarPassword($id_usuario, $nueva_password) {
        return $this->ap_actualizar($id_usuario, $nueva_password);
    }

    private function ap_actualizar($id_usuario, $nueva_password) {
        $conexion = new BD('S');
        $co = $conexion->getConexion();
        
    $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    
        $co->beginTransaction();
    try {
        // Actualizar contraseña
            $p = $co->prepare("UPDATE tbl_usuarios SET password = ? WHERE id_usuario = ?");
        $p->execute([$hash, $id_usuario]);
        
        // Marcar token como utilizado
            $p = $co->prepare("UPDATE tbl_recuperar SET utilizado = 1 WHERE id_usuario = ?");
        $p->execute([$id_usuario]);
        
            $co->commit();
        return true;
    } catch (Exception $e) {
            $co->rollBack();
        return false;
        } finally {
            $co = null;
            $conexion = null;
    }
}



public function registrarUsuarioYCliente($datos) {
        return $this->ruc_registrar($datos);
    }

    private function ruc_registrar($datos) {
    $respuesta = ['status' => 'error', 'mensaje' => ''];

        // Conexión a la base de datos de sistema
        $conexion_s = new BD('S');
        $co = $conexion_s->getConexion();
        
        // Conexión a la base de datos de producción
        $conexion_p = new BD('P');
        $cop = $conexion_p->getConexion();

    try {
            // Iniciar transacciones
            $co->beginTransaction();
            $cop->beginTransaction();

        // Verifica si el usuario ya existe
            $p = $co->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE username = ?");
        $p->execute([$datos['nombre_usuario']]);
        if ($p->fetchColumn() > 0) {
            throw new Exception("El nombre de usuario ya está en uso. Por favor elige otro.");
        }

        // Hashea la contraseña
        $hash = password_hash($datos['clave'], PASSWORD_DEFAULT);
            $id_rol_cliente = 3; // ID del rol Cliente

        // Inserta en tbl_usuarios
            $p = $co->prepare("INSERT INTO tbl_usuarios 
                            (username, password, cedula, nombres, apellidos, correo, telefono, id_rol, estatus)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'habilitado')");
        $p->execute([
            $datos['nombre_usuario'],
            $hash,
            $datos['cedula'],
            $datos['nombre'],
            $datos['apellido'],
            $datos['correo'],
            $datos['telefono'],
                $id_rol_cliente
        ]);

        // Inserta en tbl_clientes
            $p = $cop->prepare("INSERT INTO tbl_clientes 
                            (nombre, cedula, telefono, direccion, correo, activo)
                            VALUES (?, ?, ?, ?, ?, ?)");
        $p->execute([
            $datos['nombre'] . ' ' . $datos['apellido'],
            $datos['cedula'],
            $datos['telefono'],
            $datos['direccion'],
            $datos['correo'],
            1
        ]);

            // Confirmar transacciones
            $co->commit();
            $cop->commit();

        $respuesta['status'] = 'success';
        $respuesta['mensaje'] = 'Usuario y cliente registrados correctamente.';
    } catch (Exception $e) {
            // Revertir transacciones en caso de error
            if (isset($co) && $co->inTransaction()) {
                $co->rollBack();
            }
            if (isset($cop) && $cop->inTransaction()) {
                $cop->rollBack();
        }
        $respuesta['mensaje'] = $e->getMessage();
        } finally {
            // Cerrar conexiones
            $co = null;
            $cop = null;
            $conexion_s = null;
            $conexion_p = null;
        }
        
    return $respuesta;
}
}
