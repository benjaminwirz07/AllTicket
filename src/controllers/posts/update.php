<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/news.php';
require_once __DIR__ . '/../../config/uploads.php';
requireAuth();
requireAdmin($pdo);

requireMethod('POST');
requireCsrfToken();

$id = inputUuid($_POST, 'id');
$title = inputString($_POST, 'title');
$content = inputString($_POST, 'content', false);
$summary = inputString($_POST, 'summary');
$category = inputString($_POST, 'category');
$featured = ($_POST['featured'] ?? '') === '1' ? 1 : 0;
if (
    $id === ''
    || $title === ''
    || mb_strlen($title, 'UTF-8') > 255
    || mb_strlen($content, 'UTF-8') > 20000
    || mb_strlen($summary, 'UTF-8') > 500
    || !in_array($category, NEWS_CATEGORIES, true)
) {
    redirectWithFlash('/src/controllers/posts/index.php', 'danger', 'Revisá los datos de la publicación.');
}
$summary = newsSummary($summary, $content);

$imageNew = null;
try {
    $stmt = $pdo->prepare('SELECT image FROM posts WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $imageCurrent = $stmt->fetchColumn();
    if ($imageCurrent === false) {
        redirectWithFlash('/src/controllers/posts/index.php', 'danger', 'No se encontró la publicación.');
    }

    $imageNew = guardarImagen($_FILES['image_file'] ?? [], 'news');
    $stmt = $pdo->prepare(
        'UPDATE posts
            SET title = :title, slug = :slug, content = :content, summary = :summary,
                category = :category, image = :image, reading_minutes = :reading_minutes,
                featured = :featured
          WHERE id = :id'
    );
    $stmt->execute([
        'id' => $id,
        'title' => $title,
        'slug' => newsSlug($title, $id),
        'content' => $content,
        'summary' => $summary,
        'category' => $category,
        'image' => $imageNew ?? $imageCurrent,
        'reading_minutes' => newsReadingMinutes($content),
        'featured' => $featured,
    ]);

    if ($imageNew !== null) {
        borrarImagenSubida(is_string($imageCurrent) ? $imageCurrent : null);
    }
    redirectWithFlash('/src/controllers/posts/index.php', 'success', 'Publicación actualizada correctamente.');
} catch (RuntimeException $e) {
    redirectWithFlash('/src/controllers/posts/form.php?id=' . urlencode($id), 'danger', $e->getMessage());
} catch (PDOException $e) {
    borrarImagenSubida($imageNew);
    error_log('Error al actualizar la publicación: ' . $e->getMessage());
    redirectWithFlash('/src/controllers/posts/index.php', 'danger', 'No se pudo actualizar la publicación.');
}