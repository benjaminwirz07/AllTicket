<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

$id = trim($_POST['id'] ?? '');

$data = [
    'name'     => trim($_POST['name'] ?? ''),
    'address'  => trim($_POST['address'] ?? ''),
    'city'     => trim($_POST['city'] ?? ''),
    'province' => trim($_POST['province'] ?? ''),
    'capacity' => trim($_POST['capacity'] ?? ''),
];

$errors = [];

if ($data['name'] === '' || mb_strlen($data['name']) > 150) {
    $errors['name'] = 'El nombre es obligatorio (máximo 150 caracteres).';
}
if ($data['address'] === '' || mb_strlen($data['address']) > 255) {
    $errors['address'] = 'La dirección es obligatoria (máximo 255 caracteres).';
}
if ($data['city'] === '' || mb_strlen($data['city']) > 100) {
    $errors['city'] = 'La ciudad es obligatoria (máximo 100 caracteres).';
}
if (mb_strlen($data['province']) > 100) {
    $errors['province'] = 'La provincia admite hasta 100 caracteres.';
}
if (filter_var($data['capacity'], FILTER_VALIDATE_INT) === false || (int) $data['capacity'] <= 0) {
    $errors['capacity'] = 'La capacidad debe ser un número entero mayor a 0.';
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = $data + ['id' => $id];
    $volver = '/src/controllers/admin/venues/form.php' . ($id !== '' ? '?id=' . urlencode($id) : '');
    header('Location: ' . $volver);
    exit;
}

$params = [
    'name'     => $data['name'],
    'address'  => $data['address'],
    'city'     => $data['city'],
    'province' => $data['province'] !== '' ? $data['province'] : null,
    'capacity' => (int) $data['capacity'],
];

try {
    if ($id === '') {
        $stmt = $pdo->prepare(
            'INSERT INTO venues (name, address, city, province, capacity)
             VALUES (:name, :address, :city, :province, :capacity)'
        );
        $stmt->execute($params);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Lugar creado correctamente.'];
    } else {
        $params['id'] = $id;
        $stmt = $pdo->prepare(
            'UPDATE venues
                SET name = :name, address = :address, city = :city,
                    province = :province, capacity = :capacity
              WHERE id = :id'
        );
        $stmt->execute($params);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Lugar actualizado correctamente.'];
    }
} catch (PDOException $e) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'No se pudo guardar el lugar.'];
}

header('Location: /src/controllers/admin/venues/index.php');
exit;