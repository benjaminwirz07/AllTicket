<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/news.php';
require_once __DIR__ . '/../../config/uploads.php';
requireAuth();
requireAdmin($pdo);

requireMethod('POST');
requireCsrfToken();

$title = inputString($_POST, 'title');
$content = inputString($_POST, 'content', false);
$summary = inputString($_POST, 'summary');
$category = inputString($_POST, 'category');
$featured = ($_POST['featured'] ?? '') === '1' ? 1 : 0;
if (
    $title === ''
    || mb_strlen($title, 'UTF-8') > 255
    || mb_strlen($content, 'UTF-8') > 20000
    || mb_strlen($summary, 'UTF-8') > 500
    || !in_array($category, NEWS_CATEGORIES, true)
) {
    redirectWithFlash('/src/controllers/posts/form.php', 'danger', 'Revisá el título y el contenido de la publicación.');
}
$summary = newsSummary($summary, $content);

$image = null;
try {
    $image = guardarImagen($_FILES['image_file'] ?? [], 'news');
    $id = (string) $pdo->query('SELECT UUID()')->fetchColumn();
    $slug = newsSlug($title, $id);

    $stmt = $pdo->prepare(
        'INSERT INTO posts (id, title, slug, content, summary, category, image, reading_minutes, featured, user_id)
         VALUES (:id, :title, :slug, :content, :summary, :category, :image, :reading_minutes, :featured, :user_id)'
    );
    $stmt->execute([
        'id' => $id,
        'title' => $title,
        'slug' => $slug,
        'content' => $content,
        'summary' => $summary,
        'category' => $category,
        'image' => $image,
        'reading_minutes' => newsReadingMinutes($content),
        'featured' => $featured,
        'user_id' => currentUserId(),
    ]);

    redirectWithFlash('/src/controllers/posts/index.php', 'success', 'Publicación creada correctamente.');
} catch (RuntimeException $e) {
    redirectWithFlash('/src/controllers/posts/form.php', 'danger', $e->getMessage());
} catch (PDOException $e) {
    borrarImagenSubida($image);
    error_log('Error al crear la publicación: ' . $e->getMessage());
    redirectWithFlash('/src/controllers/posts/index.php', 'danger', 'No se pudo crear la publicación.');
}