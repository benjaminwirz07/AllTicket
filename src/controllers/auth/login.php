<?php
require_once __DIR__ . '/../../config/bootstrap.php';

requireMethod('POST');
requireCsrfToken();

$email    = inputEmail($_POST, 'email');
$password = is_string($_POST['password'] ?? null) ? (string) $_POST['password'] : '';
$errors   = [];

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
    $stmt = $pdo->prepare('SELECT id, password, role FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['errors'] = ['auth' => 'Las credenciales ingresadas son incorrectas.'];
        $_SESSION['old']    = ['email' => $email];
        redirectToLogin();
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (string) $user['id'],
        'role' => (string) $user['role'],
    ];

    redirectTo('/src/views/index.php');

} catch (PDOException $e) {
    error_log('Error al iniciar sesión: ' . $e->getMessage());
    $_SESSION['errors'] = ['db' => 'Ocurrió un error al procesar el ingreso. Inténtalo más tarde.'];
    redirectToLogin();
}