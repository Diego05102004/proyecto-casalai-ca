<?php
require_once 'phpmailer/src/Exception.php';
require_once 'phpmailer/src/PHPMailer.php';
require_once 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

// Verificar si la clase existe
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo '✅ PHPMailer se cargó correctamente';
} else {
    echo '❌ Error: PHPMailer no se pudo cargar';
}
?>