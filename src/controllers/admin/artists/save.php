<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/uploads.php';
requireAuth();
requireAdmin($pdo);

requireMethod('POST');
requireCsrfToken();

$id = inputString($_POST, 'id');
if ($id !== '' && !isValidUuid($id)) {
    http_response_code(400);
    exit('Solicitud no válida');
}

$data = [
    'name'        => inputString($_POST, 'name'),
    'description' => inputString($_POST, 'description'),
];

$errors = [];

if ($data['name'] === '' || mb_strlen($data['name'], 'UTF-8') > 150) {
    $errors['name'] = 'El nombre es obligatorio (máximo 150 caracteres).';
}
if (mb_strlen($data['description'], 'UTF-8') > 20000) {
    $errors['description'] = 'La descripción no puede superar los 20.000 caracteres.';
}

// Imagen actual (si estamos editando)
$imagenActual = null;
if ($id !== '') {
    $stmt = $pdo->prepare('SELECT image FROM artists WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $imagenActual = $stmt->fetchColumn() ?: null;
}

// Imagen nueva (solo si el resto es válido, para no dejar archivos sueltos)
$imagenNueva = null;
if (empty($errors)) {
    try {
        $imagenNueva = guardarImagen($_FILES['image_file'] ?? [], 'artists');
    } catch (RuntimeException $e) {
        $errors['image'] = $e->getMessage();
    }
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = $data + ['id' => $id];
    redirectTo('/src/controllers/admin/artists/form.php' . ($id !== '' ? '?id=' . urlencode($id) : ''));
}

$params = [
    'name'        => $data['name'],
    'description' => $data['description'] !== '' ? $data['description'] : null,
    'image'       => $imagenNueva ?? $imagenActual,
];

try {
    if ($id === '') {
        $stmt = $pdo->prepare(
            'INSERT INTO artists (name, description, image) VALUES (:name, :description, :image)'
        );
        $stmt->execute($params);
        setFlash('success', 'Artista creado correctamente.');
    } else {
        $stmt = $pdo->prepare(
            'UPDATE artists SET name = :name, description = :description, image = :image WHERE id = :id'
        );
        $stmt->execute($params + ['id' => $id]);
        setFlash('success', 'Artista actualizado correctamente.');

        if ($imagenNueva !== null) {
            borrarImagenSubida($imagenActual);
        }
    }
} catch (PDOException $e) {
    borrarImagenSubida($imagenNueva);
    error_log('Error al guardar el artista: ' . $e->getMessage());
    setFlash('danger', 'No se pudo guardar el artista.');
}

redirectTo('/src/controllers/admin/artists/index.php');