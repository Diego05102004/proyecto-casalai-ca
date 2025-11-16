$(document).ready(function() {
    console.log('perfil.js cargado correctamente');
    
    // Asegurarnos de que el modal esté oculto al cargar la página
    $('#avatarModal').hide();
    
    // Toggle para edición de información personal
    $('#btn-edit-personal').click(function() {
        $('#personal-info-display').hide();
        $('#personal-edit-form').show();
    });
    
    $('#btn-cancel-personal').click(function() {
        $('#personal-edit-form').hide();
        $('#personal-info-display').show();
        $('#personal-message').empty();
    });
    
    // Toggle para cambio de correo
    $('#btn-change-email').click(function() {
        $('#email-change-form').show();
        $(this).hide();
    });
    
    $('#btn-cancel-email').click(function() {
        $('#email-change-form').hide();
        $('#btn-change-email').show();
        $('#email-message').empty();
        $('#form-email')[0].reset();
    });
    
    // Toggle para cambio de contraseña
    $('#btn-change-password').click(function() {
        $('#password-change-form').show();
        $(this).hide();
    });
    
    $('#btn-cancel-password').click(function() {
        $('#password-change-form').hide();
        $('#btn-change-password').show();
        $('#password-message').empty();
        $('#form-password')[0].reset();
    });
    
    // Modal para foto de perfil
    $('#btn-change-avatar').click(function() {
        $('#avatarModal').show();
    });
    
    $('#closeAvatarModal, #cancelAvatarModal').click(function() {
        $('#avatarModal').hide();
        $('#form-avatar')[0].reset();
        const inicial = $('#avatarPreview').data('inicial') || '';
        $('#avatarPreview').html(inicial);
    });
    
    // Cerrar modal al hacer clic fuera
    $(window).click(function(e) {
        if ($(e.target).is('#avatarModal')) {
            $('#avatarModal').hide();
            $('#form-avatar')[0].reset();
            const inicial = $('#avatarPreview').data('inicial') || '';
            $('#avatarPreview').html(inicial);
        }
    });

    // Validaciones
    $('#nombres').on('keypress', function(e) {
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]*$/, e);
    }).on('keyup', function() {
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]{2,50}$/,
            $(this),
            $('#snombres'),
            '*Solo letras, de 2 a 50 caracteres*'
        );
    });
    
    $('#apellidos').on('keypress', function(e) {
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]*$/, e);
    }).on('keyup', function() {
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]{2,50}$/,
            $(this),
            $('#sapellidos'),
            '*Solo letras, de 2 a 50 caracteres*'
        );
    });
    
    $('#username').on('keypress', function(e) {
        validarKeyPress(/^[a-zA-Z0-9_]*$/, e);
    }).on('keyup', function() {
        validarKeyUp(
            /^[a-zA-Z0-9_]{3,20}$/,
            $(this),
            $('#susername'),
            '*Solo letras, números y guiones bajos, de 3 a 20 caracteres*'
        );
    });
    
    $('#new_email').on('keypress', function(e) {
        validarKeyPress(/^[a-zA-ZñÑ_0-9@,.\b]*$/, e);
    }).on('keyup', function() {
        validarKeyUp(
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            $(this),
            $('#snew_email'),
            '*Formato válido: ejemplo@gmail.com*'
        );
    });
    
    $('#new_password').on('keypress', function(e) {
        validarKeyPress(/^[A-Za-z0-9\b\u00f1\u00d1\u00E0-\u00FC]*$/, e);
    }).on('keyup', function() {
        if ($(this).val().length > 0) {
            validarKeyUp(
                /^[A-Za-z0-9\b\u00f1\u00d1\u00E0-\u00FC]{6,15}$/,
                $(this),
                $('#snew_password'),
                '*Solo letras y números, de 6 a 15 caracteres*'
            );
        } else {
            $('#snew_password').text('');
        }
    });
    
    $('#confirm_password').on('keyup', function() {
        if ($(this).val().length > 0) {
            if ($(this).val() !== $('#new_password').val()) {
                $('#sconfirm_password').text('*Las contraseñas no coinciden*');
            } else {
                $('#sconfirm_password').text('');
            }
        } else {
            $('#sconfirm_password').text('');
        }
    });

     $('#telefono').on('input', function() {
        let valor = $(this).val().replace(/\D/g, '');
        if(valor.length > 4 && valor.length <= 7) {
            valor = valor.slice(0,4) + '-' + valor.slice(4);
        } else if(valor.length > 7) {
            valor = valor.slice(0,4) + '-' + valor.slice(4,7) + '-' + valor.slice(7,11);
        }
        $(this).val(valor);
    });

    
    
     $('#foto_perfil').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'La imagen debe ser menor a 2MB'
                });
                $(this).val('');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#avatarPreview').html('<img src="' + e.target.result + '" alt="Vista previa" class="avatar-preview-img">');
            }
            reader.readAsDataURL(file);
        }
    });

    $('#form-personal').submit(function(e) {
        e.preventDefault();
        
        // Validar campos requeridos
        if (!$('#username').val() || !$('#nombres').val() || !$('#apellidos').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Todos los campos son obligatorios'
            });
            return;
        }
        
        // Solicitar contraseña con SweetAlert
        Swal.fire({
            title: 'Confirmar cambios',
            text: 'Ingresa tu contraseña actual para confirmar los cambios:',
            input: 'password',
            inputAttributes: {
                autocapitalize: 'off',
                autocorrect: 'off'
            },
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: (password) => {
                if (!password) {
                    Swal.showValidationMessage('La contraseña es requerida');
                }
                return password;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('tipo', 'personal');
                formData.append('username', $('#username').val());
                formData.append('nombres', $('#nombres').val());
                formData.append('apellidos', $('#apellidos').val());
                formData.append('telefono', $('#telefono').val());
                formData.append('clave_actual', result.value);

                enviarFormulario('personal', formData);
            }
        });
    });
    
    // Envío del formulario de cambio de correo
    $('#form-email').submit(function(e) {
        e.preventDefault();
        
        if (!$('#new_email').val() || !$('#password_email').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Todos los campos son obligatorios'
            });
            return;
        }
        
        const formData = new FormData();
        formData.append('tipo', 'email');
        formData.append('new_email', $('#new_email').val());
        formData.append('password', $('#password_email').val());

        enviarFormulario('email', formData);
    });
    
    // Envío del formulario de cambio de contraseña
    $('#form-password').submit(function(e) {
        e.preventDefault();
        
        if (!$('#current_password').val() || !$('#new_password').val() || !$('#confirm_password').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Todos los campos son obligatorios'
            });
            return;
        }
        
        if ($('#new_password').val() !== $('#confirm_password').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Las contraseñas no coinciden'
            });
            return;
        }
        
        const formData = new FormData();
        formData.append('tipo', 'password');
        formData.append('current_password', $('#current_password').val());
        formData.append('new_password', $('#new_password').val());
        formData.append('confirm_password', $('#confirm_password').val());

        enviarFormulario('password', formData);
    });
    
    // Envío del formulario de foto de perfil
    $('#form-avatar').submit(function(e) {
        e.preventDefault();
        
        const fileInput = $('#foto_perfil')[0];
        if (!fileInput.files[0]) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debes seleccionar una imagen'
            });
            return;
        }
        
        // Solicitar contraseña con SweetAlert
        Swal.fire({
            title: 'Confirmar cambio de foto',
            text: 'Ingresa tu contraseña actual para cambiar la foto de perfil:',
            input: 'password',
            inputAttributes: {
                autocapitalize: 'off',
                autocorrect: 'off'
            },
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: (password) => {
                if (!password) {
                    Swal.showValidationMessage('La contraseña es requerida');
                }
                return password;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('tipo', 'avatar');
                formData.append('foto_perfil', fileInput.files[0]);
                formData.append('clave_actual', result.value);

                enviarFormulario('avatar', formData);
            }
        });
    });
    
    // Función para enviar formularios
    function enviarFormulario(tipo, formData) {
        console.log('Enviando formulario tipo:', tipo);
        
        // Mostrar loader
        Swal.fire({
            title: 'Procesando...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: '?pagina=perfil',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('Respuesta recibida:', response);
                Swal.close();
                
                if (response && response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Acciones específicas según el tipo
                        switch(tipo) {
                            case 'personal':
                            case 'email':
                                location.reload();
                                break;
                            case 'password':
                                $('#password-change-form').hide();
                                $('#btn-change-password').show();
                                $('#form-password')[0].reset();
                                $('#snew_password').text('');
                                $('#sconfirm_password').text('');
                                break;
                            case 'avatar':
                                $('#avatarModal').hide();
                                $('#form-avatar')[0].reset();
                                const inicial = $('#avatarPreview').data('inicial') || '';
                                $('#avatarPreview').html(inicial);
                                location.reload();
                                break;
                        }
                    });
                } else if (response && response.status === 'info') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Información',
                        text: response.message
                    });
                } else {
                    const errorMsg = response && response.message ? response.message : 'Error desconocido';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMsg
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la petición:', error);
                Swal.close();
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error del servidor',
                    text: 'Ocurrió un error al procesar la solicitud'
                });
            }
        });
    }
});