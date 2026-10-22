<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);
try {
    $query = '
        SELECT
            p.*,
            u.id AS user_id,
            u.usuario AS user_name
        FROM posts p
        JOIN users u ON p.user_id = u.id
        ORDER BY created_at DESC
    ';

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    require_once __DIR__ . '/../../views/posts/index.php';
} catch (PDOException $e) {
    error_log('Error al consultar las publicaciones: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudieron cargar las publicaciones.');
}
