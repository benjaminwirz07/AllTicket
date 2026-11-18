<?php
// Prepara el formulario de lugar para crear un registro o modificar uno existente.
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

$venue = null;

if (isset($_GET['id'])) {
    // Valida el UUID solicitado y recupera el lugar antes de habilitar su edición.
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

// Restaura los datos ingresados cuando el controlador de guardado reportó errores.
if (!empty($old)) {
    $venue = $old;
}

// Renderiza el mismo formulario en los modos de alta y edición.
require_once __DIR__ . '/../../../views/admin/venues/form.php';