<?php

require_once('config/config.php');

class Login extends BD
{


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

    public function __construct() {
        $conexion = new BD('S');
        $this->co = $conexion->getConexion();
    
        $conexion2 = new BD('P');
        $this->cop = $conexion2->getConexion();
    }

    function existe() {
    
    $this->co->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $r = array();
    
    try {
        // Consultar el hash de la contraseña almacenada
        $p = $this->co->prepare("SELECT 
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
        $p->bindParam(':username', $this->username);
        $p->execute();

        $fila = $p->fetch(PDO::FETCH_ASSOC); // Usar fetch() en lugar de fetchAll()

        if ($fila) {
            // Verificar la contraseña ingresada contra el hash almacenado
            if (password_verify($this->password, $fila['password'])) {
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
    // Verificar si el email existe
    $p = $this->co->prepare("SELECT id_usuario FROM tbl_usuarios WHERE correo = ?");
    $p->execute([$email]);
    $usuario = $p->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        return ['status' => 'error', 'mensaje' => 'El correo no está registrado'];
    }
    
    // Generar token único
    $token = bin2hex(random_bytes(32));
    $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour')); 
    
    // Eliminar solicitudes previas
    $this->co->prepare("DELETE FROM tbl_recuperar WHERE id_usuario = ?")->execute([$usuario['id_usuario']]);
    
    // Insertar nueva solicitud
    $p = $this->co->prepare("INSERT INTO tbl_recuperar (id_usuario, token, expiracion, fecha, hora) 
                            VALUES (?, ?, ?, CURDATE(), CURTIME())");
    $p->execute([$usuario['id_usuario'], $token, $expiracion]);
    
    return [
        'status' => 'success', 
        'token' => $token,
        'id_usuario' => $usuario['id_usuario']
    ];
}

public function validarToken($id_usuario, $token) {
    $p = $this->co->prepare("SELECT * FROM tbl_recuperar 
                            WHERE id_usuario = ? AND token = ? AND utilizado = 0 
                            AND expiracion > NOW()");
    $p->execute([$id_usuario, $token]);
    return $p->fetch(PDO::FETCH_ASSOC);
}

public function actualizarPassword($id_usuario, $nueva_password) {
    $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    
    $this->co->beginTransaction();
    try {
        // Actualizar contraseña
        $p = $this->co->prepare("UPDATE tbl_usuarios SET password = ? WHERE id_usuario = ?");
        $p->execute([$hash, $id_usuario]);
        
        // Marcar token como utilizado
        $p = $this->co->prepare("UPDATE tbl_recuperar SET utilizado = 1 WHERE id_usuario = ?");
        $p->execute([$id_usuario]);
        
        $this->co->commit();
        return true;
    } catch (Exception $e) {
        $this->co->rollBack();
        return false;
    }
}



public function registrarUsuarioYCliente($datos) {
    $this->co->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $respuesta = ['status' => 'error', 'mensaje' => ''];

    try {
        // Iniciar transacción
        $this->co->beginTransaction();

        // Verifica si el usuario ya existe
        $p = $this->co->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE username = ?");
        $p->execute([$datos['nombre_usuario']]);
        if ($p->fetchColumn() > 0) {
            throw new Exception("El nombre de usuario ya está en uso. Por favor elige otro.");
        }

        // Hashea la contraseña
        $hash = password_hash($datos['clave'], PASSWORD_DEFAULT);

        // ID del rol Cliente (3 REVISAR LA BASE DE DATOS)
        $id_rol_cliente = 3;

        // Inserta en tbl_usuarios
        $p = $this->co->prepare("INSERT INTO tbl_usuarios 
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
            $id_rol_cliente // Usamos el ID numérico del rol Cliente
        ]);

        // Inserta en tbl_clientes
        $p = $this->cop->prepare("INSERT INTO tbl_clientes 
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

        // Confirmar transacción
        $this->co->commit();

        $respuesta['status'] = 'success';
        $respuesta['mensaje'] = 'Usuario y cliente registrados correctamente.';
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        if ($this->co->inTransaction()) {
            $this->co->rollBack();
        }
        $respuesta['mensaje'] = $e->getMessage();
    }
    return $respuesta;
}
}
