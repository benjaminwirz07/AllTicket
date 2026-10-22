<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

$venue = null;

if (isset($_GET['id'])) {
    $id = inputUuid($_GET, 'id');
    if ($id === '') {
        http_response_code(400);
        exit('Solicitud no válida');
    }
    $stmt = $pdo->prepare('SELECT * FROM venues WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $venue = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venue) {
        http_response_code(404);
        exit('Lugar no encontrado');
    }
}

// Si hubo errores al guardar, mostramos lo que la persona había escrito
if (!empty($old)) {
    $venue = $old;
}

require_once __DIR__ . '/../../../views/admin/venues/form.php';