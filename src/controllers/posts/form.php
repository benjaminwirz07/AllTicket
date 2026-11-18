<?php

// Carga los datos existentes de una publicación para el formulario de edición.
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/news.php';
requireAuth();
requireAdmin($pdo);
$post = null;

if (isset($_GET['id'])) {
  // Comprueba el UUID y devuelve un error claro si el registro solicitado no existe.
  $id = inputUuid($_GET, 'id');
  if ($id === '') {
    http_response_code(400);
    exit('Solicitud no válida');
  }
  $stmt = $pdo->prepare('
        SELECT *
        FROM posts
        WHERE id = :id
    ');

  $stmt->execute([
    'id' => $id
  ]);

  $post = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$post) {
    http_response_code(404);
    exit('No se encontró la publicación.');
  }
}

// Renderiza el formulario compartido de publicaciones.
require_once __DIR__ . '/../../views/posts/form.php';
