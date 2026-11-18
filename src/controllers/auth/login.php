<?php
// Valida las credenciales, autentica a la persona y crea una sesión renovada.
require_once __DIR__ . '/../../config/bootstrap.php';

requireMethod('POST');
requireCsrfToken();

$email    = inputEmail($_POST, 'email');
$password = is_string($_POST['password'] ?? null) ? (string) $_POST['password'] : '';
$errors   = [];

// Rechaza correos vacíos o mal formados y contraseñas faltantes antes de consultar usuarios.
if ($email === '') {
    $errors['email'] = 'El correo electrónico es obligatorio.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'El formato del correo electrónico no es válido.';
}

if ($password === '') {
    $errors['password'] = 'La contraseña es obligatoria.';
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = ['email' => $email];
    redirectToLogin();
}

try {
    // Busca el hash de contraseña asociado al correo y verifica la clave sin exponerla.
    $stmt = $pdo->prepare('SELECT id, password, role FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        // Usa un mensaje genérico para no revelar si el correo está registrado.
        $_SESSION['errors'] = ['auth' => 'Las credenciales ingresadas son incorrectas.'];
        $_SESSION['old']    = ['email' => $email];
        redirectToLogin();
    }

    // Renueva el identificador para evitar reutilizar la sesión previa a autenticarse.
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (string) $user['id'],
        'role' => (string) $user['role'],
    ];

    redirectTo('/src/views/index.php');

} catch (PDOException $e) {
    // Registra el detalle en el servidor y devuelve al formulario un error no sensible.
    error_log('Error al iniciar sesión: ' . $e->getMessage());
    $_SESSION['errors'] = ['db' => 'Ocurrió un error al procesar el ingreso. Inténtalo más tarde.'];
    redirectToLogin();
}