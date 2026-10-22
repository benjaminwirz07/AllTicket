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
    $stmt = $pdo->prepare('DELETE FROM artists WHERE id = :id');
    $stmt->execute(['id' => $id]);
    setFlash('success', 'Artista eliminado.');
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        setFlash('danger', 'No se puede eliminar: el artista tiene recitales asociados.');
    } else {
        error_log('Error al eliminar el artista: ' . $e->getMessage());
        setFlash('danger', 'Error al eliminar el artista.');
    }
}

redirectTo('/src/controllers/admin/artists/index.php');