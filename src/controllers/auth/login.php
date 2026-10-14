<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /src/views/auth/login.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
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
    header('Location: /src/views/auth/login.php');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['errors'] = ['auth' => 'Las credenciales ingresadas son incorrectas.'];
        $_SESSION['old']    = ['email' => $email];
        header('Location: /src/views/auth/login.php');
        exit;
    }

    session_regenerate_id(true);
    unset($user['password']);
    $_SESSION['user'] = $user;

    header('Location: /src/views/index.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['errors'] = ['db' => 'Ocurrió un error al procesar el ingreso. Inténtalo más tarde.'];
    header('Location: /src/views/auth/login.php');
    exit;
}