<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/uploads.php';
requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

$id = trim($_POST['id'] ?? '');

$data = [
    'name'        => trim($_POST['name'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
];

$errors = [];

if ($data['name'] === '' || strlen($data['name']) > 150) {
    $errors['name'] = 'El nombre es obligatorio (máximo 150 caracteres).';
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
    header('Location: /src/controllers/admin/artists/form.php' . ($id !== '' ? '?id=' . urlencode($id) : ''));
    exit;
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
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Artista creado correctamente.'];
    } else {
        $stmt = $pdo->prepare(
            'UPDATE artists SET name = :name, description = :description, image = :image WHERE id = :id'
        );
        $stmt->execute($params + ['id' => $id]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Artista actualizado correctamente.'];

        if ($imagenNueva !== null) {
            borrarImagenSubida($imagenActual);
        }
    }
} catch (PDOException $e) {
    borrarImagenSubida($imagenNueva);
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'No se pudo guardar el artista.'];
}

header('Location: /src/controllers/admin/artists/index.php');
exit;