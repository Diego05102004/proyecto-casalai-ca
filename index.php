<?php 
$pagina = "catalogo"; 

if (!empty($_GET['pagina'])){ 
   $pagina = $_GET['pagina'];  
}

$rango = "";
if (is_file("modelo/validalogin.php")) {
   require_once("modelo/validalogin.php");
   $v = new validalogin();
   if ($pagina == 'cerrar') {
      $v->destruyesesion();
   } else {
      $name = $v->leesesion();
   }
}

// Manejo especial para recuperación de contraseña
if ($pagina == 'password-recovery') {
    $action = $_GET['action'] ?? 'show_form';
    
    require_once("controlador/PasswordRecoveryController.php");
    $controller = new PasswordRecoveryController();
    
    switch ($action) {
        case 'request':
            $controller->procesarSolicitud();
            break;
        case 'reset':
            $controller->procesarReseteo();
            break;
        case 'show_reset_form':
            $controller->mostrarFormularioReseteo();
            break;
        default:
            $controller->mostrarFormularioRecuperacion();
            break;
    }
    exit;
} 
else if(is_file("controlador/".$pagina.".php")){ 
    require_once("controlador/".$pagina.".php");
}
else{
    echo "Página en construcción";
}