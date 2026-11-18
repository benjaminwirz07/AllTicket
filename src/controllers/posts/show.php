<?php
// Busca una publicación por slug o UUID y prepara artículos relacionados.
require_once __DIR__ . '/../../config/bootstrap.php';
$slug = inputString($_GET, 'slug');
$id = inputUuid($_GET, 'id');
// Exige al menos una clave de búsqueda antes de ejecutar la consulta.
if ($slug === '' && $id === '') {
    http_response_code(400);
    exit('Solicitud no válida');
}
try {
    // Selecciona la clave de consulta segura y recupera autor y contenido editorial.
    $lookup = $slug !== '' ? 'p.slug = :lookup' : 'p.id = :lookup';
    $query = '
        SELECT
            p.*,
            u.id AS user_id,
            u.usuario AS user_name
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE ' . $lookup . '
        LIMIT 1
    ';

    $stmt = $pdo->prepare($query);
    $stmt->execute(['lookup' => $slug !== '' ? $slug : $id]);

    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    // Si hay una categoría, carga hasta tres artículos distintos para recomendar.
    $relatedPosts = [];
    if ($post && !empty($post['category'])) {
        $stmtRelated = $pdo->prepare(
            'SELECT id, slug, title, summary, content, category, image, reading_minutes, created_at
               FROM posts
              WHERE category = :category AND id <> :id
              ORDER BY created_at DESC
              LIMIT 3'
        );
        $stmtRelated->execute(['category' => $post['category'], 'id' => $post['id']]);
        $relatedPosts = $stmtRelated->fetchAll(PDO::FETCH_ASSOC);
    }

    // Entrega el artículo y las recomendaciones a la plantilla pública.
    require_once __DIR__ . '/../../views/posts/show.php';
} catch (PDOException $e) {
    error_log('Error al consultar la publicación: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo cargar la publicación.');
}