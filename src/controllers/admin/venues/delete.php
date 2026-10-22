<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

requireMethod('POST');
requireCsrfToken();

$id = inputUuid($_POST, 'id');
if ($id === '') {
    http_response_code(400);
    exit('Solicitud no válida');
}

try {
    $stmt = $pdo->prepare('DELETE FROM venues WHERE id = :id');
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 0) {
        setFlash('warning', 'El lugar no existe o ya fue eliminado.');
    } else {
        setFlash('success', 'Lugar eliminado.');
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000' && (int) ($e->errorInfo[1] ?? 0) === 1451) {
        setFlash('danger', 'No se puede eliminar: el lugar tiene recitales asociados.');
    } else {
        error_log('Error al eliminar el lugar: ' . $e->getMessage());
        setFlash('danger', 'Error al eliminar el lugar.');
    }
}

redirectTo('/src/controllers/admin/venues/index.php');