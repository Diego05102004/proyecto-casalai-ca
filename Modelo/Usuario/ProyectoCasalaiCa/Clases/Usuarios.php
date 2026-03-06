<?php 
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
class Usuarios extends BD {
    
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

    public function __construct($tipo = 'S') {
        parent::__construct($tipo);
    }

    /**
     * @param callable
     * @param string
     */

    protected function ejecutarConConexionSegura($operation, $tipo = 'S') {
        $db = new BD($tipo); 
        $pdo = $db->getConexion();

        try {
            if (!$pdo instanceof \PDO) {
                throw new \RuntimeException("La conexión PDO [$tipo] no es válida.");
            }

            $pdo->beginTransaction();
            $resultado = $operation($pdo);
            $pdo->commit();
            return $resultado;

        } catch (\Exception $e) {
            if ($pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new \RuntimeException("Error en BD [$tipo]: " . $e->getMessage());
        } finally {
            $db->cerrar();
        }
    }
    
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
    
    public function validarActualizarAvatar($datos) {
        $errores = [];
        
        // Validar que se haya subido un archivo
        if (!isset($datos['foto_perfil']) || empty($datos['foto_perfil'])) {
            $errores['foto_perfil'] = 'Debe seleccionar una imagen para el avatar';
            return $errores;
        }
        
        // Validar que el archivo sea un array (información de $_FILES)
        if (!is_array($datos['foto_perfil'])) {
            $errores['foto_perfil'] = 'El formato del archivo no es válido';
            return $errores;
        }
        
        $archivo = $datos['foto_perfil'];
        
        // Validar que no haya error en la subida
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $errores_mensajes = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'No existe directorio temporal',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en disco',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
            ];
            
            $error_msg = $errores_mensajes[$archivo['error']] ?? 'Error desconocido al subir el archivo';
            $errores['foto_perfil'] = $error_msg;
            return $errores;
        }
        
