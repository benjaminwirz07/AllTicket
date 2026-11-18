<?php
// Prepara los datos iniciales o recupera el estado del formulario de artista.
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

// Recupera errores y valores previos guardados por el controlador de persistencia.
$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

$artist = null;

if (isset($_GET['id'])) {
    // En modo edición, valida el UUID y carga el registro que se mostrará en el formulario.
    $id = inputUuid($_GET, 'id');
    if ($id === '') {
        http_response_code(400);
        exit('Solicitud no válida');
    }
    $stmt = $pdo->prepare('SELECT * FROM artists WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$artist) {
        http_response_code(404);
        exit('Artista no encontrado');
    }
}

if (!empty($old)) {
    // Mantiene la imagen guardada mientras restaura el resto de los campos enviados.
    $imagenGuardada = $artist['image'] ?? null;
    $artist = $old;
    $artist['image'] = $imagenGuardada;
}

// La vista comparte el formulario entre creación y edición.
require_once __DIR__ . '/../../../views/admin/artists/form.php';