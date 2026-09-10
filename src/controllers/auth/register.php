<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/src/views/auth/register.php');
    exit;
}

// Limpieza básica de entradas
$name           = trim($_POST['name'] ?? '');
$email          = trim($_POST['email'] ?? '');
$password       = $_POST['password'] ?? '';
$repeatPassword = $_POST['repeatPassword'] ?? '';

$errors = [];

// 1. Validaciones de datos requeridos y formato
if (empty($name)) {
    $errors['name'] = 'El nombre de usuario es obligatorio.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Ingresá un correo electrónico válido.';
}

// 2. Validación estricta de contraseña
if (empty($password)) {
    $errors['password'] = 'La contraseña es obligatoria.';
} elseif (
    strlen($password) < 8 || 
    !preg_match('/[A-Z]/', $password) || 
    !preg_match('/[0-9]/', $password) || 
    !preg_match('/[^A-Za-z0-9]/', $password)
) {
    $errors['password'] = 'La contraseña debe tener mínimo 8 caracteres, una mayúscula, un número y un símbolo.';
}

if ($password !== $repeatPassword) {
    $errors['repeatPassword'] = 'Las contraseñas no coinciden.';
}

// 3. Verificación de duplicados en la base de datos (solo si el email parece válido)
if (empty($errors['email'])) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        $errors['email'] = 'Este correo electrónico ya está registrado. Intentá iniciar sesión.';
    }
}

// 4. Si existen errores, guardamos el estado y reorientamos al usuario
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = [
        'name'  => $name,
        'email' => $email
    ];
    header('Location: ' . BASE_URL . '/src/views/auth/register.php');
    exit;
}

// 5. Inserción exitosa
try {
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
    $stmt->execute([
        'name'     => $name,
        'email'    => $email,
        'password' => $hashedPassword
    ]);

    $_SESSION['success'] = '¡Cuenta creada con éxito! Ya podés iniciar sesión.';
    header('Location: ' . BASE_URL . '/src/views/auth/login.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['errors'] = ['general' => 'Ocurrió un error en el servidor. Intentá nuevamente más tarde.'];
    $_SESSION['old']    = ['name' => $name, 'email' => $email];
    header('Location: ' . BASE_URL . '/src/views/auth/register.php');
    exit;
}