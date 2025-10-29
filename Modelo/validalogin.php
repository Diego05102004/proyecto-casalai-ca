<?php
class validalogin
{
    function leesesion()
    {

        if (empty($_SESSION)) {
            session_start();
        }
        if (isset($_SESSION['rango'])) {
            $s = $_SESSION['rango'];
        } else {
            $s = "";
        }
        return $s;
    }
    function destruyesesion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Location: ?pagina=catalogo');
        exit;
    }
}
