<?php
// Recibe y valida el formulario administrativo antes de crear o actualizar un artista.
require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/uploads.php';
requireAuth();
requireAdmin($pdo);

requireMethod('POST');
requireCsrfToken();

$id = inputString($_POST, 'id');
// Un identificador inválido no debe alcanzar las consultas de edición.
if ($id !== '' && !isValidUuid($id)) {
    http_response_code(400);
    exit('Solicitud no válida');
}

$data = [
    'name'        => inputString($_POST, 'name'),
    'description' => inputString($_POST, 'description'),
];

$errors = [];

// Comprueba los campos de texto y sus límites antes de almacenar contenido.
if ($data['name'] === '' || mb_strlen($data['name'], 'UTF-8') > 150) {
    $errors['name'] = 'El nombre es obligatorio (máximo 150 caracteres).';
}
if (mb_strlen($data['description'], 'UTF-8') > 20000) {
    $errors['description'] = 'La descripción no puede superar los 20.000 caracteres.';
}

// Conserva la imagen existente cuando se edita sin subir un reemplazo.
$imagenActual = null;
if ($id !== '') {
    $stmt = $pdo->prepare('SELECT image FROM artists WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $imagenActual = $stmt->fetchColumn() ?: null;
}

// Procesa el archivo únicamente si los datos de texto pasaron la validación.
$imagenNueva = null;
if (empty($errors)) {
    try {
        $imagenNueva = guardarImagen($_FILES['image_file'] ?? [], 'artists');
    } catch (RuntimeException $e) {
        $errors['image'] = $e->getMessage();
    }
}

if (!empty($errors)) {
    // Los errores y valores se recuperan al volver al formulario.
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = $data + ['id' => $id];
    redirectTo('/src/controllers/admin/artists/form.php' . ($id !== '' ? '?id=' . urlencode($id) : ''));
}

$params = [
    // Reutiliza parámetros preparados para las operaciones de alta y modificación.
    'name'        => $data['name'],
    'description' => $data['description'] !== '' ? $data['description'] : null,
    'image'       => $imagenNueva ?? $imagenActual,
];

try {
    // El identificador vacío representa un alta; de lo contrario, se actualiza el artista.
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