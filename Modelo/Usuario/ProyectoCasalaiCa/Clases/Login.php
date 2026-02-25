<?php

declare(strict_types=1);

namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;

use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
use RuntimeException;
/**
 * Clase para manejar la autenticación de usuarios
 */
class Login extends BD
{
    private ?string $username = null;
    private ?string $password = null;
    private ?PDO $co = null;
    private ?PDO $cop = null;
    
    // Constantes para validaciones
    const MAX_USERNAME = 50;
    const MIN_USERNAME = 3;
    const MAX_PASSWORD = 255;
    const MIN_PASSWORD = 8;
    const MAX_EMAIL = 100;
    const MAX_NOMBRE = 50;
    const MAX_APELLIDO = 50;
    const MAX_TELEFONO = 20;
    const MAX_CEDULA = 20;
    const MIN_CEDULA = 5;
    const MAX_DIRECCION = 200;

    public function __construct()
    {
        try {
            $conexion = new BD('S');
            $this->co = $conexion->getConexion();
            
            $conexion2 = new BD('P');
            $this->cop = $conexion2->getConexion();
        } catch (PDOException $e) {
            throw new RuntimeException('Error al conectar con la base de datos: ' . $e->getMessage());
        }
    }

    public function __destruct()
    {
        $this->co = null;
        $this->cop = null;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }


    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
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
    u.cedula,
    u.foto_perfil
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
                $r['foto_perfil'] = $fila['foto_perfil'];
            } else {
                $r['resultado'] = 'noexiste';
                $r['mensaje'] = "Error en usuario o contraseña!!!";
            }
        } else {
            $r['resultado'] = 'noexiste';
            $r['mensaje'] = "Error en usuario o contraseña!!!";
        }
    } catch (PDOException $e) {
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
    } catch (PDOException $e) {
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
            throw new PDOException("El nombre de usuario ya está en uso. Por favor elige otro.");
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
    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        if ($this->co->inTransaction()) {
            $this->co->rollBack();
        }
        $respuesta['mensaje'] = $e->getMessage();
    }
    return $respuesta;
}
    
    // ==================== VALIDACIONES DE BACKEND ====================
    
    /**
     * Valida los datos para iniciar sesión
     */
    private function validarInicioSesion($datos) {
        $errores = [];
        
        // Validar username
        if (!isset($datos['username'])) {
            $errores['username'] = 'El nombre de usuario es obligatorio';
        } else {
            $username = trim($datos['username']);
            if (empty($username)) {
                $errores['username'] = 'El nombre de usuario es obligatorio';
            } elseif (mb_strlen($username) < self::MIN_USERNAME || mb_strlen($username) > self::MAX_USERNAME) {
                $errores['username'] = 'El nombre de usuario debe tener entre ' . self::MIN_USERNAME . ' y ' . self::MAX_USERNAME . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $errores['username'] = 'El nombre de usuario solo puede contener letras, números y guiones bajos';
            }
        }
        
        // Validar password
        if (!isset($datos['password'])) {
            $errores['password'] = 'La contraseña es obligatoria';
        } else {
            $password = $datos['password'];
            if (empty($password)) {
                $errores['password'] = 'La contraseña es obligatoria';
            } elseif (mb_strlen($password) < self::MIN_PASSWORD || mb_strlen($password) > self::MAX_PASSWORD) {
                $errores['password'] = 'La contraseña debe tener entre ' . self::MIN_PASSWORD . ' y ' . self::MAX_PASSWORD . ' caracteres';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para registrar un usuario
     */
    private function validarRegistroUsuario($datos) {
        $errores = [];
        
        // Validar nombre de usuario
        if (!isset($datos['nombre_usuario'])) {
            $errores['nombre_usuario'] = 'El nombre de usuario es obligatorio';
        } else {
            $nombreUsuario = trim($datos['nombre_usuario']);
            if (empty($nombreUsuario)) {
                $errores['nombre_usuario'] = 'El nombre de usuario es obligatorio';
            } elseif (mb_strlen($nombreUsuario) < self::MIN_USERNAME || mb_strlen($nombreUsuario) > self::MAX_USERNAME) {
                $errores['nombre_usuario'] = 'El nombre de usuario debe tener entre ' . self::MIN_USERNAME . ' y ' . self::MAX_USERNAME . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $nombreUsuario)) {
                $errores['nombre_usuario'] = 'El nombre de usuario solo puede contener letras, números y guiones bajos';
            }
        }
        
        // Validar contraseña
        if (!isset($datos['clave'])) {
            $errores['clave'] = 'La contraseña es obligatoria';
        } else {
            $clave = $datos['clave'];
            if (empty($clave)) {
                $errores['clave'] = 'La contraseña es obligatoria';
            } elseif (mb_strlen($clave) < self::MIN_PASSWORD || mb_strlen($clave) > self::MAX_PASSWORD) {
                $errores['clave'] = 'La contraseña debe tener entre ' . self::MIN_PASSWORD . ' y ' . self::MAX_PASSWORD . ' caracteres';
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $clave)) {
                $errores['clave'] = 'La contraseña debe contener al menos una letra mayúscula, una letra minúscula y un número';
            }
        }
        
        // Validar nombre
        if (!isset($datos['nombre'])) {
            $errores['nombre'] = 'El nombre es obligatorio';
        } else {
            $nombre = trim($datos['nombre']);
            if (empty($nombre)) {
                $errores['nombre'] = 'El nombre es obligatorio';
            } elseif (mb_strlen($nombre) > self::MAX_NOMBRE) {
                $errores['nombre'] = 'El nombre no debe exceder los ' . self::MAX_NOMBRE . ' caracteres';
            } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) {
                $errores['nombre'] = 'El nombre solo puede contener letras y espacios';
            }
        }
        
        // Validar apellido
        if (!isset($datos['apellido'])) {
            $errores['apellido'] = 'El apellido es obligatorio';
        } else {
            $apellido = trim($datos['apellido']);
            if (empty($apellido)) {
                $errores['apellido'] = 'El apellido es obligatorio';
            } elseif (mb_strlen($apellido) > self::MAX_APELLIDO) {
                $errores['apellido'] = 'El apellido no debe exceder los ' . self::MAX_APELLIDO . ' caracteres';
            } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $apellido)) {
                $errores['apellido'] = 'El apellido solo puede contener letras y espacios';
            }
        }
        
        // Validar correo
        if (!isset($datos['correo'])) {
            $errores['correo'] = 'El correo electrónico es obligatorio';
        } else {
            $correo = trim($datos['correo']);
            if (empty($correo)) {
                $errores['correo'] = 'El correo electrónico es obligatorio';
            } elseif (mb_strlen($correo) > self::MAX_EMAIL) {
                $errores['correo'] = 'El correo electrónico no debe exceder los ' . self::MAX_EMAIL . ' caracteres';
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $errores['correo'] = 'El formato del correo electrónico no es válido';
            }
        }
        
        // Validar teléfono
        if (!isset($datos['telefono'])) {
            $errores['telefono'] = 'El teléfono es obligatorio';
        } else {
            $telefono = trim($datos['telefono']);
            if (empty($telefono)) {
                $errores['telefono'] = 'El teléfono es obligatorio';
            } elseif (mb_strlen($telefono) > self::MAX_TELEFONO) {
                $errores['telefono'] = 'El teléfono no debe exceder los ' . self::MAX_TELEFONO . ' caracteres';
            } elseif (!preg_match('/^[0-9\-\+\(\)\s]+$/', $telefono)) {
                $errores['telefono'] = 'El teléfono solo puede contener números, guiones, paréntesis y el signo +';
            }
        }
        
        // Validar cédula
        if (!isset($datos['cedula'])) {
            $errores['cedula'] = 'La cédula es obligatoria';
        } else {
            $cedula = trim($datos['cedula']);
            if (empty($cedula)) {
                $errores['cedula'] = 'La cédula es obligatoria';
            } elseif (mb_strlen($cedula) < self::MIN_CEDULA || mb_strlen($cedula) > self::MAX_CEDULA) {
                $errores['cedula'] = 'La cédula debe tener entre ' . self::MIN_CEDULA . ' y ' . self::MAX_CEDULA . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $cedula)) {
                $errores['cedula'] = 'La cédula solo puede contener letras, números y guiones';
            }
        }
        
        // Validar dirección (opcional)
        if (isset($datos['direccion'])) {
            $direccion = trim($datos['direccion']);
            if (!empty($direccion) && mb_strlen($direccion) > self::MAX_DIRECCION) {
                $errores['direccion'] = 'La dirección no debe exceder los ' . self::MAX_DIRECCION . ' caracteres';
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para iniciar sesión (método público)
     */
    public function validarInicioSesionDatos($datos) {
        return $this->validarInicioSesion($datos);
    }
    
    /**
     * Valida los datos para registrar usuario (método público)
     */
    public function validarRegistroUsuarioDatos($datos) {
        return $this->validarRegistroUsuario($datos);
    }
    
    /**
     * Verifica si un nombre de usuario ya existe
     */
    private function verificarUsernameExistente($username) {
        try {
            $p = $this->co->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE username = ?");
            $p->execute([$username]);
            return $p->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Error en verificarUsernameExistente: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si un correo electrónico ya existe
     */
    private function verificarCorreoExistente($correo) {
        try {
            $p = $this->co->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE correo = ?");
            $p->execute([$correo]);
            return $p->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Error en verificarCorreoExistente: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si una cédula ya existe
     */
    private function verificarCedulaExistente($cedula) {
        try {
            $p = $this->co->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE cedula = ?");
            $p->execute([$cedula]);
            return $p->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Error en verificarCedulaExistente: ' . $e->getMessage());
            return false;
        }
    }
}

