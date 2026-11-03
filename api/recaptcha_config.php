<?php
require_once __DIR__ . '/config.php';

$siteKey = apiEnvironmentValue('RECAPTCHA_SITE_KEY');
if ($siteKey === '') {
    errorResponse('reCAPTCHA no está configurado en el servidor.', 503);
}

jsonResponse(['site_key' => $siteKey]);
