<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

requireMethod('POST');
requireCsrfToken();

try {
    $id = inputUuid($_POST, 'id');
    $title = inputString($_POST, 'title');
    $content = inputString($_POST, 'content', false);
    if ($id === '' || $title === '' || mb_strlen($title, 'UTF-8') > 255 || mb_strlen($content, 'UTF-8') > 20000) {
        redirectWithFlash('/src/controllers/posts/index.php', 'danger', 'Revisá los datos de la publicación.');
    }

    $stmt = $pdo->prepare('UPDATE posts SET title = :title, content = :content WHERE id = :id');
    $stmt->execute([
        'id' => $id,
        'title' => $title,
        'content' => $content,
    ]);

    redirectWithFlash('/src/controllers/posts/index.php', 'success', 'Publicación actualizada correctamente.');
} catch (PDOException $e) {
    error_log('Error al actualizar la publicación: ' . $e->getMessage());
    redirectWithFlash('/src/controllers/posts/index.php', 'danger', 'No se pudo actualizar la publicación.');
}