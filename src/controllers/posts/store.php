<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

requireMethod('POST');
requireCsrfToken();

try {
    $title = inputString($_POST, 'title');
    $content = inputString($_POST, 'content', false);
    if ($title === '' || mb_strlen($title, 'UTF-8') > 255 || mb_strlen($content, 'UTF-8') > 20000) {
        redirectWithFlash('/src/controllers/posts/index.php', 'danger', 'Revisá el título y el contenido de la publicación.');
    }

    $stmt = $pdo->prepare('INSERT INTO posts (title, content, user_id) VALUES (:title, :content, :user_id)');
    $stmt->execute([
        'title' => $title,
        'content' => $content,
        'user_id' => currentUserId(),
    ]);

    redirectWithFlash('/src/controllers/posts/index.php', 'success', 'Publicación creada correctamente.');
} catch (PDOException $e) {
    error_log('Error al crear la publicación: ' . $e->getMessage());
    redirectWithFlash('/src/controllers/posts/index.php', 'danger', 'No se pudo crear la publicación.');
}