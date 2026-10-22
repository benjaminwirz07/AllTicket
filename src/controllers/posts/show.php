<?php
require_once __DIR__ . '/../../config/bootstrap.php';
 $id = inputUuid($_GET, 'id');
if ($id === '') {
    http_response_code(400);
    exit('Solicitud no válida');
}
try {
    $query = '
        SELECT
            p.*,
            u.id AS user_id,
            u.usuario AS user_name
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = :id
    ';

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'id' => $id,
    ]);

    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    require_once __DIR__ . '/../../views/posts/show.php';
} catch (PDOException $e) {
    error_log('Error al consultar la publicación: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo cargar la publicación.');
}