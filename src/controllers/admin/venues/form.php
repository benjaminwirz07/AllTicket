<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAdmin($pdo);

$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

$venue = null;

if (!empty($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM venues WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_GET['id']]);
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