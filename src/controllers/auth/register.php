<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/auth/register.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (
    empty($_SESSION['csrf_token']) ||
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    $_SESSION['errors'] = ['general' => 'La sesión expiró o el formulario no es válido. Intentá nuevamente.'];
    header('Location: ../../views/auth/register.php');
    exit;
}

$email          = trim($_POST['email'] ?? '');
$password       = $_POST['password'] ?? '';
$repeatPassword = $_POST['repeatPassword'] ?? '';

$usuario     = trim($_POST['usuario'] ?? '');
$first_name  = trim($_POST['first_name'] ?? '');
$second_name = trim($_POST['second_name'] ?? '');
$last_name   = trim($_POST['last_name'] ?? '');
$dni         = trim($_POST['dni'] ?? '');
$genero      = strtoupper(trim($_POST['genero'] ?? ''));
$phone       = trim($_POST['phone'] ?? '');
$city        = trim($_POST['city'] ?? '');
$province    = trim($_POST['province'] ?? '');
$birth_date  = trim($_POST['birth_date'] ?? '');

$generosPermitidos = ['F', 'M', 'X'];
$errors = [];

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Ingresá un correo electrónico válido.';
}

if ($usuario === '') {
    $errors['usuario'] = 'El usuario es obligatorio.';
} elseif (!preg_match('/^[A-Za-z0-9._]{4,30}$/', $usuario)) {
    $errors['usuario'] = 'El usuario debe tener entre 4 y 30 caracteres: letras, números, punto o guion bajo.';
}

if (empty($first_name)) {
    $errors['first_name'] = 'El nombre es obligatorio.';
}

if (empty($last_name)) {
    $errors['last_name'] = 'El apellido es obligatorio.';
}

if ($dni === '') {
    $errors['dni'] = 'El DNI es obligatorio.';
} elseif (!preg_match('/^\d{7,8}$/', $dni)) {
    $errors['dni'] = 'El DNI debe tener 7 u 8 números, sin puntos.';
}

if (!in_array($genero, $generosPermitidos, true)) {
    $errors['genero'] = 'Seleccioná un género.';
}

if (!empty($phone) && !preg_match('/^\d{10}$/', $phone)) {
    $errors['phone'] = 'El teléfono debe tener exactamente 10 números, sin 0 ni 15.';
}

if (!empty($birth_date)) {
    $birthDateTime = DateTime::createFromFormat('Y-m-d', $birth_date);
    if (!$birthDateTime || $birthDateTime->format('Y-m-d') !== $birth_date) {
        $errors['birth_date'] = 'Ingresá una fecha de nacimiento válida.';
    } elseif ($birthDateTime > new DateTime()) {
        $errors['birth_date'] = 'La fecha de nacimiento no puede ser futura.';
    }
}

if (!empty($first_name) && strlen($first_name) > 100) {
    $errors['first_name'] = 'El nombre no puede superar los 100 caracteres.';
}
if (!empty($second_name) && strlen($second_name) > 100) {
    $errors['second_name'] = 'El segundo nombre no puede superar los 100 caracteres.';
}
if (!empty($last_name) && strlen($last_name) > 100) {
    $errors['last_name'] = 'El apellido no puede superar los 100 caracteres.';
}
if (!empty($email) && strlen($email) > 150) {
    $errors['email'] = 'El correo electrónico no puede superar los 150 caracteres.';
}

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

$old = [
    'usuario'     => $usuario,
    'email'       => $email,
    'first_name'  => $first_name,
    'second_name' => $second_name,
    'last_name'   => $last_name,
    'dni'         => $dni,
    'genero'      => $genero,
    'phone'       => $phone,
    'city'        => $city,
    'province'    => $province,
    'birth_date'  => $birth_date,
];

if (empty($errors['email'])) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        $errors['email'] = 'Este correo electrónico ya está registrado. Intentá iniciar sesión.';
    }
}

if ($dni !== '' && empty($errors['dni'])) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE dni = :dni LIMIT 1');
    $stmt->execute(['dni' => $dni]);
    if ($stmt->fetch()) {
        $errors['dni'] = 'Este DNI ya está registrado.';
    }
}

if ($usuario !== '' && empty($errors['usuario'])) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE usuario = :usuario LIMIT 1');
    $stmt->execute(['usuario' => $usuario]);
    if ($stmt->fetch()) {
        $errors['usuario'] = 'Ese usuario ya está en uso. Elegí otro.';
    }
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = $old;
    header('Location: ../../views/auth/register.php');
    exit;
}

try {
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        'INSERT INTO users (
            usuario, first_name, second_name, last_name,
            dni, genero, phone, city, province, birth_date,
            email, password
        ) VALUES (
            :usuario, :first_name, :second_name, :last_name,
            :dni, :genero, :phone, :city, :province, :birth_date,
            :email, :password
        )'
    );

    $stmt->execute([
        'usuario'     => $usuario,
        'first_name'  => $first_name,
        'second_name' => $second_name !== '' ? $second_name : null,
        'last_name'   => $last_name,
        'dni'         => $dni,
        'genero'      => $genero,
        'phone'       => $phone !== '' ? $phone : null,
        'city'        => $city !== '' ? $city : null,
        'province'    => $province !== '' ? $province : null,
        'birth_date'  => $birth_date !== '' ? $birth_date : null,
        'email'       => $email,
        'password'    => $hashedPassword,
    ]);

    unset($_SESSION['csrf_token']);
    $_SESSION['success'] = '¡Cuenta creada con éxito! Ya podés iniciar sesión.';
    header('Location: ../../views/auth/login.php');
    exit;

} catch (PDOException $e) {
    error_log('Error al registrar usuario: ' . $e->getMessage());
    $_SESSION['errors'] = ['general' => 'Ocurrió un error en el servidor. Intentá nuevamente más tarde.'];
    $_SESSION['old']    = $old;
    header('Location: ../../views/auth/register.php');
    exit;
}