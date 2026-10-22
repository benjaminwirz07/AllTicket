<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

$artist = null;

if (isset($_GET['id'])) {
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
    $imagenGuardada = $artist['image'] ?? null;
    $artist = $old;
    $artist['image'] = $imagenGuardada;
}

require_once __DIR__ . '/../../../views/admin/artists/form.php';