        // Validar tipo de archivo
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($archivo['type'], $tipos_permitidos)) {
            $errores['foto_perfil'] = 'Solo se permiten archivos JPG, PNG, GIF o WebP';
        }
        
        // Validar tamaño (2MB máximo)
        $tamano_maximo = 2 * 1024 * 1024; // 2MB en bytes
        if ($archivo['size'] > $tamano_maximo) {
            $errores['foto_perfil'] = 'La imagen debe ser menor a 2MB';
        }
        
        // Validar extensión del archivo
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $extensiones_permitidas)) {
            $errores['foto_perfil'] = 'La extensión del archivo no es permitida';
        }
        
        // Validar que sea una imagen real (usando getimagesize) - solo si hay tmp_name
        if (!empty($archivo['tmp_name']) && file_exists($archivo['tmp_name'])) {
            $info_imagen = @getimagesize($archivo['tmp_name']);
            if ($info_imagen === false) {
                // Si getimagesize falla, verificar con exif_imagetype (más simple)
                if (function_exists('exif_imagetype')) {
                    $tipo_imagen = @exif_imagetype($archivo['tmp_name']);
                    if ($tipo_imagen === false) {
                        $errores['foto_perfil'] = 'El archivo no es una imagen válida';
                    }
                } else {
                    // Si tampoco funciona, verificar por extensión y tamaño mínimo
                    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
                    if (!in_array($extension, $extensiones_permitidas)) {
                        $errores['foto_perfil'] = 'La extensión del archivo no es permitida';
                    } elseif ($archivo['size'] < 100) { // Mínimo 100 bytes
                        $errores['foto_perfil'] = 'El archivo parece demasiado pequeño para ser una imagen';
                    }
                }
            }
        }
        
        return $errores;
    }

    public function clienteExiste($cedula) {
        return $this->c_clienteExiste($cedula);
    }
    private function c_clienteExiste($cedula) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($cedula){
            $sql = "SELECT COUNT(*) FROM tbl_clientes WHERE cedula = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cedula]);
            return $stmt->fetchColumn() > 0;
        }, 'P');
    }

    public function ingresarUsuario() {
        return $this->i_ingresarUsuario();
    }
    private function i_ingresarUsuario() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $pdo->beginTransaction();
                $claveEncriptada = password_hash($this->clave, PASSWORD_BCRYPT);

                $sql = "INSERT INTO tbl_usuarios (username, password, id_rol, correo, nombres, apellidos, telefono, cedula)
                        VALUES (:username, :clave, :id_rol, :correo, :nombres, :apellidos, :telefono, :cedula)";
                $stmt = $pdo->prepare($sql);
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
                    $stmtCliente = $pdo->prepare($sqlCliente);
                    $nombreCompleto = $this->nombre . ' ' . $this->apellido;
                    $stmtCliente->bindParam(':nombre', $nombreCompleto);
                    $stmtCliente->bindParam(':cedula', $this->cedula);
                    $stmtCliente->bindParam(':telefono', $this->telefono);
                    $stmtCliente->bindParam(':correo', $this->correo);
                    $stmtCliente->execute();
                }

                $pdo->commit();
                return true;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                return false;
            }
        }, 'S');
    }

    public function modificarUsuario($id_usuario) {
        return $this->m_modificarUsuario($id_usuario);
    }
    private function m_modificarUsuario($id_usuario) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario){
            try {
                $pdo->beginTransaction();
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

                $stmt = $pdo->prepare($sql);
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
                    $stmtCliente = $pdo->prepare($sqlCliente);
                    $nombreCompleto = $this->nombre . ' ' . $this->apellido;
                    $stmtCliente->bindParam(':nombre', $nombreCompleto);
                    $stmtCliente->bindParam(':telefono', $this->telefono);
                    $stmtCliente->bindParam(':correo', $this->correo);
                    $stmtCliente->bindParam(':cedula', $this->cedula);
                    $stmtCliente->execute();
                }

                $pdo->commit();
                return true;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                return false;
            }
        }, 'S');
    }

    public function existeUsuario($username, $excluir_id = null) {
        return $this->e_existeUsuario($username, $excluir_id);
    }
    private function e_existeUsuario($username, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($username, $excluir_id) {
            
            $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE username = ?";
            $params = [$username];

            if ($excluir_id !== null) {
                $sql .= " AND id_usuario != ?";
                $params[] = $excluir_id;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;

        }, 'S');
    }

    public function existeCedula($cedula, $excluir_id = null) {
        return $this->e_existeCedula($cedula, $excluir_id);
    }
    private function e_existeCedula($cedula, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($cedula, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE cedula = ?";
            $params = [$cedula];

            if ($excluir_id !== null) {
                $sql .= " AND id_usuario != ?";
                $params[] = $excluir_id;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        }, 'S');
    }

    public function existeCorreo($correo, $excluir_id = null) {
        return $this->e_existeCorreo($correo, $excluir_id);
    }

    private function e_existeCorreo($correo, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($correo, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE correo = ?";
            $params = [$correo];

            if ($excluir_id !== null) {
                $sql .= " AND id_usuario != ?";
                $params[] = $excluir_id;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        }, 'S');
    }

    public function obtenerUltimoUsuario() {
        return $this->o_ultimoUsuario();
    }
    private function o_ultimoUsuario() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT usuarios.*, rol.nombre_rol 
                    FROM tbl_usuarios AS usuarios
                    INNER JOIN tbl_rol AS rol ON usuarios.id_rol = rol.id_rol
                    ORDER BY usuarios.id_usuario DESC LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $usuario ?: null;
        }, 'S');
    }

    public function obtenerUsuarioPorId($id_usuario) {
        return $this->o_usuarioPorId($id_usuario);
    }
    private function o_usuarioPorId($id_usuario) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario) {
            $query = "SELECT usuarios.*, rol.nombre_rol 
                      FROM tbl_usuarios AS usuarios
                      INNER JOIN tbl_rol AS rol ON usuarios.id_rol = rol.id_rol
                      WHERE usuarios.id_usuario = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$id_usuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }, 'S');
    }

    public function eliminarUsuario($id_usuario) {
        return $this->d_eliminarUsuario($id_usuario);
    }
    private function d_eliminarUsuario($id_usuario) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario) {
            $sql = "DELETE FROM tbl_usuarios WHERE id_usuario = :id_usuario";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_usuario', $id_usuario);
            return $stmt->execute();
        }, 'S');
    }

    public function cambiarEstatus($nuevoEstatus) {
        return $this->c_cambiarEstatus($nuevoEstatus);
    }
    private function c_cambiarEstatus($nuevoEstatus) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nuevoEstatus) {
            try {
                $sql = "UPDATE tbl_usuarios SET estatus = :estatus WHERE id_usuario = :id_usuario";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':estatus', $nuevoEstatus);
                $stmt->bindParam(':id_usuario', $this->id_usuario);
                return $stmt->execute();
            } catch (PDOException $e) {
                return false;
            }
        }, 'S');
    }

    public function obtenerReporteRoles() {
        return $this->r_reporteRoles();
    }
    private function r_reporteRoles() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT rol.nombre_rol, COUNT(u.id_usuario) as cantidad
                    FROM tbl_rol rol
                    LEFT JOIN tbl_usuarios u ON rol.id_rol = u.id_rol
                    GROUP BY rol.id_rol, rol.nombre_rol
                    ORDER BY cantidad DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }, 'S');
    }

    public function actualizarPerfil($id_usuario, $datos) {
        return $this->a_actualizarPerfil($id_usuario, $datos);
    }

    private function a_actualizarPerfil($id_usuario, $datos) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario, $datos) {
            $params = [':id_usuario' => $id_usuario];
            $updates = [];

            foreach ($datos as $campo => $valor) {
                if ($valor !== '' || $campo === 'password') {
                    $updates[] = "$campo = :$campo";
                    $params[":$campo"] = ($campo === 'password') 
                        ? password_hash($valor, PASSWORD_BCRYPT) 
                        : $valor;
                }
            }

            if (empty($updates)) {
                return false;
            }

            $sql = "UPDATE tbl_usuarios SET " . implode(", ", $updates) . " WHERE id_usuario = :id_usuario";

            $stmt = $pdo->prepare($sql);
            return $stmt->execute($params);
            
        }, 'S');
    }

    public function getusuarios($estatus = 'habilitado') {
        return $this->g_getusuarios($estatus);
    }
    private function g_getusuarios($estatus = 'habilitado') {
        return $this->ejecutarConConexionSegura(function($pdo) {
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
        }, 'S');
    }
}