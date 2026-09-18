<?php
require_once __DIR__ . '/../../config/bootstrap.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/auth/register.php');
    exit;
}

// Limpieza básica de entradas
$name           = trim($_POST['name'] ?? '');
$email          = trim($_POST['email'] ?? '');
$password       = $_POST['password'] ?? '';
$repeatPassword = $_POST['repeatPassword'] ?? '';

// Datos personales
$first_name  = trim($_POST['first_name'] ?? '');
$second_name = trim($_POST['second_name'] ?? '');
$last_name   = trim($_POST['last_name'] ?? '');
$dni         = trim($_POST['dni'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$city        = trim($_POST['city'] ?? '');
$province    = trim($_POST['province'] ?? '');
$birth_date  = trim($_POST['birth_date'] ?? '');

$errors = [];

// 1. Validaciones de datos requeridos y formato
if (empty($name)) {
    $errors['name'] = 'El nombre de usuario es obligatorio.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Ingresá un correo electrónico válido.';
}

// Validación de datos personales
if (empty($first_name)) {
    $errors['first_name'] = 'El nombre es obligatorio.';
}

if (empty($last_name)) {
    $errors['last_name'] = 'El apellido es obligatorio.';
}

if (!empty($dni) && !preg_match('/^[A-Za-z0-9]{6,20}$/', $dni)) {
    $errors['dni'] = 'El DNI/Documento debe tener entre 6 y 20 caracteres alfanuméricos.';
}

if (!empty($phone) && !preg_match('/^[0-9+\-\s()]{6,30}$/', $phone)) {
    $errors['phone'] = 'Ingresá un número de teléfono válido.';
}

if (!empty($birth_date)) {
    $birthDateTime = DateTime::createFromFormat('Y-m-d', $birth_date);
    if (!$birthDateTime || $birthDateTime->format('Y-m-d') !== $birth_date) {
        $errors['birth_date'] = 'Ingresá una fecha de nacimiento válida.';
    } elseif ($birthDateTime > new DateTime()) {
        $errors['birth_date'] = 'La fecha de nacimiento no puede ser futura.';
    }
}

// Validación de longitud máxima (según columnas de la BD)
if (!empty($name) && mb_strlen($name) > 50) {
    $errors['name'] = 'El nombre de usuario no puede superar los 50 caracteres.';
}

if (!empty($first_name) && mb_strlen($first_name) > 100) {
    $errors['first_name'] = 'El nombre no puede superar los 100 caracteres.';
}

if (!empty($second_name) && mb_strlen($second_name) > 100) {
    $errors['second_name'] = 'El segundo nombre no puede superar los 100 caracteres.';
}

if (!empty($last_name) && mb_strlen($last_name) > 100) {
    $errors['last_name'] = 'El apellido no puede superar los 100 caracteres.';
}

if (!empty($email) && mb_strlen($email) > 150) {
    $errors['email'] = 'El correo electrónico no puede superar los 150 caracteres.';
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

// 3. Verificación de duplicados en la base de datos
if (empty($errors['email'])) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        $errors['email'] = 'Este correo electrónico ya está registrado. Intentá iniciar sesión.';
    }
}

if (!empty($dni) && empty($errors['dni'])) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE dni = :dni LIMIT 1');
    $stmt->execute(['dni' => $dni]);
    if ($stmt->fetch()) {
        $errors['dni'] = 'Este DNI ya está registrado.';
    }
}

// 4. Si existen errores, guardamos el estado y reorientamos al usuario
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = [
        'name'        => $name,
        'email'       => $email,
        'first_name'  => $first_name,
        'second_name' => $second_name,
        'last_name'   => $last_name,
        'dni'         => $dni,
        'phone'       => $phone,
        'city'        => $city,
        'province'    => $province,
        'birth_date'  => $birth_date,
    ];
    header('Location: ../../views/auth/register.php');
    exit;
}

// 5. Inserción exitosa
try {
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (name, first_name, second_name, last_name, dni, phone, city, province, birth_date, email, password)
         VALUES (:name, :first_name, :second_name, :last_name, :dni, :phone, :city, :province, :birth_date, :email, :password)'
    );
    $stmt->execute([
        'name'        => $name,
        'first_name'  => $first_name,
        'second_name' => $second_name !== '' ? $second_name : null,
        'last_name'   => $last_name,
        'dni'         => $dni !== '' ? $dni : null,
        'phone'       => $phone !== '' ? $phone : null,
        'city'        => $city !== '' ? $city : null,
        'province'    => $province !== '' ? $province : null,
        'birth_date'  => $birth_date !== '' ? $birth_date : null,
        'email'       => $email,
        'password'    => $hashedPassword
    ]);

    $_SESSION['success'] = '¡Cuenta creada con éxito! Ya podés iniciar sesión.';
    header('Location: ../../views/auth/login.php');
    exit;

} catch (PDOException $e) {
    error_log('Error al registrar usuario: ' . $e->getMessage());

    $_SESSION['errors'] = ['general' => 'Ocurrió un error en el servidor. Intentá nuevamente más tarde.'];
    $_SESSION['old']    = [
        'name'        => $name,
        'email'       => $email,
        'first_name'  => $first_name,
        'second_name' => $second_name,
        'last_name'   => $last_name,
        'dni'         => $dni,
        'phone'       => $phone,
        'city'        => $city,
        'province'    => $province,
        'birth_date'  => $birth_date,
    ];
    header('Location: ../../views/auth/register.php');
    exit;
}