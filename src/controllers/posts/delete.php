<?php
// Elimina una publicación del panel y borra su imagen asociada si el borrado tuvo éxito.
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/uploads.php';
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
    // Recupera la ruta de imagen antes de borrar el registro y sus referencias.
    $stmt = $pdo->prepare('SELECT image FROM posts WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $image = $stmt->fetchColumn();

    $stmt = $pdo->prepare('DELETE FROM posts WHERE id = :id');
    $stmt->execute(['id' => $id]);
    if ($stmt->rowCount() === 1 && is_string($image)) {
        borrarImagenSubida($image);
    }
    redirectWithFlash('/src/controllers/posts/index.php', 'success', 'Publicación eliminada correctamente.');
} catch (PDOException $e) {
    error_log('Error al eliminar la publicación: ' . $e->getMessage());
    redirectWithFlash('/src/controllers/posts/index.php', 'danger', 'No se pudo eliminar la publicación.');
}