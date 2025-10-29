$(document).ready(function(){
        // Auto-ajuste de altura del textarea de Dirección (registro)
        (function(){
            const direccionField = document.getElementById('direccion');
            if (!direccionField) return;
            const MIN_H = 55; // px
            const MAX_H = 140; // px
            const autoResize = (el) => {
                el.style.height = 'auto';
                const next = Math.max(MIN_H, el.scrollHeight);
                el.style.height = Math.min(MAX_H, next) + 'px';
                el.style.overflowY = next > MAX_H ? 'auto' : 'hidden';
            };
            const togglePlaceholderCenter = () => {
                if (!direccionField.value) {
                    direccionField.classList.add('placeholder-center');
                } else {
                    direccionField.classList.remove('placeholder-center');
                }
            };
            direccionField.addEventListener('input', () => { autoResize(direccionField); togglePlaceholderCenter(); });
            direccionField.addEventListener('focus', togglePlaceholderCenter);
            direccionField.addEventListener('blur', togglePlaceholderCenter);
            setTimeout(() => { autoResize(direccionField); togglePlaceholderCenter(); }, 0);
        })();


        $("#nombre_usuario").on("keypress", function (e) {
          validarkeypress(/^[a-zA-Z0-9_]*$/, e);
        });
        $("#nombre_usuario").on("keyup", function () {
          validarkeyup(
            /^[a-zA-Z0-9_]{4,20}$/,
            $(this),
            $("#snombre_usuario"),
            "*El usuario debe tener entre 4 y 20 caracteres alfanuméricos*"
          );
        });

        $("#nombre").on("keypress", function (e) {
          validarkeypress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]*$/, e);
          let nombre = document.getElementById("nombre");
          nombre.value = space(nombre.value);
        });
        $("#nombre").on("keyup", function () {
          validarkeyup(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s]{2,50}$/,
            $(this),
            $("#snombre"),
            "*Solo letras, de 2 a 50 caracteres*"
          );
        });
        $("#apellido").on("keypress", function (e) {
          validarkeypress(/^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]*$/, e);
          let apellido_usuario = document.getElementById("apellido");
          apellido_usuario.value = space(apellido_usuario.value);
        });
        $("#apellido").on("keyup", function () {
          validarkeyup(
            /^[a-zA-ZÁÉÍÓÚÑáéíóúüÜ\s]{2,50}$/,
            $(this),
            $("#sapellido"),
            "*Solo letras, de 2 a 50 caracteres*"
          );
        });

        $("#cedula").on("keypress", function(e){
            validarkeypress(/^[0-9.]*$/, e);
        });

        $("#cedula").on("keyup", function(){
            validarkeyup(
                /^(?:\d{1,2}\.\d{3}\.\d{3})$/,
                $(this),
                $("#scedula"),
                "*Formato válido: 1.234.567 o 12.345.678*"
            );
        });
        $("#cedula").on("input", function() {
            let d = $(this).val().replace(/\D/g, '');
            let out = d;
            if (d.length === 7) {
                // X.XXX.XXX
                out = d.slice(0,1) + '.' + d.slice(1,4) + '.' + d.slice(4,7);
            } else if (d.length === 8) {
                // XX.XXX.XXX
                out = d.slice(0,2) + '.' + d.slice(2,5) + '.' + d.slice(5,8);
            }
            $(this).val(out);
        });

        $("#telefono").on("keypress", function (e) {
          validarkeypress(/^[0-9-]*$/, e);
        });

        $("#telefono").on("keyup", function () {
          validarkeyup(
            /^\d{4}-\d{3}-\d{4}$/,
            $(this),
            $("#stelefono_usuario"),
            "*Formato válido: 04XX-XXX-XXXX*"
          );
        });
        $("#telefono").on("input", function() {
            let valor_t1 = $(this).val().replace(/\D/g, '');
            if(valor_t1.length > 4 && valor_t1.length <= 7)
                valor_t1 = valor_t1.slice(0,4) + '-' + valor_t1.slice(4);
            else if(valor_t1.length > 7)
                valor_t1 = valor_t1.slice(0,4) + '-' + valor_t1.slice(4,7) + '-' + valor_t1.slice(7,11);
            $(this).val(valor_t1);
        });

        $("#correo").on("keypress", function (e) {
          validarkeypress(/^[a-zA-ZñÑ_0-9@,.\b]*$/, e);
        });

        $("#correo").on("keyup", function () {
          validarkeyup(
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            $(this),
            $("#scorreo_usuario"),
            "*Formato válido: example@gmail.com*"
          );
        });

        $("#direccion").on("keypress", function(e){
            validarkeypress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]*$/, e);
            let direccion = document.getElementById("direccion");
            direccion.value = space(direccion.value);
        });

        $("#direccion").on("keyup", function(){
            validarkeyup(
                /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{2,100}$/,
                $(this),
                $("#sdireccion"),
                "*El formato permite letras y números*"
            );
        });

        $("#clave").on("keypress", function (e) {
          validarkeypress(/^[A-Za-z0-9\b\u00f1\u00d1\u00E0-\u00FC]*$/, e);
        });
        $("#clave").on("keyup", function () {
          validarkeyup(
            /^[A-Za-z0-9\b\u00f1\u00d1\u00E0-\u00FC]{6,15}$/,
            $(this),
            $("#sclave_usuario"),
            "*Solo letras y números, de 6 a 15 caracteres*"
          );
        });

        $("#clave_confirmar").on("keypress", function (e) {
          validarkeypress(/^[A-Za-z0-9\b\u00f1\u00d1\u00E0-\u00FC]*$/, e);
        });
        $("#clave_confirmar").on("keyup", function () {
          validarkeyup(
            /^[A-Za-z0-9\b\u00f1\u00d1\u00E0-\u00FC]{6,15}$/,
            $(this),
            $("#sclave_confirmar"),
            "*Solo letras y números, de 6 a 15 caracteres*"
          );
        });

        $("#f input, #registro-usuario-cliente input, #direccion").on("keypress", function(e) {
        if (e.which === 13) { // 13 es el código de la tecla Enter
            e.preventDefault();
            
            // Determinar qué formulario enviar
            if ($(this).closest('form').attr('id') === 'f') {
                $("#acceder").click(); // Disparar el click en el botón de inicio de sesión
            } else {
                $("#registro-usuario-cliente").submit(); // Enviar formulario de registro
            }
        }
    });

    // Validación para el formulario de registro de usuario y cliente
    $("#registro-usuario-cliente").on("submit", function(e){
        let valido = true;
        let mensaje = "";

        // Validar campos vacíos
        $("#registro-usuario-cliente input[required], #registro-usuario-cliente textarea[required]").each(function(){
            if($.trim($(this).val()) === ""){
                valido = false;
                mensaje = "Todos los campos son obligatorios.";
                $(this).focus();
                return false;
            }
        });

        if(!valido){
            muestraMensaje("error", 4000, "Error de validación", mensaje);
            e.preventDefault();
            return false;
        }
    });

    // Formato automático para teléfono ####-###-####
    $("#telefono").on("input", function() {
        let valor = $(this).val().replace(/\D/g, '');
        if(valor.length > 4 && valor.length <= 7)
            valor = valor.slice(0,4) + '-' + valor.slice(4);
        else if(valor.length > 7)
            valor = valor.slice(0,4) + '-' + valor.slice(4,7) + '-' + valor.slice(7,11);
        $(this).val(valor);
    });

    // Función para bloquear caracteres inválidos
    function validarkeypress(er, e){
        let key = e.keyCode || e.which;
        let tecla = String.fromCharCode(key);
        if(!er.test(tecla)){
            e.preventDefault();
        }
    }

    // ... (el resto de tu código de validación y muestraMensaje permanece igual) ...

    
    //Función que verifica que exista algo dentro de un div
    //oculto y lo muestra por el modal
    if($.trim($("#mensajes").text()) != ""){
      muestraMensaje($("#mensajes").html());
    }
    //Fin de seccion de mostrar envio en modal mensaje//		
      
      
      $("#username").on("keypress",function(e){
        validarkeypress(/^[a-zA-Z0-9_]*$/,e);
      });
      
      $("#username").on("keyup",function(){
        validarkeyup(/^[a-zA-Z0-9_]{4,20}$/,$(this),
        $("#susername"),"*Ingrese su nombre de usuario*");
      });
      
      $("#password").on("keypress",function(e){
        validarkeypress(/^[A-Za-z0-9\b\u00f1\u00d1\u00E0-\u00FC]*$/,e);
      });
      
      $("#password").on("keyup",function(){
        
        validarkeyup(/^[A-Za-z0-9\b\u00f1\u00d1\u00E0-\u00FC]{6,15}$/,
        $(this),$("#spassword"),"*Ingrese su contraseña de seguridad*");
      });
      
      
      
    //FIN DE VALIDACION DE DATOS
    
    
    
    //CONTROL DE BOTONES
    
    
    $("#acceder").on("click",function(){
      event.preventDefault();
      if(validarenvio()){
        
        $("#accion").val("acceder");	
        $("#f").submit();
        
      }
    });
      
    });
    
    //Validación de todos los campos antes del envio
    function validarenvio(){
      
      if(validarkeyup(/^[A-Za-z0-9]{4,20}$/,$("#username"),
        $("#susername"),"El formato es de 4 y 20 caracteres")==0){
          muestraMensaje("error",4000,"ERROR!","El usuario es incorrecto, ingrese el usuario nuevamente");
        return false;					
      }	
      else if(validarkeyup(/^[A-Za-z0-9]{6,15}$/,
        $("#password"),$("#spassword"),"El formato es de 6 y 15 caracteres")==0){
         muestraMensaje("error",4000,"ERROR!","La contraseña es incorrecto, ingrese la contraseña nuevamente");
        return false;
      }
      
      
      return true;
    }
    
    
    //Funcion que muestra el modal con un mensaje
    function muestraMensaje(icono,tiempo,titulo,mensaje){
      Swal.fire({
      icon:icono,
        timer:tiempo,	
        title:titulo,
      html:mensaje,
      showConfirmButton:true,
      confirmButtonText:'Aceptar',
      });
    }
    
$(document).ready(function() {
    const mensajesDiv = $("#mensajes");
    const mensaje = mensajesDiv.data("mensaje");
    const tipo = mensajesDiv.data("tipo") || "error";

    if (mensaje) {
        muestraMensaje(tipo, 4000, tipo === "success" ? "¡Éxito!" : "Error", mensaje);
    }
});
    
    //Función para validar por Keypress
    function validarkeypress(er,e){
      
      key = e.keyCode;
      
      
        tecla = String.fromCharCode(key);
      
      
        a = er.test(tecla);
      
        if(!a){
      
        e.preventDefault();
        }
      
        
    }
    //Función para validar por keyup
    function validarkeyup(er,etiqueta,etiquetamensaje,
    mensaje){
      a = er.test(etiqueta.val());
      if(a){
        etiquetamensaje.text("");
        return 1;
      }
      else{
        etiquetamensaje.text(mensaje);
        return 0;
      }
    }
    