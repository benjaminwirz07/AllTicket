<?php
require_once __DIR__ . '/../../config/bootstrap.php';

// Paso clave #1: Validar tipo de solicitud ----------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {  // Si la solicitud no es POST, volves al register
  header('Location: /src/views/auth/register.php');
  exit;
}

// Paso clave #2: Tomar datos -----------------------------------
$data = [
  'email'           => trim($_POST['email'] ?? ''), // trim(str) saca los espacios al inicio y al final
  'name'            => trim($_POST['name'] ?? ''),
  'password'        => $_POST['password'] ?? '',
  'repeatPassword'  => $_POST['repeatPassword'] ?? ''
];

// Validaciones básicas
$errors = [];

if (empty($data['email'])) {
  $errors['email'] = 'El correo electrónico es obligatorio.';
} elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
  $errors['email'] = 'El formato del correo electrónico no es válido.';
} elseif (strlen($data['email']) > 150) {
  $errors['email'] = 'El correo electrónico es demasiado largo.';
}

if (empty($data['name'])) {
  $errors['name'] = 'El usuario es obligatorio.';
} elseif (strlen($data['name']) > 50) {
  $errors['name'] = 'El usuario no puede superar los 50 caracteres.';
}

if (empty($data['password'])) {
  $errors['password'] = 'La contraseña es obligatoria.';
} elseif (strlen($data['password']) < 8) {
  $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
} elseif (!preg_match('/[A-Z]/', $data['password'])) {
  $errors['password'] = 'La contraseña debe tener al menos una letra mayúscula.';
} elseif (!preg_match('/[0-9]/', $data['password'])) {
  $errors['password'] = 'La contraseña debe tener al menos un número.';
} elseif (!preg_match('/[^A-Za-z0-9]/', $data['password'])) {
  $errors['password'] = 'La contraseña debe tener al menos un carácter especial.';
}

if (empty($data['repeatPassword'])) {
  $errors['repeatPassword'] = 'Debés repetir la contraseña.';
} elseif ($data['password'] !== $data['repeatPassword']) {
  $errors['repeatPassword'] = 'Las contraseñas no coinciden.';
}

// Si hay errores de validación de campos, regresar al register
if (!empty($errors)) {
  $_SESSION['errors'] = $errors;
  $_SESSION['old']    = [
    'email' => $data['email'],
    'name'  => $data['name'],
  ];
  header('Location: /src/views/auth/register.php');
  exit;
}

// Paso clave #3: Hacer cosas ----------------------------------
try {
  // Validamos que el usuario no exista
  $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
  $stmt->execute(['email' => $data['email']]);

  if ($stmt->fetch()) {
    $_SESSION['errors'] = ['email' => 'Ya existe una cuenta registrada con ese correo electrónico.'];
    $_SESSION['old']    = [
      'email' => $data['email'],
      'name'  => $data['name'],
    ];
    header('Location: /src/views/auth/register.php');
    exit;
  }

  // Hasheamos la contraseña, nunca se guarda en texto plano
  $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

  // Insertamos en DB
  $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
  $stmt->execute([
    'name'     => $data['name'],
    'email'    => $data['email'],
    'password' => $hashedPassword,
  ]);

  // Mensaje de éxito para mostrar en el login (opcional, requiere que login.php lo lea)
  $_SESSION['success'] = 'Cuenta creada correctamente. Iniciá sesión para continuar.';

  // Redirigimos al login para que el usuario inicie sesión con su nueva cuenta
  header('Location: /src/views/auth/login.php');
  exit;
} catch (PDOException $e) {
  $_SESSION['errors'] = ['db' => 'Ocurrió un error al procesar el registro. Inténtalo más tarde.'];
  header('Location: /src/views/auth/register.php');
  exit;
}