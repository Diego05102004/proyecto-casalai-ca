<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario - Tienda Online</title>
    <?php include 'header.php'; ?>

</head>
<body class="fondo" style="height:100vh; background-image:url(img/fondo.jpg); background-size:cover;">
<?php include 'NewNavBar.php'; ?>


    <div class="profile-container">
        <div class="profile-header">
            <div class="avatar-container">
                <div class="avatar">
                    <?php if (!empty($usuario['foto_perfil'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" alt="Foto de perfil" class="avatar-img">
                    <?php else: ?>
                        <?php echo substr($usuario['nombres'], 0, 1); ?>
                    <?php endif; ?>
                </div>
                <div class="avatar-edit" id="btn-change-avatar">
                    <img src="img/camera.svg" alt="Cambiar foto">
                </div>
            </div>
            <div class="user-info">
                <h1><?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></h1>
                <p>@<?php echo htmlspecialchars($usuario['username']); ?></p>
                <span class="user-rango"><?php echo htmlspecialchars($usuario['nombre_rol']); ?></span>
            </div>
        </div>
        
        <div class="profile-content">
            <!-- Información Personal -->
            <div class="profile-section">
                <div class="section-header">
                    <h2 class="section-title">Información Personal</h2>
                    <button class="btn-perfil" id="btn-edit-personal">
                        <img src="img/edit.svg" alt="Editar" class="btn-icon">
                        Editar
                    </button>
                </div>
                
                <div id="personal-info-display">
                    <div class="info-display">
                        <div class="info-label">Nombre de Usuario</div>
                        <div class="info-value"><?php echo htmlspecialchars($usuario['username']); ?></div>
                    </div>
                    
                    <div class="info-display">
                        <div class="info-label">Nombres</div>
                        <div class="info-value"><?php echo htmlspecialchars($usuario['nombres']); ?></div>
                    </div>
                    
                    <div class="info-display">
                        <div class="info-label">Apellidos</div>
                        <div class="info-value"><?php echo htmlspecialchars($usuario['apellidos']); ?></div>
                    </div>
                    
                    <div class="info-display">
                        <div class="info-label">Teléfono</div>
                        <div class="info-value"><?php echo htmlspecialchars($usuario['telefono']); ?></div>
                    </div>
                    
                    <div class="info-display">
                        <div class="info-label">Cédula</div>
                        <div class="info-value"><?php echo htmlspecialchars($usuario['cedula']); ?></div>
                    </div>
                    
                    <div class="info-display">
                        <div class="info-label">Rango</div>
                        <div class="info-value"><?php echo htmlspecialchars($usuario['nombre_rol']); ?></div>
                    </div>
                </div>
                
                <div id="personal-edit-form" class="hidden-section">
                    <div id="personal-message"></div>
                    
                    <form id="form-personal">
                        <input type="hidden" id="clave_actual_personal" name="clave_actual">
                        
                        <div class="form-group">
                            <label for="username">Nombre de Usuario</label>
                            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($usuario['username']); ?>">
                            <span id="susername" class="error-message"></span>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombres">Nombres</label>
                                <input type="text" id="nombres" name="nombres" class="form-control" value="<?php echo htmlspecialchars($usuario['nombres']); ?>">
                                <span id="snombres" class="error-message"></span>
                            </div>
                            
                            <div class="form-group">
                                <label for="apellidos">Apellidos</label>
                                <input type="text" id="apellidos" name="apellidos" class="form-control" value="<?php echo htmlspecialchars($usuario['apellidos']); ?>">
                                <span id="sapellidos" class="error-message"></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" class="form-control" value="<?php echo htmlspecialchars($usuario['telefono']); ?>">
                            <span id="stelefono" class="error-message"></span>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-success">
                                <img src="img/save.svg" alt="Guardar" class="btn-icon">
                                Guardar
                            </button>
                            <button type="button" class="btn btn-secondary" id="btn-cancel-personal">
                                <img src="img/close.svg" alt="Cancelar" class="btn-icon">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Configuración de Cuenta -->
            <div class="profile-section">
                <div class="section-header">
                    <h2 class="section-title">Configuración de Cuenta</h2>
                </div>
                
                <!-- Cambio de Correo -->
                <div class="form-group">
                    <div class="info-display">
                        <label>Correo Electrónico</label>
                        <div class="info-value"><?php echo htmlspecialchars($usuario['correo']); ?></div>
                        <button class="btn-perfil" style="margin-top: 10px;" id="btn-change-email">
                            <img src="img/email.svg" alt="Cambiar correo" class="btn-icon">
                            Cambiar Correo Electrónico
                        </button>
                    </div>
                </div>
                
                <div id="email-change-form" class="hidden-section">
                    <div id="email-message"></div>
                    
                    <form id="form-email">
                        <div class="form-group">
                            <label for="current_email">Correo Actual</label>
                            <input type="email" id="current_email" class="form-control" value="<?php echo htmlspecialchars($usuario['correo']); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_email">Nuevo Correo</label>
                            <input type="email" id="new_email" name="new_email" class="form-control" placeholder="Ingresa tu nuevo correo">
                            <span id="snew_email" class="error-message"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_email">Contraseña Actual</label>
                            <input type="password" id="password_email" name="password" class="form-control" placeholder="Ingresa tu contraseña para confirmar">
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-success">
                                <img src="img/save.svg" alt="Guardar" class="btn-icon">
                                Actualizar Correo
                            </button>
                            <button type="button" class="btn btn-secondary" id="btn-cancel-email">
                                <img src="img/close.svg" alt="Cancelar" class="btn-icon">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Cambio de Contraseña -->
                <div class="form-group">
                    <div class="info-display">
                        <label>Contraseña</label>
                        <button class="btn-perfil" id="btn-change-password">
                            <img src="img/lock.svg" alt="Cambiar contraseña" class="btn-icon">
                            Cambiar Contraseña
                        </button>
                    </div>
                </div>
                
                <div id="password-change-form" class="hidden-section">
                    <div id="password-message"></div>
                    
                    <form id="form-password">
                        <div class="form-group">
                            <label for="current_password">Contraseña Actual</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Ingresa tu contraseña actual">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Nueva Contraseña</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Ingresa tu nueva contraseña">
                            <span id="snew_password" class="error-message"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmar Nueva Contraseña</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirma tu nueva contraseña">
                            <span id="sconfirm_password" class="error-message"></span>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-success">
                                <img src="img/save.svg" alt="Guardar" class="btn-icon">
                                Actualizar Contraseña
                            </button>
                            <button type="button" class="btn btn-secondary" id="btn-cancel-password">
                                <img src="img/close.svg" alt="Cancelar" class="btn-icon">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal personalizado para cambiar foto de perfil -->
    <div id="avatarModal" class="modal-custom hidden-section">
        <div class="modal-content-custom">
            <div class="modal-header-custom">
                <h3>Cambiar Foto de Perfil</h3>
                <button type="button" class="close-modal" id="closeAvatarModal">&times;</button>
            </div>
            <div class="modal-body-custom avatar-modal-content">
                <div class="avatar-preview" id="avatarPreview" data-inicial="<?php echo htmlspecialchars(substr($usuario['nombres'], 0, 1)); ?>">
                    <?php if (!empty($usuario['foto_perfil'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" alt="Vista previa" class="avatar-preview-img">
                    <?php else: ?>
                        <?php echo substr($usuario['nombres'], 0, 1); ?>
                    <?php endif; ?>
                </div>
                
                <form id="form-avatar">
                    <div class="file-input-wrapper">
                        <div class="file-input-btn">
                            <img src="img/upload.svg" alt="Subir" class="btn-icon">
                            Seleccionar Imagen
                        </div>
                        <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*">
                    </div>
                    <small class="text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB</small>
                    
                    <div class="action-buttons" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-success">
                            <img src="img/save.svg" alt="Guardar" class="btn-icon">
                            Guardar Foto
                        </button>
                        <button type="button" class="btn btn-secondary" id="cancelAvatarModal">
                            <img src="img/close.svg" alt="Cancelar" class="btn-icon">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="Javascript/perfil.js"></script>

</body>
</html>