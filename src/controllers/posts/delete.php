<?php
require_once __DIR__ . '/../../config/bootstrap.php';
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
    $stmt = $pdo->prepare('DELETE FROM posts WHERE id = :id');
    $stmt->execute(['id' => $id]);
    redirectWithFlash('/src/controllers/posts/index.php', 'success', 'Publicación eliminada correctamente.');
} catch (PDOException $e) {
    error_log('Error al eliminar la publicación: ' . $e->getMessage());
    redirectWithFlash('/src/controllers/posts/index.php', 'danger', 'No se pudo eliminar la publicación.');
}