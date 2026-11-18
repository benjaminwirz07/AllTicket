<?php
// Valida y persiste los datos del lugar enviado desde el panel administrativo.
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

requireMethod('POST');
requireCsrfToken();

$id = inputString($_POST, 'id');
// Comprueba que el identificador de edición tenga el formato UUID esperado.
if ($id !== '' && !isValidUuid($id)) {
    http_response_code(400);
    exit('Solicitud no válida');
}

$data = [
    'name'     => inputString($_POST, 'name'),
    'address'  => inputString($_POST, 'address'),
    'city'     => inputString($_POST, 'city'),
    'province' => inputString($_POST, 'province'),
    'capacity' => inputString($_POST, 'capacity'),
];

$errors = [];

// Valida campos obligatorios, longitudes y capacidad antes de preparar la escritura.
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
if (
    filter_var($data['capacity'], FILTER_VALIDATE_INT) === false
    || (int) $data['capacity'] <= 0
    || (int) $data['capacity'] > 2147483647
) {
    $errors['capacity'] = 'La capacidad debe ser un número entero mayor a 0.';
}

if (!empty($errors)) {
    // Conserva los valores para que se puedan corregir sin volver a completar el formulario.
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = $data + ['id' => $id];
    $volver = '/src/controllers/admin/venues/form.php' . ($id !== '' ? '?id=' . urlencode($id) : '');
    redirectTo($volver);
}

$params = [
    // Normaliza los campos opcionales y convierte la capacidad a entero para SQL.
    'name'     => $data['name'],
    'address'  => $data['address'],
    'city'     => $data['city'],
    'province' => $data['province'] !== '' ? $data['province'] : null,
    'capacity' => (int) $data['capacity'],
];

try {
    // Decide entre alta y edición según la presencia del identificador.
    if ($id === '') {
        $stmt = $pdo->prepare(
            'INSERT INTO venues (name, address, city, province, capacity)
             VALUES (:name, :address, :city, :province, :capacity)'
        );
        $stmt->execute($params);
        setFlash('success', 'Lugar creado correctamente.');
    } else {
        $params['id'] = $id;
        $stmt = $pdo->prepare(
            'UPDATE venues
                SET name = :name, address = :address, city = :city,
                    province = :province, capacity = :capacity
              WHERE id = :id'
        );
        $stmt->execute($params);
        setFlash('success', 'Lugar actualizado correctamente.');
    }
} catch (PDOException $e) {
    error_log('Error al guardar el lugar: ' . $e->getMessage());
    setFlash('danger', 'No se pudo guardar el lugar.');
}

redirectTo('/src/controllers/admin/venues/index.php');