<?php
require_once __DIR__ . '/../../config/bootstrap.php';

// Asegurarse de que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Paso clave #1: Validar tipo de solicitud ----------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /src/views/auth/login.php');
    exit;
}

// Paso clave #2: Tomar datos -----------------------------------
$data = [
    'email'    => trim($_POST['email'] ?? ''),
    'password' => $_POST['password'] ?? '',
];

// Paso clave #3: Validar datos ---------------------------------
$errors = [];

if (empty($data['email'])) {
    $errors['email'] = 'El correo electrónico es obligatorio.';
} elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'El formato del correo electrónico no es válido.';
}

if (empty($data['password'])) {
    $errors['password'] = 'La contraseña es obligatoria.';
}

// Si hay errores de validación de campos, regresar al login
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = ['email' => $data['email']];
    header('Location: /src/views/auth/login.php');
    exit;
}

// Paso clave #4: Buscar el usuario en la Base de Datos ---------
// (Asumiendo que $pdo viene configurado en bootstrap.php)
try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $data['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Paso clave #5: Verificar existencia y contraseña ----------
    if (!$user || !password_verify($data['password'], $user['password'])) {
        // Por seguridad, se da un mensaje genérico sin especificar si falló el email o la clave
        $_SESSION['errors'] = ['auth' => 'Las credenciales ingresadas son incorrectas.'];
        $_SESSION['old']    = ['email' => $data['email']];
        header('Location: /src/views/auth/login.php');
        exit;
    }

    // Paso clave #6: Iniciar sesión del usuario ------------------
    // Regenerar ID de sesión para prevenir Session Fixation
    session_regenerate_id(true);

    // Guardar los datos necesarios en la sesión (sin incluir el hash de la clave)
    unset($user['password']);
    $_SESSION['user'] = $user;

    // Paso clave #7: Redireccionar al área privada ---------------
    header('Location: /src/views/index.php');
    exit;

} catch (PDOException $e) {
    // Manejo de error de BD
    $_SESSION['errors'] = ['db' => 'Ocurrió un error al procesar el ingreso. Inténtalo más tarde.'];
    header('Location: /src/views/auth/login.php');
    exit;
}