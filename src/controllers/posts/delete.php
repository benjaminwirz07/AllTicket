<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    http_response_code(405);
    exit('Método no permitido');
}

try {
    $stmt = $pdo->prepare('DELETE FROM posts WHERE id = :id');
    $stmt->execute(['id' => $_POST['id']]);

    header('Location: /src/controllers/posts/index.php');
    exit;
} catch (PDOException $e) {
    exit('Error al eliminar el post');
}