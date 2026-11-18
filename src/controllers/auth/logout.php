<?php
// Finaliza la sesión autenticada y elimina también la cookie correspondiente.
require_once __DIR__ . '/../../config/bootstrap.php';

requireMethod('POST');
requireCsrfToken();

// Vacía los datos en memoria antes de destruir la sesión del servidor.
$_SESSION = [];
 
// Invalida en el navegador la cookie con los mismos atributos de la sesión original.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
 
// Destruimos la sesión en el servidor
session_destroy();
 
redirectToLogin();